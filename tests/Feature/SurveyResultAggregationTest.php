<?php

namespace Tests\Feature;

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
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Test perhitungan agregat hasil survei per tipe pertanyaan.
 */
class SurveyResultAggregationTest extends TestCase
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

    private function studentUser(): User
    {
        $user = User::create([
            'username' => 'siswa_'.uniqid(),
            'password' => self::PASSWORD,
            'role' => 'student',
            'is_active' => true,
        ]);

        Student::create([
            'user_id' => $user->id,
            'nis' => 'NIS'.uniqid(),
            'name' => 'Siswa '.uniqid(),
            'class_id' => $this->makeClass()->id,
        ]);

        return $user;
    }

    private function teacherUser(): User
    {
        $user = User::create([
            'username' => 'guru_'.uniqid(),
            'password' => self::PASSWORD,
            'role' => 'teacher',
            'is_active' => true,
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

    private function makeSurvey(array $overrides = []): Survey
    {
        $creator = $overrides['creator_id'] ?? $this->admin()->id;
        unset($overrides['creator_id']);

        return Survey::create(array_merge([
            'creator_id' => $creator,
            'category_id' => $this->makeCategory()->id,
            'title' => 'Survei '.uniqid(),
            'student_target_mode' => 'all',
            'teacher_target_mode' => 'none',
            'status' => 'published',
            'starts_at' => $this->base->copy()->subHour(),
            'ends_at' => $this->base->copy()->addHour(),
            'is_result_published' => false,
        ], $overrides));
    }

    private function makeQuestion(Survey $survey, string $type, int $position = 1): Question
    {
        return Question::create([
            'survey_id' => $survey->id,
            'question_text' => 'Pertanyaan '.uniqid(),
            'type' => $type,
            'is_required' => true,
            'position' => $position,
        ]);
    }

    private function makeOption(Question $question, int $position): QuestionOption
    {
        return QuestionOption::create([
            'question_id' => $question->id,
            'option_text' => 'Opsi '.uniqid(),
            'position' => $position,
        ]);
    }

    private function makeResponse(Survey $survey, User $user): Response
    {
        return Response::create([
            'survey_id' => $survey->id,
            'user_id' => $user->id,
            'submitted_at' => now(),
        ]);
    }

    public function test_single_choice_counts_correctly(): void
    {
        $survey = $this->makeSurvey();
        $question = $this->makeQuestion($survey, 'single_choice');
        $option1 = $this->makeOption($question, 1);
        $option2 = $this->makeOption($question, 2);
        $option3 = $this->makeOption($question, 3);

        $user1 = $this->studentUser();
        $user2 = $this->studentUser();
        $user3 = $this->studentUser();

        foreach ([$user1, $user2] as $user) {
            $response = $this->makeResponse($survey, $user);
            $answer = Answer::create(['response_id' => $response->id, 'question_id' => $question->id]);
            $answer->selectedOptions()->attach($option1->id, ['question_id' => $question->id]);
        }

        $response3 = $this->makeResponse($survey, $user3);
        $answer3 = Answer::create(['response_id' => $response3->id, 'question_id' => $question->id]);
        $answer3->selectedOptions()->attach($option2->id, ['question_id' => $question->id]);

        $this->actingAs($this->admin())
            ->get(route('surveys.results.show', $survey))
            ->assertOk()
            ->assertSee('2 (66.7%)')
            ->assertSee('1 (33.3%)')
            ->assertSee('0 (0%)');
    }

    public function test_options_with_zero_count_are_shown(): void
    {
        $survey = $this->makeSurvey();
        $question = $this->makeQuestion($survey, 'single_choice');
        $option1 = $this->makeOption($question, 1);
        $this->makeOption($question, 2);

        $user = $this->studentUser();
        $response = $this->makeResponse($survey, $user);
        $answer = Answer::create(['response_id' => $response->id, 'question_id' => $question->id]);
        $answer->selectedOptions()->attach($option1->id, ['question_id' => $question->id]);

        $this->actingAs($this->admin())
            ->get(route('surveys.results.show', $survey))
            ->assertOk()
            ->assertSee('1 (100%)')
            ->assertSee('0 (0%)');
    }

    public function test_multiple_choice_counts_selections_correctly(): void
    {
        $survey = $this->makeSurvey();
        $question = $this->makeQuestion($survey, 'multiple_choice');
        $option1 = $this->makeOption($question, 1);
        $option2 = $this->makeOption($question, 2);
        $option3 = $this->makeOption($question, 3);

        $user1 = $this->studentUser();
        $response1 = $this->makeResponse($survey, $user1);
        $answer1 = Answer::create(['response_id' => $response1->id, 'question_id' => $question->id]);
        $answer1->selectedOptions()->attach([$option1->id, $option2->id], ['question_id' => $question->id]);

        $user2 = $this->studentUser();
        $response2 = $this->makeResponse($survey, $user2);
        $answer2 = Answer::create(['response_id' => $response2->id, 'question_id' => $question->id]);
        $answer2->selectedOptions()->attach([$option1->id], ['question_id' => $question->id]);

        $this->actingAs($this->admin())
            ->get(route('surveys.results.show', $survey))
            ->assertOk()
            ->assertSee('Responden dapat memilih lebih dari satu jawaban')
            ->assertSee('2 (100%)')
            ->assertSee('1 (50%)');
    }

    public function test_multiple_choice_percentage_can_exceed_100(): void
    {
        $survey = $this->makeSurvey();
        $question = $this->makeQuestion($survey, 'multiple_choice');
        $option1 = $this->makeOption($question, 1);
        $option2 = $this->makeOption($question, 2);

        foreach ([$this->studentUser(), $this->studentUser()] as $user) {
            $response = $this->makeResponse($survey, $user);
            $answer = Answer::create(['response_id' => $response->id, 'question_id' => $question->id]);
            $answer->selectedOptions()->attach([$option1->id, $option2->id], ['question_id' => $question->id]);
        }

        $response = $this->actingAs($this->admin())
            ->get(route('surveys.results.show', $survey));

        $response->assertOk();
        $response->assertSee('2 (100%)');
    }

    public function test_rating_calculates_average_and_distribution(): void
    {
        $survey = $this->makeSurvey();
        $question = $this->makeQuestion($survey, 'rating');

        foreach ([5, 4, 4, 3] as $rating) {
            $response = $this->makeResponse($survey, $this->studentUser());
            Answer::create([
                'response_id' => $response->id,
                'question_id' => $question->id,
                'rating_value' => $rating,
            ]);
        }

        $this->actingAs($this->admin())
            ->get(route('surveys.results.show', $survey))
            ->assertOk()
            ->assertSee('4')
            ->assertSee('dari 5')
            ->assertSee('5 ★')
            ->assertSee('4 ★')
            ->assertSee('3 ★')
            ->assertSee('2 ★')
            ->assertSee('1 ★');
    }

    public function test_rating_without_answers_handled(): void
    {
        $survey = $this->makeSurvey();
        $question = $this->makeQuestion($survey, 'rating');

        $this->makeResponse($survey, $this->studentUser());

        $this->actingAs($this->admin())
            ->get(route('surveys.results.show', $survey))
            ->assertOk()
            ->assertSee('Tidak ada jawaban untuk pertanyaan ini');
    }

    public function test_short_text_displays_anonymous_answers(): void
    {
        $survey = $this->makeSurvey();
        $question = $this->makeQuestion($survey, 'short_text');

        foreach (['Jawaban pertama', 'Jawaban kedua'] as $text) {
            $response = $this->makeResponse($survey, $this->studentUser());
            Answer::create([
                'response_id' => $response->id,
                'question_id' => $question->id,
                'answer_text' => $text,
            ]);
        }

        $this->actingAs($this->admin())
            ->get(route('surveys.results.show', $survey))
            ->assertOk()
            ->assertSee('Jawaban pertama')
            ->assertSee('Jawaban kedua');
    }

    public function test_long_text_displays_anonymous_answers(): void
    {
        $survey = $this->makeSurvey();
        $question = $this->makeQuestion($survey, 'long_text');

        $response = $this->makeResponse($survey, $this->studentUser());
        Answer::create([
            'response_id' => $response->id,
            'question_id' => $question->id,
            'answer_text' => 'Ini adalah jawaban panjang yang menjelaskan sesuatu dengan detail.',
        ]);

        $this->actingAs($this->admin())
            ->get(route('surveys.results.show', $survey))
            ->assertOk()
            ->assertSee('Ini adalah jawaban panjang yang menjelaskan sesuatu dengan detail.');
    }

    public function test_optional_question_without_answers_handled(): void
    {
        $survey = $this->makeSurvey();
        $question = $this->makeQuestion($survey, 'short_text');
        Question::where('id', $question->id)->update(['is_required' => false]);

        $user = $this->studentUser();
        $this->makeResponse($survey, $user);

        $this->actingAs($this->admin())
            ->get(route('surveys.results.show', $survey))
            ->assertOk()
            ->assertSee('Tidak ada jawaban untuk pertanyaan ini');
    }

    public function test_empty_text_answers_not_counted(): void
    {
        $survey = $this->makeSurvey();
        $question = $this->makeQuestion($survey, 'short_text');

        $user1 = $this->studentUser();
        $response1 = $this->makeResponse($survey, $user1);
        Answer::create([
            'response_id' => $response1->id,
            'question_id' => $question->id,
            'answer_text' => 'Jawaban valid',
        ]);

        $user2 = $this->studentUser();
        $response2 = $this->makeResponse($survey, $user2);
        Answer::create([
            'response_id' => $response2->id,
            'question_id' => $question->id,
            'answer_text' => '',
        ]);

        $response = $this->actingAs($this->admin())
            ->get(route('surveys.results.show', $survey))
            ->assertOk();

        $this->assertMatchesRegularExpression('/1\s+jawaban/', $response->getContent());
    }

    public function test_text_pagination_shows_max_10_per_page(): void
    {
        $teacher = $this->teacherUser();
        $survey = $this->makeSurvey(['creator_id' => $teacher->id]);
        $question = $this->makeQuestion($survey, 'short_text');

        for ($i = 1; $i <= 15; $i++) {
            $response = $this->makeResponse($survey, $this->studentUser());
            Answer::create([
                'response_id' => $response->id,
                'question_id' => $question->id,
                'answer_text' => "Jawaban nomor {$i}",
            ]);
        }

        $responsePage1 = $this->actingAs($teacher)
            ->get(route('surveys.results.show', $survey));

        $responsePage1->assertOk()
            ->assertSee('Jawaban nomor 1')
            ->assertSee('Jawaban nomor 10')
            ->assertDontSee('Jawaban nomor 11');

        $responsePage2 = $this->actingAs($teacher)
            ->get(route('surveys.results.show', $survey).'?q1_page=2');

        $responsePage2->assertOk()
            ->assertSee('Jawaban nomor 11')
            ->assertSee('Jawaban nomor 15')
            ->assertDontSee('Jawaban nomor 10');
    }

    public function test_text_answers_do_not_expose_identity(): void
    {
        $survey = $this->makeSurvey();
        $question = $this->makeQuestion($survey, 'short_text');

        $user = $this->studentUser();
        $response = $this->makeResponse($survey, $user);
        Answer::create([
            'response_id' => $response->id,
            'question_id' => $question->id,
            'answer_text' => 'Jawaban anonim',
        ]);

        $result = $this->actingAs($this->admin())
            ->get(route('surveys.results.show', $survey));

        $result->assertOk();
        $result->assertDontSee($user->username);
        $result->assertDontSee($user->student->name);
        $result->assertDontSee($user->student->nis);
    }

    public function test_result_page_does_not_show_submitted_at_timestamps(): void
    {
        $survey = $this->makeSurvey();
        $question = $this->makeQuestion($survey, 'rating');

        $user = $this->studentUser();
        $response = $this->makeResponse($survey, $user);
        Answer::create([
            'response_id' => $response->id,
            'question_id' => $question->id,
            'rating_value' => 5,
        ]);

        $result = $this->actingAs($this->admin())
            ->get(route('surveys.results.show', $survey));

        $result->assertOk();
        $result->assertDontSee($response->submitted_at->format('Y-m-d'));
        $result->assertDontSee($response->submitted_at->format('H:i'));
    }
}
