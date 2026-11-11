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
 * Akses hasil survei untuk responden.
 */
class SurveyResultRespondentAccessTest extends TestCase
{
    use DatabaseTransactions;

    private const PASSWORD = 'Password-Awal-123';

    private const TEXT_THRESHOLD = 5;

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

    private function makeClass(): SchoolClass
    {
        return SchoolClass::create([
            'name' => 'Kelas '.uniqid(),
            'academic_year' => '2026/2027',
            'is_active' => true,
        ]);
    }

    private function studentUser(bool $active = true): User
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
            'class_id' => $this->makeClass()->id,
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
            'name' => 'Kategori '.uniqid(),
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
            'is_result_published' => true,
        ], $overrides));
    }

    private function makeResponse(Survey $survey, User $user): Response
    {
        return Response::create([
            'survey_id' => $survey->id,
            'user_id' => $user->id,
            'submitted_at' => now(),
        ]);
    }

    // ------------------------------------------------------------ authorization

    public function test_student_who_submitted_can_view_results(): void
    {
        $student = $this->studentUser();
        $survey = $this->makeSurvey();
        $this->makeResponse($survey, $student);

        $this->actingAs($student)
            ->get(route('responses.results', $survey))
            ->assertOk()
            ->assertSee($survey->title);
    }

    public function test_teacher_who_submitted_can_view_results(): void
    {
        $teacher = $this->teacherUser();
        $survey = $this->makeSurvey(['teacher_target_mode' => 'all']);
        $this->makeResponse($survey, $teacher);

        $this->actingAs($teacher)
            ->get(route('responses.results', $survey))
            ->assertOk()
            ->assertSee($survey->title);
    }

    public function test_student_who_has_not_submitted_cannot_view(): void
    {
        $student = $this->studentUser();
        $survey = $this->makeSurvey();

        $this->actingAs($student)
            ->get(route('responses.results', $survey))
            ->assertForbidden();
    }

    public function test_teacher_who_has_not_submitted_cannot_view(): void
    {
        $teacher = $this->teacherUser();
        $survey = $this->makeSurvey(['teacher_target_mode' => 'all']);

        $this->actingAs($teacher)
            ->get(route('responses.results', $survey))
            ->assertForbidden();
    }

    public function test_inactive_user_cannot_view(): void
    {
        $student = $this->studentUser(active: false);
        $survey = $this->makeSurvey();
        $this->makeResponse($survey, $student);

        $this->actingAs($student)
            ->get(route('responses.results', $survey))
            ->assertForbidden();
    }

    public function test_student_without_profile_cannot_view(): void
    {
        $user = User::create([
            'username' => 'siswa_'.uniqid(),
            'password' => self::PASSWORD,
            'role' => 'student',
            'is_active' => true,
        ]);

        $survey = $this->makeSurvey();
        $this->makeResponse($survey, $user);

        $this->actingAs($user)
            ->get(route('responses.results', $survey))
            ->assertForbidden();
    }

    public function test_teacher_without_profile_cannot_view(): void
    {
        $user = User::create([
            'username' => 'guru_'.uniqid(),
            'password' => self::PASSWORD,
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $survey = $this->makeSurvey(['teacher_target_mode' => 'all']);
        $this->makeResponse($survey, $user);

        $this->actingAs($user)
            ->get(route('responses.results', $survey))
            ->assertForbidden();
    }

    public function test_admin_cannot_use_respondent_route(): void
    {
        $admin = $this->admin();
        $survey = $this->makeSurvey(['creator_id' => $admin->id]);

        $this->actingAs($admin)
            ->get(route('responses.results', $survey))
            ->assertForbidden();
    }

    public function test_guest_redirected_to_login(): void
    {
        $survey = $this->makeSurvey();

        $this->get(route('responses.results', $survey))
            ->assertRedirect(route('login'));
    }

    public function test_unpublished_results_rejected(): void
    {
        $student = $this->studentUser();
        $survey = $this->makeSurvey(['is_result_published' => false]);
        $this->makeResponse($survey, $student);

        $this->actingAs($student)
            ->get(route('responses.results', $survey))
            ->assertForbidden();
    }

    public function test_draft_survey_rejected(): void
    {
        $student = $this->studentUser();
        $survey = $this->makeSurvey(['status' => 'draft']);
        $this->makeResponse($survey, $student);

        $this->actingAs($student)
            ->get(route('responses.results', $survey))
            ->assertForbidden();
    }

    public function test_published_survey_allowed(): void
    {
        $student = $this->studentUser();
        $survey = $this->makeSurvey(['status' => 'published']);
        $this->makeResponse($survey, $student);

        $this->actingAs($student)
            ->get(route('responses.results', $survey))
            ->assertOk();
    }

    public function test_closed_survey_allowed(): void
    {
        $student = $this->studentUser();
        $survey = $this->makeSurvey(['status' => 'closed']);
        $this->makeResponse($survey, $student);

        $this->actingAs($student)
            ->get(route('responses.results', $survey))
            ->assertOk();
    }

    // ------------------------------------------------------------ privacy threshold

    public function test_text_answers_hidden_below_threshold(): void
    {
        $survey = $this->makeSurvey();
        $question = Question::create([
            'survey_id' => $survey->id,
            'question_text' => 'Pertanyaan teks',
            'type' => 'short_text',
            'is_required' => false,
            'position' => 1,
        ]);

        $students = [];
        for ($i = 0; $i < self::TEXT_THRESHOLD - 1; $i++) {
            $student = $this->studentUser();
            $students[] = $student;
            $response = $this->makeResponse($survey, $student);
            Answer::create([
                'response_id' => $response->id,
                'question_id' => $question->id,
                'answer_text' => 'Jawaban '.$i,
            ]);
        }

        $this->actingAs($students[0])
            ->get(route('responses.results', $survey))
            ->assertOk()
            ->assertSee('minimal 5 respons')
            ->assertDontSee('Jawaban 0')
            ->assertDontSee('Jawaban 1');
    }

    public function test_text_answers_shown_at_threshold(): void
    {
        $survey = $this->makeSurvey();
        $question = Question::create([
            'survey_id' => $survey->id,
            'question_text' => 'Pertanyaan teks',
            'type' => 'short_text',
            'is_required' => false,
            'position' => 1,
        ]);

        $students = [];
        for ($i = 0; $i < self::TEXT_THRESHOLD; $i++) {
            $student = $this->studentUser();
            $students[] = $student;
            $response = $this->makeResponse($survey, $student);
            Answer::create([
                'response_id' => $response->id,
                'question_id' => $question->id,
                'answer_text' => 'Jawaban '.$i,
            ]);
        }

        $this->actingAs($students[0])
            ->get(route('responses.results', $survey))
            ->assertOk()
            ->assertSee('Jawaban 0')
            ->assertDontSee('minimal 5 respons');
    }

    public function test_threshold_applies_to_long_text_too(): void
    {
        $survey = $this->makeSurvey();
        $question = Question::create([
            'survey_id' => $survey->id,
            'question_text' => 'Pertanyaan panjang',
            'type' => 'long_text',
            'is_required' => false,
            'position' => 1,
        ]);

        $students = [];
        for ($i = 0; $i < self::TEXT_THRESHOLD - 1; $i++) {
            $student = $this->studentUser();
            $students[] = $student;
            $response = $this->makeResponse($survey, $student);
            Answer::create([
                'response_id' => $response->id,
                'question_id' => $question->id,
                'answer_text' => 'Jawaban panjang '.$i,
            ]);
        }

        $this->actingAs($students[0])
            ->get(route('responses.results', $survey))
            ->assertOk()
            ->assertSee('minimal 5 respons')
            ->assertDontSee('Jawaban panjang 0');
    }

    public function test_threshold_based_on_total_survey_responses(): void
    {
        $survey = $this->makeSurvey();

        $ratingQ = Question::create([
            'survey_id' => $survey->id,
            'question_text' => 'Rating',
            'type' => 'rating',
            'is_required' => true,
            'position' => 1,
        ]);

        $textQ = Question::create([
            'survey_id' => $survey->id,
            'question_text' => 'Teks',
            'type' => 'short_text',
            'is_required' => false,
            'position' => 2,
        ]);

        $students = [];
        for ($i = 0; $i < self::TEXT_THRESHOLD; $i++) {
            $student = $this->studentUser();
            $students[] = $student;
            $response = $this->makeResponse($survey, $student);

            Answer::create([
                'response_id' => $response->id,
                'question_id' => $ratingQ->id,
                'rating_value' => 3,
            ]);

            if ($i < 2) {
                Answer::create([
                    'response_id' => $response->id,
                    'question_id' => $textQ->id,
                    'answer_text' => 'Teks '.$i,
                ]);
            }
        }

        $this->actingAs($students[0])
            ->get(route('responses.results', $survey))
            ->assertOk()
            ->assertSee('Teks 0');
    }

    public function test_choice_and_rating_visible_below_threshold(): void
    {
        $survey = $this->makeSurvey();

        $choice = Question::create([
            'survey_id' => $survey->id,
            'question_text' => 'Pilihan',
            'type' => 'single_choice',
            'is_required' => true,
            'position' => 1,
        ]);
        $option = QuestionOption::create([
            'question_id' => $choice->id,
            'option_text' => 'Opsi A',
            'position' => 1,
        ]);

        $rating = Question::create([
            'survey_id' => $survey->id,
            'question_text' => 'Rating',
            'type' => 'rating',
            'is_required' => true,
            'position' => 2,
        ]);

        $students = [];
        for ($i = 0; $i < self::TEXT_THRESHOLD - 1; $i++) {
            $student = $this->studentUser();
            $students[] = $student;
            $response = $this->makeResponse($survey, $student);

            $choiceAnswer = Answer::create([
                'response_id' => $response->id,
                'question_id' => $choice->id,
            ]);
            $choiceAnswer->selectedOptions()->attach($option->id, ['question_id' => $choice->id]);

            Answer::create([
                'response_id' => $response->id,
                'question_id' => $rating->id,
                'rating_value' => 4,
            ]);
        }

        $response = $this->actingAs($students[0])->get(route('responses.results', $survey));

        $response->assertOk()
            ->assertSee('Opsi A')
            ->assertSee('dari 5');
    }

    public function test_text_answers_anonymous(): void
    {
        $survey = $this->makeSurvey();
        $question = Question::create([
            'survey_id' => $survey->id,
            'question_text' => 'Teks',
            'type' => 'short_text',
            'is_required' => false,
            'position' => 1,
        ]);

        $students = [];
        for ($i = 0; $i < self::TEXT_THRESHOLD; $i++) {
            $student = $this->studentUser();
            $students[] = $student;
            $response = $this->makeResponse($survey, $student);
            Answer::create([
                'response_id' => $response->id,
                'question_id' => $question->id,
                'answer_text' => 'Jawaban '.$i,
            ]);
        }

        $html = $this->actingAs($students[0])
            ->get(route('responses.results', $survey))
            ->getContent();

        $this->assertStringContainsString('Jawaban 0', $html);
        $this->assertStringNotContainsString('response_id', $html);
        $this->assertStringNotContainsString('answer_id', $html);
        $this->assertStringNotContainsString('submitted_at', $html);

        foreach ($students as $student) {
            $this->assertStringNotContainsString($student->username, $html);
            $this->assertStringNotContainsString($student->student->nis, $html);
        }
    }

    // ------------------------------------------------------------ respondent view context

    public function test_respondent_view_hides_creator_info(): void
    {
        $student = $this->studentUser();
        $creator = $this->admin();
        $survey = $this->makeSurvey(['creator_id' => $creator->id]);
        $this->makeResponse($survey, $student);

        $this->actingAs($student)
            ->get(route('responses.results', $survey))
            ->assertOk()
            ->assertDontSee($creator->username);
    }

    public function test_respondent_view_hides_publication_toggle(): void
    {
        $student = $this->studentUser();
        $survey = $this->makeSurvey();
        $this->makeResponse($survey, $student);

        $this->actingAs($student)
            ->get(route('responses.results', $survey))
            ->assertOk()
            ->assertDontSee('Publikasikan Hasil')
            ->assertDontSee('Sembunyikan Hasil')
            ->assertDontSee(route('surveys.results.publication.update', $survey), false);
    }

    public function test_respondent_view_back_link_to_responses_index(): void
    {
        $student = $this->studentUser();
        $survey = $this->makeSurvey();
        $this->makeResponse($survey, $student);

        $this->actingAs($student)
            ->get(route('responses.results', $survey))
            ->assertOk()
            ->assertSee(route('responses.index'), false);
    }

    // ------------------------------------------------------------ regression

    public function test_admin_still_sees_full_results_via_creator_route(): void
    {
        $admin = $this->admin();
        $survey = $this->makeSurvey(['creator_id' => $admin->id]);

        $question = Question::create([
            'survey_id' => $survey->id,
            'question_text' => 'Teks',
            'type' => 'short_text',
            'is_required' => false,
            'position' => 1,
        ]);

        for ($i = 0; $i < 2; $i++) {
            $student = $this->studentUser();
            $response = $this->makeResponse($survey, $student);
            Answer::create([
                'response_id' => $response->id,
                'question_id' => $question->id,
                'answer_text' => 'Admin sees this '.$i,
            ]);
        }

        $this->actingAs($admin)
            ->get(route('surveys.results.show', $survey))
            ->assertOk()
            ->assertSee('Admin sees this 0')
            ->assertDontSee('minimal 5 respons');
    }

    public function test_teacher_creator_still_sees_full_results_via_creator_route(): void
    {
        $teacher = $this->teacherUser();
        $survey = $this->makeSurvey(['creator_id' => $teacher->id]);

        $question = Question::create([
            'survey_id' => $survey->id,
            'question_text' => 'Teks',
            'type' => 'short_text',
            'is_required' => false,
            'position' => 1,
        ]);

        for ($i = 0; $i < 2; $i++) {
            $student = $this->studentUser();
            $response = $this->makeResponse($survey, $student);
            Answer::create([
                'response_id' => $response->id,
                'question_id' => $question->id,
                'answer_text' => 'Creator sees this '.$i,
            ]);
        }

        $this->actingAs($teacher)
            ->get(route('surveys.results.show', $survey))
            ->assertOk()
            ->assertSee('Creator sees this 0')
            ->assertDontSee('minimal 5 respons');
    }

    public function test_publication_toggle_still_works(): void
    {
        $admin = $this->admin();
        $survey = $this->makeSurvey(['creator_id' => $admin->id, 'is_result_published' => false]);

        $this->actingAs($admin)
            ->patch(route('surveys.results.publication.update', $survey), [
                'is_result_published' => true,
            ])
            ->assertRedirect(route('surveys.results.show', $survey));

        $this->assertTrue($survey->fresh()->is_result_published);
    }
}
