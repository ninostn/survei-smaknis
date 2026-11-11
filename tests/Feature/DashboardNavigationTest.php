<?php

namespace Tests\Feature;

use App\Models\Question;
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

/**
 * Integrasi dashboard, navigasi, dan alur responden setelah submit.
 */
class DashboardNavigationTest extends TestCase
{
    use DatabaseTransactions;

    private const PASSWORD = 'Password-Awal-123';

    private const ACTIVE_NAV = 'bg-white text-ink shadow-sm ring-1 ring-slate-200/70';

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

    private function assertActiveNav(string $url, string $content): void
    {
        $this->assertMatchesRegularExpression(
            '/href="'.preg_quote($url, '/').'"\s+class="[^"]*'.preg_quote(self::ACTIVE_NAV, '/').'/',
            $content,
        );
        $this->assertSame(1, substr_count($content, self::ACTIVE_NAV));
    }

    public function test_every_navigation_route_exists(): void
    {
        $routes = [
            'admin.dashboard',
            'admin.users.index',
            'admin.classes.index',
            'admin.subjects.index',
            'admin.teaching-assignments.index',
            'admin.survey-categories.index',
            'surveys.index',
            'surveys.create',
            'responses.index',
            'student.dashboard',
            'teacher.dashboard',
        ];

        foreach ($routes as $name) {
            $this->assertTrue(Route::has($name), 'Route navigasi tidak tersedia: '.$name);
        }
    }

    public function test_admin_dashboard_shows_admin_navigation_only(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee(route('admin.users.index'), false)
            ->assertSee(route('admin.classes.index'), false)
            ->assertSee(route('admin.subjects.index'), false)
            ->assertSee(route('admin.teaching-assignments.index'), false)
            ->assertSee(route('surveys.index'), false)
            ->assertSee(route('admin.survey-categories.index'), false)
            ->assertDontSee('Isi Survei')
            ->assertDontSee(route('responses.index'), false);

        $this->assertActiveNav(route('admin.dashboard'), $response->getContent());
    }

    public function test_teacher_dashboard_links_to_management_and_respondent_pages(): void
    {
        $response = $this->actingAs($this->teacherUser())->get(route('teacher.dashboard'));

        $response->assertOk()
            ->assertSee('Kelola Survei')
            ->assertSee('Isi Survei')
            ->assertSee(route('surveys.index'), false)
            ->assertSee(route('responses.index'), false)
            ->assertDontSee(route('admin.users.index'), false);
    }

    public function test_student_dashboard_links_to_respondent_page_only(): void
    {
        $response = $this->actingAs($this->studentUser())->get(route('student.dashboard'));

        $response->assertOk()
            ->assertSee('Isi Survei')
            ->assertSee(route('responses.index'), false)
            ->assertDontSee(route('surveys.index'), false)
            ->assertDontSee(route('admin.users.index'), false)
            ->assertDontSee(route('admin.survey-categories.index'), false);
    }

    public function test_teacher_navigation_marks_survey_page_active(): void
    {
        $response = $this->actingAs($this->teacherUser())->get(route('surveys.index'));

        $response->assertOk()->assertSee('Isi Survei');
        $this->assertActiveNav(route('surveys.index'), $response->getContent());
    }

    public function test_student_navigation_marks_respondent_page_active(): void
    {
        $response = $this->actingAs($this->studentUser())->get(route('responses.index'));

        $response->assertOk();
        $this->assertActiveNav(route('responses.index'), $response->getContent());
    }

    public function test_roles_cannot_open_each_others_pages(): void
    {
        $this->actingAs($this->studentUser())
            ->get(route('surveys.index'))
            ->assertForbidden();

        $this->actingAs($this->studentUser())
            ->get(route('admin.users.index'))
            ->assertForbidden();

        $this->actingAs($this->teacherUser())
            ->get(route('admin.users.index'))
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->get(route('responses.index'))
            ->assertForbidden();
    }

    public function test_success_flash_appears_once_after_submission(): void
    {
        $student = $this->studentUser();
        $survey = $this->makeSurvey(['student_target_mode' => 'all']);
        $question = Question::create([
            'survey_id' => $survey->id,
            'question_text' => 'Pertanyaan uji',
            'type' => 'rating',
            'is_required' => true,
            'position' => 1,
        ]);

        $this->actingAs($student)
            ->post(route('responses.store', $survey), [
                'answers' => [$question->id => ['rating' => 3]],
            ])
            ->assertRedirect(route('responses.index'));

        $this->get(route('responses.index'))
            ->assertOk()
            ->assertSee('Jawaban berhasil dikirim.');

        $this->get(route('responses.index'))
            ->assertOk()
            ->assertDontSee('Jawaban berhasil dikirim.');
    }

    public function test_submitted_survey_disappears_from_list(): void
    {
        $student = $this->studentUser();
        $survey = $this->makeSurvey([
            'title' => 'Survei Untuk Diisi',
            'student_target_mode' => 'all',
        ]);
        $question = Question::create([
            'survey_id' => $survey->id,
            'question_text' => 'Pertanyaan uji',
            'type' => 'rating',
            'is_required' => true,
            'position' => 1,
        ]);

        $this->actingAs($student)
            ->get(route('responses.index'))
            ->assertOk()
            ->assertSee('Survei Untuk Diisi');

        $this->actingAs($student)
            ->post(route('responses.store', $survey), [
                'answers' => [$question->id => ['rating' => 3]],
            ]);

        $this->actingAs($student)
            ->get(route('responses.index'))
            ->assertOk()
            ->assertDontSee('Survei Untuk Diisi');
    }
}
