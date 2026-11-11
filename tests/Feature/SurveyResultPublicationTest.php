<?php

namespace Tests\Feature;

use App\Models\Question;
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
 * Test toggle publikasi hasil survei.
 */
class SurveyResultPublicationTest extends TestCase
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

    private function makeQuestion(Survey $survey): Question
    {
        return Question::create([
            'survey_id' => $survey->id,
            'question_text' => 'Pertanyaan '.uniqid(),
            'type' => 'rating',
            'is_required' => true,
            'position' => 1,
        ]);
    }

    public function test_admin_can_publish_results(): void
    {
        $survey = $this->makeSurvey();
        $this->assertFalse($survey->is_result_published);

        $this->actingAs($this->admin())
            ->patch(route('surveys.results.publication.update', $survey), [
                'is_result_published' => true,
            ])
            ->assertRedirect(route('surveys.results.show', $survey))
            ->assertSessionHas('status', 'Hasil survei berhasil dipublikasikan.');

        $this->assertTrue($survey->fresh()->is_result_published);
    }

    public function test_admin_can_unpublish_results(): void
    {
        $survey = $this->makeSurvey(['is_result_published' => true]);
        $this->assertTrue($survey->is_result_published);

        $this->actingAs($this->admin())
            ->patch(route('surveys.results.publication.update', $survey), [
                'is_result_published' => false,
            ])
            ->assertRedirect(route('surveys.results.show', $survey))
            ->assertSessionHas('status', 'Hasil survei berhasil disembunyikan.');

        $this->assertFalse($survey->fresh()->is_result_published);
    }

    public function test_teacher_can_publish_own_survey_results(): void
    {
        $teacher = $this->teacherUser();
        $survey = $this->makeSurvey(['creator_id' => $teacher->id]);

        $this->actingAs($teacher)
            ->patch(route('surveys.results.publication.update', $survey), [
                'is_result_published' => true,
            ])
            ->assertRedirect(route('surveys.results.show', $survey));

        $this->assertTrue($survey->fresh()->is_result_published);
    }

    public function test_teacher_can_unpublish_own_survey_results(): void
    {
        $teacher = $this->teacherUser();
        $survey = $this->makeSurvey(['creator_id' => $teacher->id, 'is_result_published' => true]);

        $this->actingAs($teacher)
            ->patch(route('surveys.results.publication.update', $survey), [
                'is_result_published' => false,
            ])
            ->assertRedirect(route('surveys.results.show', $survey));

        $this->assertFalse($survey->fresh()->is_result_published);
    }

    public function test_teacher_cannot_change_other_teacher_publication(): void
    {
        $teacher1 = $this->teacherUser();
        $teacher2 = $this->teacherUser();
        $survey = $this->makeSurvey(['creator_id' => $teacher1->id]);

        $this->actingAs($teacher2)
            ->patch(route('surveys.results.publication.update', $survey), [
                'is_result_published' => true,
            ])
            ->assertForbidden();

        $this->assertFalse($survey->fresh()->is_result_published);
    }

    public function test_student_cannot_change_publication(): void
    {
        $survey = $this->makeSurvey();

        $this->actingAs($this->studentUser())
            ->patch(route('surveys.results.publication.update', $survey), [
                'is_result_published' => true,
            ])
            ->assertForbidden();

        $this->assertFalse($survey->fresh()->is_result_published);
    }

    public function test_guest_redirected_to_login(): void
    {
        $survey = $this->makeSurvey();

        $this->patch(route('surveys.results.publication.update', $survey), [
            'is_result_published' => true,
        ])
            ->assertRedirect(route('login'));
    }

    public function test_invalid_input_rejected(): void
    {
        $survey = $this->makeSurvey();

        $this->actingAs($this->admin())
            ->patchJson(route('surveys.results.publication.update', $survey), [
                'is_result_published' => 'invalid',
            ])
            ->assertStatus(422);

        $this->assertFalse($survey->fresh()->is_result_published);
    }

    public function test_missing_input_rejected(): void
    {
        $survey = $this->makeSurvey();

        $this->actingAs($this->admin())
            ->patchJson(route('surveys.results.publication.update', $survey), [])
            ->assertStatus(422);

        $this->assertFalse($survey->fresh()->is_result_published);
    }

    public function test_publication_does_not_change_survey_status(): void
    {
        $survey = $this->makeSurvey(['status' => 'draft']);

        $this->actingAs($this->admin())
            ->patch(route('surveys.results.publication.update', $survey), [
                'is_result_published' => true,
            ]);

        $fresh = $survey->fresh();
        $this->assertTrue($fresh->is_result_published);
        $this->assertSame('draft', $fresh->status);
    }

    public function test_publication_does_not_change_responses(): void
    {
        $survey = $this->makeSurvey();
        $question = $this->makeQuestion($survey);
        $user = $this->studentUser();

        Response::create([
            'survey_id' => $survey->id,
            'user_id' => $user->id,
            'submitted_at' => now(),
        ]);

        $countBefore = $survey->responses()->count();

        $this->actingAs($this->admin())
            ->patch(route('surveys.results.publication.update', $survey), [
                'is_result_published' => true,
            ]);

        $this->assertSame($countBefore, $survey->fresh()->responses()->count());
    }

    public function test_draft_survey_can_have_published_results(): void
    {
        $teacher = $this->teacherUser();
        $survey = $this->makeSurvey(['creator_id' => $teacher->id, 'status' => 'draft']);

        $this->actingAs($teacher)
            ->patch(route('surveys.results.publication.update', $survey), [
                'is_result_published' => true,
            ])
            ->assertRedirect();

        $this->assertTrue($survey->fresh()->is_result_published);
    }

    public function test_closed_survey_can_change_publication(): void
    {
        $survey = $this->makeSurvey(['status' => 'closed', 'is_result_published' => false]);

        $this->actingAs($this->admin())
            ->patch(route('surveys.results.publication.update', $survey), [
                'is_result_published' => true,
            ])
            ->assertRedirect();

        $this->assertTrue($survey->fresh()->is_result_published);
    }

    public function test_result_page_shows_publication_status(): void
    {
        $teacher = $this->teacherUser();
        $survey = $this->makeSurvey(['creator_id' => $teacher->id, 'is_result_published' => false]);

        $response = $this->actingAs($teacher)
            ->get(route('surveys.results.show', $survey));

        $response->assertOk()
            ->assertSee('Hasil disembunyikan')
            ->assertSee('Publikasikan Hasil');
    }

    public function test_result_page_shows_published_status(): void
    {
        $teacher = $this->teacherUser();
        $survey = $this->makeSurvey(['creator_id' => $teacher->id, 'is_result_published' => true]);

        $response = $this->actingAs($teacher)
            ->get(route('surveys.results.show', $survey));

        $response->assertOk()
            ->assertSee('Hasil dipublikasikan')
            ->assertSee('Sembunyikan Hasil');
    }

    public function test_default_result_is_not_published(): void
    {
        $survey = $this->makeSurvey([
            'status' => 'draft',
        ]);

        $this->assertFalse($survey->is_result_published);
    }
}
