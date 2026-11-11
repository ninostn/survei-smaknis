<?php

namespace Tests\Feature;

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
use Tests\TestCase;

/**
 * Test hak akses halaman hasil survei untuk admin dan creator.
 */
class SurveyResultAccessTest extends TestCase
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
            'student_target_mode' => 'none',
            'teacher_target_mode' => 'none',
            'status' => 'published',
            'starts_at' => $this->base->copy()->subHour(),
            'ends_at' => $this->base->copy()->addHour(),
            'is_result_published' => false,
        ], $overrides));
    }

    private function makeQuestion(Survey $survey, string $type): Question
    {
        return Question::create([
            'survey_id' => $survey->id,
            'question_text' => 'Pertanyaan '.uniqid(),
            'type' => $type,
            'is_required' => true,
            'position' => 1,
        ]);
    }

    private function makeOption(Question $question): QuestionOption
    {
        return QuestionOption::create([
            'question_id' => $question->id,
            'option_text' => 'Opsi '.uniqid(),
            'position' => 1,
        ]);
    }

    public function test_admin_can_view_any_survey_results(): void
    {
        $teacher = $this->teacherUser();
        $survey = $this->makeSurvey(['creator_id' => $teacher->id]);

        $this->actingAs($this->admin())
            ->get(route('surveys.results.show', $survey))
            ->assertOk()
            ->assertSee($survey->title);
    }

    public function test_teacher_can_view_own_survey_results(): void
    {
        $teacher = $this->teacherUser();
        $survey = $this->makeSurvey(['creator_id' => $teacher->id]);

        $this->actingAs($teacher)
            ->get(route('surveys.results.show', $survey))
            ->assertOk()
            ->assertSee($survey->title);
    }

    public function test_teacher_cannot_view_other_teacher_survey_results(): void
    {
        $teacher1 = $this->teacherUser();
        $teacher2 = $this->teacherUser();
        $survey = $this->makeSurvey(['creator_id' => $teacher1->id]);

        $this->actingAs($teacher2)
            ->get(route('surveys.results.show', $survey))
            ->assertForbidden();
    }

    public function test_student_cannot_access_creator_result_route(): void
    {
        $survey = $this->makeSurvey();

        $this->actingAs($this->studentUser())
            ->get(route('surveys.results.show', $survey))
            ->assertForbidden();
    }

    public function test_guest_redirected_to_login(): void
    {
        $survey = $this->makeSurvey();

        $this->get(route('surveys.results.show', $survey))
            ->assertRedirect(route('login'));
    }

    public function test_survey_without_responses_shows_empty_state(): void
    {
        $teacher = $this->teacherUser();
        $survey = $this->makeSurvey(['creator_id' => $teacher->id]);

        $this->actingAs($teacher)
            ->get(route('surveys.results.show', $survey))
            ->assertOk()
            ->assertSee('Belum ada respons untuk survei ini.');
    }

    public function test_admin_can_view_draft_survey_results(): void
    {
        $survey = $this->makeSurvey(['status' => 'draft']);

        $this->actingAs($this->admin())
            ->get(route('surveys.results.show', $survey))
            ->assertOk();
    }

    public function test_admin_can_view_closed_survey_results(): void
    {
        $survey = $this->makeSurvey(['status' => 'closed']);

        $this->actingAs($this->admin())
            ->get(route('surveys.results.show', $survey))
            ->assertOk();
    }

    public function test_teacher_can_view_own_draft_survey_results(): void
    {
        $teacher = $this->teacherUser();
        $survey = $this->makeSurvey(['creator_id' => $teacher->id, 'status' => 'draft']);

        $this->actingAs($teacher)
            ->get(route('surveys.results.show', $survey))
            ->assertOk();
    }
}
