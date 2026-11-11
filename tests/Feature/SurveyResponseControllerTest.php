<?php

namespace Tests\Feature;

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
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SurveyResponseControllerTest extends TestCase
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

    private function studentUser(?SchoolClass $class = null): User
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
            'class_id' => ($class ?? $this->makeClass())->id,
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

    private function respond(Survey $survey, User $user): Response
    {
        return Response::create([
            'survey_id' => $survey->id,
            'user_id' => $user->id,
            'submitted_at' => now(),
        ]);
    }

    // ---------------------------------------------------------------- guest

    public function test_guest_is_redirected_from_index(): void
    {
        $this->get(route('responses.index'))->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_show(): void
    {
        $survey = $this->makeSurvey(['student_target_mode' => 'all']);

        $this->get(route('responses.show', $survey))->assertRedirect(route('login'));
    }

    // ---------------------------------------------------------------- admin

    public function test_admin_cannot_open_index(): void
    {
        $this->actingAs($this->admin())
            ->get(route('responses.index'))
            ->assertForbidden();
    }

    public function test_admin_cannot_open_show(): void
    {
        $survey = $this->makeSurvey([
            'student_target_mode' => 'all',
            'teacher_target_mode' => 'all',
        ]);

        $this->actingAs($this->admin())
            ->get(route('responses.show', $survey))
            ->assertForbidden();
    }

    // ---------------------------------------------------------------- access

    public function test_student_can_open_index(): void
    {
        $this->actingAs($this->studentUser())
            ->get(route('responses.index'))
            ->assertOk();
    }

    public function test_teacher_can_open_index(): void
    {
        $this->actingAs($this->teacherUser())
            ->get(route('responses.index'))
            ->assertOk();
    }

    // ---------------------------------------------------------------- dashboards

    public function test_student_dashboard_links_to_isi_survei(): void
    {
        $student = $this->studentUser();

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Isi Survei')
            ->assertSee(route('responses.index'));
    }

    public function test_teacher_dashboard_links_to_isi_survei(): void
    {
        $teacher = $this->teacherUser();

        $this->actingAs($teacher)
            ->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSee('Isi Survei')
            ->assertSee(route('responses.index'));
    }

    public function test_admin_dashboard_has_no_isi_survei_link(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee(route('responses.index'));
    }

    // ---------------------------------------------------------------- index

    public function test_index_only_lists_eligible_surveys(): void
    {
        $student = $this->studentUser();
        $eligible = $this->makeSurvey(['title' => 'Survei Layak', 'student_target_mode' => 'all']);
        $notTargeted = $this->makeSurvey(['title' => 'Survei Bukan Target']);

        $response = $this->actingAs($student)->get(route('responses.index'));

        $response->assertOk()
            ->assertSee($eligible->title)
            ->assertDontSee($notTargeted->title);
    }

    public function test_index_hides_surveys_already_answered(): void
    {
        $student = $this->studentUser();
        $answered = $this->makeSurvey(['title' => 'Survei Sudah Diisi', 'student_target_mode' => 'all']);
        $this->respond($answered, $student);

        $this->actingAs($student)
            ->get(route('responses.index'))
            ->assertOk()
            ->assertDontSee($answered->title);
    }

    public function test_index_shows_only_targeted_class_for_student(): void
    {
        $class = $this->makeClass();
        $student = $this->studentUser($class);

        $forClass = $this->makeSurvey(['title' => 'Survei Kelas Saya', 'student_target_mode' => 'classes']);
        $forClass->targetClasses()->attach($class->id);

        $otherClass = $this->makeSurvey(['title' => 'Survei Kelas Lain', 'student_target_mode' => 'classes']);
        $otherClass->targetClasses()->attach($this->makeClass()->id);

        $this->actingAs($student)
            ->get(route('responses.index'))
            ->assertOk()
            ->assertSee($forClass->title)
            ->assertDontSee($otherClass->title);
    }

    public function test_index_shows_only_selected_target_for_teacher(): void
    {
        $teacher = $this->teacherUser();
        $other = $this->teacherUser();

        $selected = $this->makeSurvey(['title' => 'Survei Guru Dipilih', 'teacher_target_mode' => 'selected']);
        $selected->targetTeachers()->attach($teacher->teacher->id);

        $otherSelected = $this->makeSurvey(['title' => 'Survei Guru Lain', 'teacher_target_mode' => 'selected']);
        $otherSelected->targetTeachers()->attach($other->teacher->id);

        $this->actingAs($teacher)
            ->get(route('responses.index'))
            ->assertOk()
            ->assertSee($selected->title)
            ->assertDontSee($otherSelected->title);
    }

    public function test_index_hides_draft_and_closed_surveys(): void
    {
        $student = $this->studentUser();
        $draft = $this->makeSurvey(['title' => 'Survei Draft', 'student_target_mode' => 'all', 'status' => 'draft']);
        $closed = $this->makeSurvey(['title' => 'Survei Ditutup', 'student_target_mode' => 'all', 'status' => 'closed']);

        $this->actingAs($student)
            ->get(route('responses.index'))
            ->assertOk()
            ->assertDontSee($draft->title)
            ->assertDontSee($closed->title);
    }

    public function test_index_hides_surveys_outside_period(): void
    {
        $student = $this->studentUser();
        $notStarted = $this->makeSurvey([
            'title' => 'Survei Belum Mulai',
            'student_target_mode' => 'all',
            'starts_at' => $this->base->copy()->addHour(),
            'ends_at' => $this->base->copy()->addHours(2),
        ]);
        $ended = $this->makeSurvey([
            'title' => 'Survei Sudah Lewat',
            'student_target_mode' => 'all',
            'starts_at' => $this->base->copy()->subHours(2),
            'ends_at' => $this->base->copy()->subHour(),
        ]);

        $this->actingAs($student)
            ->get(route('responses.index'))
            ->assertOk()
            ->assertDontSee($notStarted->title)
            ->assertDontSee($ended->title);
    }

    public function test_index_paginates_ten_per_page(): void
    {
        $student = $this->studentUser();

        $titles = [];

        foreach (range(1, 11) as $index) {
            $titles[$index] = 'Survei Halaman '.$index.' Uji';
            $this->makeSurvey([
                'title' => $titles[$index],
                'student_target_mode' => 'all',
                'ends_at' => $this->base->copy()->addHours($index),
            ]);
        }

        $first = $this->actingAs($student)->get(route('responses.index'));
        $first->assertOk();

        foreach (range(1, 10) as $index) {
            $first->assertSee($titles[$index]);
        }
        $first->assertDontSee($titles[11]);

        $second = $this->actingAs($student)->get(route('responses.index', ['page' => 2]));
        $second->assertOk()->assertSee($titles[11]);
        $second->assertDontSee($titles[1]);
    }

    public function test_index_shows_empty_state(): void
    {
        $this->actingAs($this->studentUser())
            ->get(route('responses.index'))
            ->assertOk()
            ->assertSee('Survei Tersedia')
            ->assertSee('Belum ada survei yang bisa diisi.');
    }

    // ---------------------------------------------------------------- show

    public function test_student_can_open_detail_of_eligible_survey(): void
    {
        $student = $this->studentUser();
        $survey = $this->makeSurvey(['title' => 'Survei Detail Siswa', 'student_target_mode' => 'all']);

        $this->actingAs($student)
            ->get(route('responses.show', $survey))
            ->assertOk()
            ->assertSee('Survei Detail Siswa')
            ->assertSee('Kembali ke daftar survei');
    }

    public function test_teacher_can_open_detail_of_eligible_survey(): void
    {
        $teacher = $this->teacherUser();
        $survey = $this->makeSurvey(['title' => 'Survei Detail Guru', 'teacher_target_mode' => 'all']);

        $this->actingAs($teacher)
            ->get(route('responses.show', $survey))
            ->assertOk()
            ->assertSee('Survei Detail Guru');
    }

    public function test_student_outside_target_gets_forbidden(): void
    {
        $student = $this->studentUser();

        $this->actingAs($student)
            ->get(route('responses.show', $this->makeSurvey(['student_target_mode' => 'classes'])))
            ->assertForbidden();
    }

    public function test_teacher_outside_target_gets_forbidden(): void
    {
        $teacher = $this->teacherUser();
        $other = $this->teacherUser();

        $survey = $this->makeSurvey(['teacher_target_mode' => 'selected']);
        $survey->targetTeachers()->attach($other->teacher->id);

        $this->actingAs($teacher)
            ->get(route('responses.show', $survey))
            ->assertForbidden();
    }

    public function test_teacher_creator_outside_target_gets_forbidden(): void
    {
        $teacher = $this->teacherUser();
        $survey = $this->makeSurvey([
            'creator_id' => $teacher->id,
            'teacher_target_mode' => 'selected',
        ]);

        $this->actingAs($teacher)
            ->get(route('responses.show', $survey))
            ->assertForbidden();
    }

    public function test_survey_already_answered_gets_forbidden(): void
    {
        $student = $this->studentUser();
        $survey = $this->makeSurvey(['student_target_mode' => 'all']);
        $this->respond($survey, $student);

        $this->actingAs($student)
            ->get(route('responses.show', $survey))
            ->assertForbidden();
    }

    public function test_draft_and_closed_surveys_get_forbidden(): void
    {
        $student = $this->studentUser();
        $draft = $this->makeSurvey(['student_target_mode' => 'all', 'status' => 'draft']);
        $closed = $this->makeSurvey(['student_target_mode' => 'all', 'status' => 'closed']);

        $this->actingAs($student)
            ->get(route('responses.show', $draft))
            ->assertForbidden();

        $this->actingAs($student)
            ->get(route('responses.show', $closed))
            ->assertForbidden();
    }

    public function test_detail_shows_questions_and_options_in_position_order(): void
    {
        $student = $this->studentUser();
        $survey = $this->makeSurvey(['student_target_mode' => 'all']);

        $second = Question::create([
            'survey_id' => $survey->id,
            'question_text' => 'Pertanyaan Kedua',
            'type' => 'single_choice',
            'is_required' => false,
            'position' => 2,
        ]);
        $first = Question::create([
            'survey_id' => $survey->id,
            'question_text' => 'Pertanyaan Pertama',
            'type' => 'single_choice',
            'is_required' => true,
            'position' => 1,
        ]);

        QuestionOption::create([
            'question_id' => $second->id,
            'option_text' => 'Opsi Kedua',
            'position' => 2,
        ]);
        QuestionOption::create([
            'question_id' => $second->id,
            'option_text' => 'Opsi Pertama',
            'position' => 1,
        ]);
        QuestionOption::create([
            'question_id' => $first->id,
            'option_text' => 'Opsi Tunggal',
            'position' => 1,
        ]);

        $response = $this->actingAs($student)->get(route('responses.show', $survey));

        $response->assertOk()
            ->assertSeeInOrder(['Pertanyaan Pertama', 'Opsi Tunggal', 'Pertanyaan Kedua', 'Opsi Pertama', 'Opsi Kedua'])
            ->assertSee('Pilihan tunggal')
            ->assertSee('Wajib')
            ->assertSee('Opsional');
    }

    public function test_non_choice_question_does_not_render_options(): void
    {
        $student = $this->studentUser();
        $survey = $this->makeSurvey(['student_target_mode' => 'all']);

        $rating = Question::create([
            'survey_id' => $survey->id,
            'question_text' => 'Nilai layanan',
            'type' => 'rating',
            'is_required' => true,
            'position' => 1,
        ]);
        QuestionOption::create([
            'question_id' => $rating->id,
            'option_text' => 'Opsi Tidak Relevan',
            'position' => 1,
        ]);

        Question::create([
            'survey_id' => $survey->id,
            'question_text' => 'Saran singkat',
            'type' => 'short_text',
            'is_required' => false,
            'position' => 2,
        ]);
        Question::create([
            'survey_id' => $survey->id,
            'question_text' => 'Saran panjang',
            'type' => 'long_text',
            'is_required' => false,
            'position' => 3,
        ]);

        $this->actingAs($student)
            ->get(route('responses.show', $survey))
            ->assertOk()
            ->assertSee('Rating 1-5')
            ->assertSee('Teks singkat')
            ->assertSee('Teks panjang')
            ->assertSee('Maksimal 255 karakter.')
            ->assertSee('Maksimal 5000 karakter.')
            ->assertDontSee('Opsi Tidak Relevan');
    }

    public function test_invalid_survey_id_returns_not_found(): void
    {
        $this->actingAs($this->studentUser())
            ->get('/isi-survei/999999999')
            ->assertNotFound();
    }

    // ---------------------------------------------------------------- stage boundary

    public function test_only_expected_response_routes_exist(): void
    {
        $this->assertTrue(Route::has('responses.index'));
        $this->assertTrue(Route::has('responses.show'));
        $this->assertTrue(Route::has('responses.store'));
        $this->assertFalse(Route::has('responses.create'));
        $this->assertFalse(Route::has('responses.update'));
        $this->assertFalse(Route::has('responses.destroy'));

        $student = $this->studentUser();
        $survey = $this->makeSurvey(['student_target_mode' => 'all']);

        $this->actingAs($student)
            ->put(route('responses.show', $survey))
            ->assertStatus(405);
    }

    public function test_detail_page_has_active_submit_form(): void
    {
        $student = $this->studentUser();
        $survey = $this->makeSurvey(['student_target_mode' => 'all']);
        Question::create([
            'survey_id' => $survey->id,
            'question_text' => 'Pertanyaan',
            'type' => 'short_text',
            'is_required' => true,
            'position' => 1,
        ]);

        $this->actingAs($student)
            ->get(route('responses.show', $survey))
            ->assertOk()
            ->assertSee('action="'.route('responses.store', $survey).'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('Kirim Jawaban')
            ->assertSee('onsubmit="return confirm(', false)
            ->assertDontSee('disabled', false);
    }
}
