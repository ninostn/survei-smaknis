<?php

namespace Tests\Feature;

use App\Exceptions\DuplicateResponseException;
use App\Http\Controllers\SurveyResponseController;
use App\Http\Requests\SubmitResponseRequest;
use App\Models\Answer;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Response;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Survey;
use App\Models\SurveyCategory;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ResponseSubmissionTest extends TestCase
{
    use DatabaseTransactions;

    private const PASSWORD = 'Password-Awal-123';

    private Carbon $base;

    private ?SurveyCategory $category = null;

    protected function setUp(): void
    {
        parent::setUp();

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
    private function postAnswers(User $user, Survey $survey, array $payload)
    {
        return $this->actingAs($user)->post(route('responses.store', $survey), $payload);
    }

    /**
     * Simulasi pemanggilan controller setelah FormRequest lolos, untuk menguji
     * race condition, duplicate constraint, dan rollback.
     *
     * @param  array<int|string, mixed>  $answers
     */
    private function storeDirectly(User $user, Survey $survey, array $answers = []): RedirectResponse
    {
        $request = new class extends SubmitResponseRequest
        {
            /** @var array<string, mixed> */
            public array $payload = [];

            public function validated($key = null, $default = null)
            {
                return $key === null ? $this->payload : ($this->payload[$key] ?? $default);
            }
        };

        $request->setUserResolver(fn () => $user);
        $request->payload = ['answers' => $answers];

        return app(SurveyResponseController::class)->store($request, $survey);
    }

    /**
     * @return array<string, mixed>
     */
    private function fullPayload(Question $single, QuestionOption $singleOption, Question $multiple, array $multipleOptions, Question $rating, Question $short, Question $long): array
    {
        return [
            'answers' => [
                $single->id => ['option_id' => $singleOption->id],
                $multiple->id => ['option_ids' => array_map(fn (QuestionOption $option) => $option->id, $multipleOptions)],
                $rating->id => ['rating' => 4],
                $short->id => ['text' => 'Jawaban singkat'],
                $long->id => ['text' => 'Jawaban panjang yang lebih lengkap.'],
            ],
        ];
    }

    // ------------------------------------------------------------ normal submission

    public function test_student_can_submit_survey(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $single = $this->makeQuestion($survey, 'single_choice', position: 1);
        $singleOption = $this->makeOption($single);

        $response = $this->postAnswers($student, $survey, [
            'answers' => [$single->id => ['option_id' => $singleOption->id]],
        ]);

        $response->assertRedirect(route('responses.index'));
        $response->assertSessionHas('status', 'Jawaban berhasil dikirim.');

        $this->assertSame(1, Response::where('survey_id', $survey->id)->where('user_id', $student->id)->count());
        $this->assertSame(1, Answer::whereIn('question_id', $survey->questions()->pluck('id'))->count());
    }

    public function test_teacher_can_submit_survey(): void
    {
        $teacher = $this->teacherUser();
        $survey = $this->makeSurvey(['teacher_target_mode' => 'all']);
        $question = $this->makeQuestion($survey, 'rating');

        $this->postAnswers($teacher, $survey, ['answers' => [$question->id => ['rating' => 5]]])
            ->assertRedirect(route('responses.index'));

        $this->assertDatabaseHas('responses', [
            'survey_id' => $survey->id,
            'user_id' => $teacher->id,
        ]);
    }

    public function test_each_answer_type_is_stored_correctly(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();

        $single = $this->makeQuestion($survey, 'single_choice', position: 1);
        $singleOption = $this->makeOption($single);

        $multiple = $this->makeQuestion($survey, 'multiple_choice', position: 2);
        $multipleOptions = [
            $this->makeOption($multiple, 1),
            $this->makeOption($multiple, 2),
        ];

        $rating = $this->makeQuestion($survey, 'rating', position: 3);
        $short = $this->makeQuestion($survey, 'short_text', position: 4);
        $long = $this->makeQuestion($survey, 'long_text', position: 5);

        $this->postAnswers($student, $survey, $this->fullPayload(
            $single, $singleOption, $multiple, $multipleOptions, $rating, $short, $long,
        ))->assertRedirect(route('responses.index'));

        $response = Response::where('survey_id', $survey->id)->where('user_id', $student->id)->sole();

        $singleAnswer = Answer::where('response_id', $response->id)->where('question_id', $single->id)->sole();
        $this->assertNull($singleAnswer->answer_text);
        $this->assertNull($singleAnswer->rating_value);
        $this->assertSame(
            [$singleOption->id],
            DB::table('answer_options')->where('answer_id', $singleAnswer->id)->pluck('option_id')->all(),
        );

        $multipleAnswer = Answer::where('response_id', $response->id)->where('question_id', $multiple->id)->sole();
        $this->assertEqualsCanonicalizing(
            [$multipleOptions[0]->id, $multipleOptions[1]->id],
            DB::table('answer_options')->where('answer_id', $multipleAnswer->id)->pluck('option_id')->all(),
        );

        $ratingAnswer = Answer::where('response_id', $response->id)->where('question_id', $rating->id)->sole();
        $this->assertSame(4, $ratingAnswer->rating_value);
        $this->assertNull($ratingAnswer->answer_text);

        $shortAnswer = Answer::where('response_id', $response->id)->where('question_id', $short->id)->sole();
        $this->assertSame('Jawaban singkat', $shortAnswer->answer_text);
        $this->assertNull($shortAnswer->rating_value);

        $longAnswer = Answer::where('response_id', $response->id)->where('question_id', $long->id)->sole();
        $this->assertSame('Jawaban panjang yang lebih lengkap.', $longAnswer->answer_text);

        $this->assertSame(5, Answer::where('response_id', $response->id)->count());
    }

    public function test_submitted_at_uses_application_timezone(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $question = $this->makeQuestion($survey, 'rating');

        $this->postAnswers($student, $survey, ['answers' => [$question->id => ['rating' => 3]]]);

        $response = Response::where('survey_id', $survey->id)->where('user_id', $student->id)->sole();

        $this->assertSame($this->base->toDateTimeString(), $response->submitted_at->toDateTimeString());
        $this->assertSame(
            $this->base->format('Y-m-d H:i:s'),
            DB::table('responses')->where('id', $response->id)->value('submitted_at'),
        );
    }

    // ------------------------------------------------------------ data integrity

    public function test_identity_comes_from_session_and_route_not_payload(): void
    {
        $student = $this->studentUser();
        $other = $this->admin();
        $survey = $this->studentSurvey();
        $otherSurvey = $this->makeSurvey();
        $question = $this->makeQuestion($survey, 'rating');

        $this->actingAs($student)
            ->postJson(route('responses.store', $survey), [
                'user_id' => $other->id,
                'survey_id' => $otherSurvey->id,
                'answers' => [$question->id => ['rating' => 3]],
            ])
            ->assertStatus(422);

        $this->assertSame(0, Response::where('survey_id', $survey->id)->count());
        $this->assertSame(0, Response::where('user_id', $other->id)->count());
    }

    public function test_answer_options_pivot_contains_question_id(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $question = $this->makeQuestion($survey, 'multiple_choice');
        $first = $this->makeOption($question, 1);
        $second = $this->makeOption($question, 2);

        $this->postAnswers($student, $survey, [
            'answers' => [$question->id => ['option_ids' => [$first->id, $second->id]]],
        ]);

        $answer = Answer::where('question_id', $question->id)->sole();
        $pivot = DB::table('answer_options')->where('answer_id', $answer->id)->get();

        $this->assertCount(2, $pivot);
        $this->assertSame([$question->id, $question->id], $pivot->pluck('question_id')->all());
    }

    public function test_option_from_another_question_cannot_be_stored(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $question = $this->makeQuestion($survey, 'single_choice', position: 1);
        $this->makeOption($question);
        $other = $this->makeQuestion($survey, 'single_choice', position: 2);
        $foreignOption = $this->makeOption($other);

        $this->actingAs($student)
            ->postJson(route('responses.store', $survey), [
                'answers' => [$question->id => ['option_id' => $foreignOption->id]],
            ])
            ->assertStatus(422);

        $this->assertSame(0, Response::where('survey_id', $survey->id)->count());
        $this->assertSame(0, Answer::whereIn('question_id', [$question->id, $other->id])->count());
    }

    public function test_empty_optional_answers_create_no_answer_rows(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $single = $this->makeQuestion($survey, 'single_choice', required: false, position: 1);
        $multiple = $this->makeQuestion($survey, 'multiple_choice', required: false, position: 2);
        $rating = $this->makeQuestion($survey, 'rating', required: false, position: 3);
        $short = $this->makeQuestion($survey, 'short_text', required: false, position: 4);
        $long = $this->makeQuestion($survey, 'long_text', required: false, position: 5);

        $this->postAnswers($student, $survey, [
            'answers' => [
                $single->id => ['option_id' => null],
                $multiple->id => ['option_ids' => []],
                $rating->id => ['rating' => null],
                $short->id => ['text' => '   '],
                $long->id => [],
            ],
        ])->assertRedirect(route('responses.index'));

        $response = Response::where('survey_id', $survey->id)->sole();
        $this->assertSame(0, Answer::where('response_id', $response->id)->count());
        $this->assertSame(0, DB::table('answer_options')->whereIn('question_id', $survey->questions()->pluck('id'))->count());
    }

    public function test_filled_optional_answer_is_stored(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $question = $this->makeQuestion($survey, 'short_text', required: false);

        $this->postAnswers($student, $survey, [
            'answers' => [$question->id => ['text' => '  catatan tambahan  ']],
        ]);

        $answer = Answer::where('question_id', $question->id)->sole();
        $this->assertSame('catatan tambahan', $answer->answer_text);
    }

    public function test_each_question_produces_one_answer(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $question = $this->makeQuestion($survey, 'multiple_choice');
        $option = $this->makeOption($question);

        $this->postAnswers($student, $survey, [
            'answers' => [$question->id => ['option_ids' => [$option->id]]],
        ]);

        $this->assertSame(1, Answer::where('question_id', $question->id)->count());
    }

    // ------------------------------------------------------------ atomicity

    public function test_failure_while_storing_answer_rolls_back_response(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $first = $this->makeQuestion($survey, 'rating', position: 1);
        $second = $this->makeQuestion($survey, 'rating', position: 2);

        $created = 0;
        Event::listen('eloquent.creating: '.Answer::class, function () use (&$created): void {
            $created++;

            if ($created === 2) {
                throw new RuntimeException('Gagal menyimpan jawaban kedua.');
            }
        });

        try {
            $this->storeDirectly($student, $survey, [
                $first->id => ['rating' => 3],
                $second->id => ['rating' => 4],
            ]);
            $this->fail('Penyimpanan seharusnya gagal.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Gagal menyimpan jawaban kedua.', $exception->getMessage());
        }

        $this->assertSame(0, Response::where('survey_id', $survey->id)->count());
        $this->assertSame(0, Answer::whereIn('question_id', [$first->id, $second->id])->count());
    }

    public function test_failure_while_storing_answer_option_rolls_back_everything(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $question = $this->makeQuestion($survey, 'single_choice');
        $option = $this->makeOption($question);

        DB::listen(function ($query): void {
            if (str_contains($query->sql, 'insert into `answer_options`')) {
                throw new RuntimeException('Gagal menyimpan opsi jawaban.');
            }
        });

        try {
            $this->storeDirectly($student, $survey, [
                $question->id => ['option_id' => $option->id],
            ]);
            $this->fail('Penyimpanan seharusnya gagal.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Gagal menyimpan opsi jawaban.', $exception->getMessage());
        }

        $this->assertSame(0, Response::where('survey_id', $survey->id)->count());
        $this->assertSame(0, Answer::where('question_id', $question->id)->count());
        $this->assertSame(0, DB::table('answer_options')->where('question_id', $question->id)->count());
    }

    // ------------------------------------------------------------ duplicate

    public function test_user_who_already_responded_cannot_submit_again(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $question = $this->makeQuestion($survey, 'rating');
        $payload = ['answers' => [$question->id => ['rating' => 3]]];

        $this->postAnswers($student, $survey, $payload)->assertRedirect(route('responses.index'));

        $this->actingAs($student)
            ->post(route('responses.store', $survey), $payload)
            ->assertForbidden();

        $this->assertSame(1, Response::where('survey_id', $survey->id)->count());
        $this->assertSame(1, Answer::where('question_id', $question->id)->count());
    }

    public function test_unique_constraint_remains_the_last_protection(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();

        Response::create([
            'survey_id' => $survey->id,
            'user_id' => $student->id,
            'submitted_at' => now(),
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        Response::create([
            'survey_id' => $survey->id,
            'user_id' => $student->id,
            'submitted_at' => now(),
        ]);
    }

    public function test_duplicate_constraint_is_translated_to_friendly_message(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $question = $this->makeQuestion($survey, 'rating');

        Event::listen('eloquent.creating: '.Response::class, function (): void {
            throw new UniqueConstraintViolationException('mysql', 'insert into responses', [], new RuntimeException('duplicate'));
        });

        $response = $this->storeDirectly($student, $survey, [
            $question->id => ['rating' => 3],
        ]);

        $this->assertSame(route('responses.index'), $response->getTargetUrl());
        $this->assertSame('Anda sudah pernah mengisi survei ini.', session('error'));
        $this->assertSame(0, Response::where('survey_id', $survey->id)->count());
        $this->assertSame(0, Answer::where('question_id', $question->id)->count());
    }

    public function test_non_duplicate_database_error_is_not_masked(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $question = $this->makeQuestion($survey, 'rating');

        Event::listen('eloquent.creating: '.Response::class, function (): void {
            throw new QueryException('mysql', 'insert into responses', [], new RuntimeException('foreign key'));
        });

        try {
            $this->storeDirectly($student, $survey, [
                $question->id => ['rating' => 3],
            ]);
            $this->fail('Kesalahan database seharusnya diteruskan.');
        } catch (QueryException $exception) {
            $this->assertNotInstanceOf(DuplicateResponseException::class, $exception);
        }

        $this->assertSame(0, Response::where('survey_id', $survey->id)->count());
        $this->assertSame(0, Answer::where('question_id', $question->id)->count());
    }

    // ------------------------------------------------------------ authorization & changing conditions

    public function test_admin_cannot_submit(): void
    {
        $survey = $this->studentSurvey();
        $question = $this->makeQuestion($survey, 'rating');

        $this->postAnswers($this->admin(), $survey, ['answers' => [$question->id => ['rating' => 3]]])
            ->assertForbidden();

        $this->assertSame(0, Response::where('survey_id', $survey->id)->count());
    }

    public function test_inactive_user_cannot_submit(): void
    {
        $student = $this->studentUser(active: false);
        $survey = $this->studentSurvey();
        $question = $this->makeQuestion($survey, 'rating');

        $this->postAnswers($student, $survey, ['answers' => [$question->id => ['rating' => 3]]])
            ->assertForbidden();

        $this->assertSame(0, Response::where('survey_id', $survey->id)->count());
    }

    public function test_student_outside_target_cannot_submit(): void
    {
        $student = $this->studentUser();
        $survey = $this->makeSurvey(['student_target_mode' => 'classes']);
        $survey->targetClasses()->attach($this->makeClass()->id);
        $question = $this->makeQuestion($survey, 'rating');

        $this->postAnswers($student, $survey, ['answers' => [$question->id => ['rating' => 3]]])
            ->assertForbidden();

        $this->assertSame(0, Response::where('survey_id', $survey->id)->count());
    }

    public function test_teacher_outside_target_cannot_submit(): void
    {
        $teacher = $this->teacherUser();
        $other = $this->teacherUser();
        $survey = $this->makeSurvey(['teacher_target_mode' => 'selected']);
        $survey->targetTeachers()->attach($other->teacher->id);
        $question = $this->makeQuestion($survey, 'rating');

        $this->postAnswers($teacher, $survey, ['answers' => [$question->id => ['rating' => 3]]])
            ->assertForbidden();

        $this->assertSame(0, Response::where('survey_id', $survey->id)->count());
    }

    public function test_survey_outside_period_cannot_be_submitted(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey([
            'starts_at' => $this->base->copy()->subHours(2),
            'ends_at' => $this->base->copy()->subHour(),
        ]);
        $question = $this->makeQuestion($survey, 'rating');

        $this->postAnswers($student, $survey, ['answers' => [$question->id => ['rating' => 3]]])
            ->assertForbidden();

        $this->assertSame(0, Response::where('survey_id', $survey->id)->count());
    }

    public function test_closed_survey_cannot_be_submitted(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey(['status' => 'closed']);
        $question = $this->makeQuestion($survey, 'rating');

        $this->postAnswers($student, $survey, ['answers' => [$question->id => ['rating' => 3]]])
            ->assertForbidden();

        $this->assertSame(0, Response::where('survey_id', $survey->id)->count());
    }

    public function test_controller_rechecks_eligibility_after_form_request(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $question = $this->makeQuestion($survey, 'rating');

        $survey->update(['status' => 'closed']);

        try {
            $this->storeDirectly($student, $survey->fresh(), [
                $question->id => ['rating' => 3],
            ]);
            $this->fail('Submit seharusnya ditolak setelah kondisi berubah.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertSame(0, Response::where('survey_id', $survey->id)->count());
        $this->assertSame(0, Answer::where('question_id', $question->id)->count());
    }

    public function test_controller_rechecks_time_window_after_form_request(): void
    {
        $student = $this->studentUser();
        $survey = $this->studentSurvey();
        $question = $this->makeQuestion($survey, 'rating');

        $survey->update(['ends_at' => $this->base->copy()->subMinute()]);

        try {
            $this->storeDirectly($student, $survey->fresh(), [
                $question->id => ['rating' => 3],
            ]);
            $this->fail('Submit seharusnya ditolak setelah periode berakhir.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertSame(0, Response::where('survey_id', $survey->id)->count());
    }

    public function test_submit_requires_authentication(): void
    {
        $survey = $this->studentSurvey();

        $this->post(route('responses.store', $survey), ['answers' => []])
            ->assertRedirect(route('login'));
    }
}
