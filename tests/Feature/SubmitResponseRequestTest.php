<?php

namespace Tests\Feature;

use App\Http\Requests\SubmitResponseRequest;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Survey;
use App\Models\SurveyCategory;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SubmitResponseRequestTest extends TestCase
{
    use DatabaseTransactions;

    private const PASSWORD = 'Password-Awal-123';

    private Carbon $base;

    private ?SurveyCategory $category = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Route khusus test: tidak didaftarkan ke aplikasi produksi.
        Route::middleware('web')->post('/uji/isi-survei/{survey}', function (SubmitResponseRequest $request, Survey $survey) {
            return response()->json(['data' => $request->validated()]);
        });

        $this->base = Carbon::parse('2026-11-10 10:00:00');
        Carbon::setTestNow($this->base);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function admin(): User
    {
        return User::create([
            'username' => 'admin_'.uniqid(),
            'password' => self::PASSWORD,
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    private function makeClass(bool $active = true): SchoolClass
    {
        return SchoolClass::create([
            'name' => 'Kelas Uji '.uniqid(),
            'academic_year' => '2026/2027',
            'is_active' => $active,
        ]);
    }

    private function studentUser(bool $active = true, ?SchoolClass $class = null): User
    {
        $user = User::create([
            'username' => 'siswa_'.uniqid(),
            'password' => self::PASSWORD,
            'role' => 'student',
            'is_active' => $active,
        ]);

        Student::create([
            'user_id' => $user->id,
            'nis' => 'NIS'.uniqid(),
            'name' => 'Siswa '.uniqid(),
            'class_id' => ($class ?? $this->makeClass())->id,
        ]);

        return $user;
    }

    private function teacherUser(bool $active = true): User
    {
        $user = User::create([
            'username' => 'guru_'.uniqid(),
            'password' => self::PASSWORD,
            'role' => 'teacher',
            'is_active' => $active,
        ]);

        Teacher::create([
            'user_id' => $user->id,
            'name' => 'Guru '.uniqid(),
        ]);

        return $user;
    }

    private function makeCategory(): SurveyCategory
    {
        return $this->category ??= SurveyCategory::create([
            'name' => 'Kategori Uji '.uniqid(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeSurvey(array $overrides = []): Survey
    {
        $creator = $overrides['creator_id'] ?? $this->admin()->id;
        unset($overrides['creator_id']);

        return Survey::create(array_merge([
            'creator_id' => $creator,
            'category_id' => $this->makeCategory()->id,
            'title' => 'Survei '.uniqid(),
            'description' => 'Deskripsi survei uji.',
            'student_target_mode' => 'none',
            'teacher_target_mode' => 'none',
            'status' => 'published',
            'starts_at' => $this->base->copy()->subHour(),
            'ends_at' => $this->base->copy()->addHour(),
            'is_result_published' => false,
        ], $overrides));
    }

    private function studentSurvey(array $overrides = []): Survey
    {
        return $this->makeSurvey(array_merge(['student_target_mode' => 'all'], $overrides));
    }

    private function makeQuestion(Survey $survey, string $type, bool $required = true, int $position = 1): Question
    {
        return Question::create([
            'survey_id' => $survey->id,
            'question_text' => 'Pertanyaan '.uniqid(),
            'type' => $type,
            'is_required' => $required,
            'position' => $position,
        ]);
    }

    private function makeOption(Question $question, int $position = 1): QuestionOption
    {
        return QuestionOption::create([
            'question_id' => $question->id,
            'option_text' => 'Opsi '.uniqid(),
            'position' => $position,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function submit(User $user, Survey $survey, array $payload)
    {
        return $this->actingAs($user)->postJson('/uji/isi-survei/'.$survey->id, $payload);
    }

    /**
     * @param  array<int, mixed>  $optionIds
     * @return array<string, mixed>
     */
    private function multiplePayload(Question $question, array $optionIds): array
    {
        return ['answers' => [$question->id => ['option_ids' => $optionIds]]];
    }

    // ------------------------------------------------------------ single choice

    public function test_single_choice_valid(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $question = $this->makeQuestion($survey, 'single_choice');
        $option = $this->makeOption($question);

        $this->submit($student, $survey, [
            'answers' => [$question->id => ['option_id' => $option->id]],
        ])->assertOk()->assertJsonPath('data.answers.'.$question->id.'.option_id', $option->id);
    }

    public function test_single_choice_required_but_empty_is_rejected(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $question = $this->makeQuestion($survey, 'single_choice');
        $this->makeOption($question);

        $this->submit($student, $survey, ['answers' => [$question->id => []]])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['answers.'.$question->id.'.option_id']);
    }

    public function test_single_choice_with_two_choices_is_rejected(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $question = $this->makeQuestion($survey, 'single_choice');
        $first = $this->makeOption($question, 1);
        $second = $this->makeOption($question, 2);

        $this->submit($student, $survey, [
            'answers' => [$question->id => ['option_id' => [$first->id, $second->id]]],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['answers.'.$question->id.'.option_id']);
    }

    public function test_single_choice_with_option_from_other_question_is_rejected(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $question = $this->makeQuestion($survey, 'single_choice', position: 1);
        $this->makeOption($question);
        $other = $this->makeQuestion($survey, 'single_choice', position: 2);
        $foreignOption = $this->makeOption($other);

        $this->submit($student, $survey, [
            'answers' => [$question->id => ['option_id' => $foreignOption->id]],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['answers.'.$question->id.'.option_id']);
    }

    // ------------------------------------------------------------ multiple choice

    public function test_multiple_choice_valid(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $question = $this->makeQuestion($survey, 'multiple_choice');
        $first = $this->makeOption($question, 1);
        $second = $this->makeOption($question, 2);

        $this->submit($student, $survey, $this->multiplePayload($question, [$first->id, $second->id]))
            ->assertOk();
    }

    public function test_multiple_choice_required_but_empty_is_rejected(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $question = $this->makeQuestion($survey, 'multiple_choice');

        $this->submit($student, $survey, $this->multiplePayload($question, []))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['answers.'.$question->id.'.option_ids']);
    }

    public function test_multiple_choice_with_duplicate_ids_is_rejected(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $question = $this->makeQuestion($survey, 'multiple_choice');
        $option = $this->makeOption($question);

        $this->submit($student, $survey, $this->multiplePayload($question, [$option->id, $option->id]))
            ->assertStatus(422)
            ->assertInvalid(['answers.'.$question->id.'.option_ids.0' => 'duplikat']);
    }

    public function test_multiple_choice_with_option_from_other_question_is_rejected(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $question = $this->makeQuestion($survey, 'multiple_choice', position: 1);
        $this->makeOption($question);
        $other = $this->makeQuestion($survey, 'multiple_choice', position: 2);
        $foreignOption = $this->makeOption($other);

        $this->submit($student, $survey, $this->multiplePayload($question, [$foreignOption->id]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['answers.'.$question->id.'.option_ids.0']);
    }

    public function test_multiple_choice_with_three_options_is_valid(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $question = $this->makeQuestion($survey, 'multiple_choice');
        $ids = [
            $this->makeOption($question, 1)->id,
            $this->makeOption($question, 2)->id,
            $this->makeOption($question, 3)->id,
        ];

        $this->submit($student, $survey, $this->multiplePayload($question, $ids))->assertOk();
    }

    // ------------------------------------------------------------ rating

    public function test_rating_value_one_is_valid(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $question = $this->makeQuestion($survey, 'rating');

        $this->submit($student, $survey, ['answers' => [$question->id => ['rating' => 1]]])
            ->assertOk();
    }

    public function test_rating_value_five_is_valid(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $question = $this->makeQuestion($survey, 'rating');

        $this->submit($student, $survey, ['answers' => [$question->id => ['rating' => 5]]])
            ->assertOk();
    }

    public function test_rating_value_zero_is_rejected(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $question = $this->makeQuestion($survey, 'rating');

        $this->submit($student, $survey, ['answers' => [$question->id => ['rating' => 0]]])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['answers.'.$question->id.'.rating']);
    }

    public function test_rating_value_six_is_rejected(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $question = $this->makeQuestion($survey, 'rating');

        $this->submit($student, $survey, ['answers' => [$question->id => ['rating' => 6]]])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['answers.'.$question->id.'.rating']);
    }

    public function test_rating_non_integer_is_rejected(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $question = $this->makeQuestion($survey, 'rating');

        $this->submit($student, $survey, ['answers' => [$question->id => ['rating' => 'tiga']]])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['answers.'.$question->id.'.rating']);
    }

    // ------------------------------------------------------------ text

    public function test_short_text_with_255_characters_is_valid(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $question = $this->makeQuestion($survey, 'short_text');

        $this->submit($student, $survey, [
            'answers' => [$question->id => ['text' => str_repeat('a', 255)]],
        ])->assertOk();
    }

    public function test_short_text_longer_than_255_characters_is_rejected(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $question = $this->makeQuestion($survey, 'short_text');

        $this->submit($student, $survey, [
            'answers' => [$question->id => ['text' => str_repeat('a', 256)]],
        ])->assertStatus(422)
            ->assertInvalid(['answers.'.$question->id.'.text' => '255']);
    }

    public function test_long_text_with_5000_characters_is_valid(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $question = $this->makeQuestion($survey, 'long_text');

        $this->submit($student, $survey, [
            'answers' => [$question->id => ['text' => str_repeat('a', 5000)]],
        ])->assertOk();
    }

    public function test_long_text_longer_than_5000_characters_is_rejected(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $question = $this->makeQuestion($survey, 'long_text');

        $this->submit($student, $survey, [
            'answers' => [$question->id => ['text' => str_repeat('a', 5001)]],
        ])->assertStatus(422)
            ->assertInvalid(['answers.'.$question->id.'.text' => '5000']);
    }

    public function test_whitespace_only_is_rejected_for_required_text(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $question = $this->makeQuestion($survey, 'short_text');

        $this->submit($student, $survey, [
            'answers' => [$question->id => ['text' => '    ']],
        ])->assertStatus(422)
            ->assertInvalid(['answers.'.$question->id.'.text' => 'wajib']);
    }

    public function test_whitespace_only_is_treated_as_empty_for_optional_text(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $question = $this->makeQuestion($survey, 'long_text', required: false);

        $this->submit($student, $survey, [
            'answers' => [$question->id => ['text' => "  \n  "]],
        ])->assertOk()
            ->assertJsonPath('data.answers.'.$question->id.'.text', null);
    }

    public function test_empty_optional_answers_are_valid(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $single = $this->makeQuestion($survey, 'single_choice', required: false, position: 1);
        $multiple = $this->makeQuestion($survey, 'multiple_choice', required: false, position: 2);
        $rating = $this->makeQuestion($survey, 'rating', required: false, position: 3);
        $short = $this->makeQuestion($survey, 'short_text', required: false, position: 4);
        $long = $this->makeQuestion($survey, 'long_text', required: false, position: 5);

        $this->submit($student, $survey, [
            'answers' => [
                $single->id => [],
                $multiple->id => ['option_ids' => []],
                $rating->id => ['rating' => null],
                $short->id => ['text' => '   '],
                $long->id => [],
            ],
        ])->assertOk();
    }

    public function test_filled_optional_answers_are_valid(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $question = $this->makeQuestion($survey, 'single_choice', required: false);
        $option = $this->makeOption($question);

        $this->submit($student, $survey, [
            'answers' => [$question->id => ['option_id' => $option->id]],
        ])->assertOk();
    }

    // ------------------------------------------------------------ payload shape

    public function test_question_from_another_survey_is_rejected(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $otherSurvey = $this->makeSurvey();
        $foreignQuestion = $this->makeQuestion($otherSurvey, 'short_text');

        $this->submit($student, $survey, [
            'answers' => [$foreignQuestion->id => ['text' => 'jawaban']],
        ])->assertStatus(422)
            ->assertInvalid(['answers' => 'bukan bagian dari survei ini']);
    }

    public function test_mismatched_field_for_question_type_is_rejected(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $rating = $this->makeQuestion($survey, 'rating', position: 1);
        $single = $this->makeQuestion($survey, 'single_choice', position: 2);
        $singleOption = $this->makeOption($single);
        $text = $this->makeQuestion($survey, 'short_text', position: 3);

        $this->submit($student, $survey, [
            'answers' => [
                $rating->id => ['option_id' => $singleOption->id],
                $single->id => ['option_ids' => [$singleOption->id]],
                $text->id => ['rating' => 3],
            ],
        ])->assertStatus(422)
            ->assertInvalid([
                'answers.'.$rating->id => 'tidak sesuai',
                'answers.'.$single->id => 'tidak sesuai',
                'answers.'.$text->id => 'tidak sesuai',
            ]);
    }

    public function test_payload_without_answers_fails_when_questions_are_required(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $question = $this->makeQuestion($survey, 'rating');

        $this->submit($student, $survey, [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['answers.'.$question->id.'.rating']);
    }

    public function test_payload_without_answers_is_valid_when_all_questions_are_optional(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $this->makeQuestion($survey, 'rating', required: false);

        $this->submit($student, $survey, [])->assertOk();
    }

    public function test_identity_keys_in_payload_are_rejected(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $question = $this->makeQuestion($survey, 'single_choice');
        $option = $this->makeOption($question);

        $this->submit($student, $survey, [
            'user_id' => $student->id,
            'survey_id' => $survey->id,
            'answers' => [$question->id => ['option_id' => $option->id, 'question_id' => $question->id]],
        ])->assertStatus(422)
            ->assertInvalid([
                'user_id' => 'identitas',
                'survey_id' => 'identitas',
                'answers.'.$question->id => 'tidak sesuai',
            ]);
    }

    // ------------------------------------------------------------ authorization

    public function test_inactive_user_is_rejected(): void
    {
        $student = $this->studentUser(active: false);
        $survey = $this->studentSurvey();
        $question = $this->makeQuestion($survey, 'rating', required: false);

        $this->submit($student, $survey, ['answers' => [$question->id => ['rating' => 3]]])
            ->assertForbidden();
    }

    public function test_admin_is_rejected(): void
    {
        $survey = $this->studentSurvey();
        $question = $this->makeQuestion($survey, 'rating', required: false);

        $this->submit($this->admin(), $survey, ['answers' => [$question->id => ['rating' => 3]]])
            ->assertForbidden();
    }

    public function test_eligible_student_and_teacher_pass_authorization(): void
    {
        $survey = $this->makeSurvey([
            'student_target_mode' => 'all',
            'teacher_target_mode' => 'all',
        ]);
        $question = $this->makeQuestion($survey, 'rating', required: false);
        $payload = ['answers' => [$question->id => ['rating' => 3]]];

        $this->submit($this->studentUser(), $survey, $payload)->assertOk();
        $this->submit($this->teacherUser(), $survey, $payload)->assertOk();
    }

    public function test_ineligible_student_and_teacher_are_rejected(): void
    {
        $survey = $this->makeSurvey([
            'student_target_mode' => 'classes',
            'teacher_target_mode' => 'selected',
        ]);
        $survey->targetClasses()->attach($this->makeClass()->id);
        $survey->targetTeachers()->attach($this->teacherUser()->teacher->id);

        $question = $this->makeQuestion($survey, 'rating', required: false);
        $payload = ['answers' => [$question->id => ['rating' => 3]]];

        $this->submit($this->studentUser(), $survey, $payload)->assertForbidden();
        $this->submit($this->teacherUser(), $survey, $payload)->assertForbidden();
    }
}
