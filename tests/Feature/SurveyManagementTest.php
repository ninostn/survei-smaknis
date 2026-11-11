<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\AnswerOption;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Response;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Survey;
use App\Models\SurveyCategory;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class SurveyManagementTest extends TestCase
{
    use DatabaseTransactions;

    private const PASSWORD = 'Password-Awal-123';

    private function admin(): User
    {
        return User::create([
            'username' => 'admin_'.uniqid(),
            'password' => self::PASSWORD,
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    private function teacherUser(bool $withProfile = true, bool $active = true): User
    {
        $user = User::create([
            'username' => 'guru_'.uniqid(),
            'password' => self::PASSWORD,
            'role' => 'teacher',
            'is_active' => $active,
        ]);

        if ($withProfile) {
            Teacher::create([
                'user_id' => $user->id,
                'name' => 'Guru Uji '.uniqid(),
            ]);
        }

        return $user;
    }

    private function student(): User
    {
        return User::create([
            'username' => 'siswa_'.uniqid(),
            'password' => self::PASSWORD,
            'role' => 'student',
            'is_active' => true,
        ]);
    }

    private function makeCategory(): SurveyCategory
    {
        return SurveyCategory::create([
            'name' => 'Kategori Uji '.uniqid(),
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

    private function makeAssignment(Teacher $teacher, SchoolClass $class): TeachingAssignment
    {
        $suffix = uniqid();

        $subject = Subject::create([
            'code' => 'MP-'.$suffix,
            'name' => 'Mata Pelajaran '.$suffix,
        ]);

        return TeachingAssignment::create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'academic_year' => $class->academic_year,
        ]);
    }

    private function makeSurvey(
        User $creator,
        SurveyCategory $category,
        string $title,
        array $overrides = [],
    ): Survey {
        return Survey::create(array_merge([
            'creator_id' => $creator->id,
            'category_id' => $category->id,
            'title' => $title,
        ], $overrides));
    }

    private function payload(SurveyCategory $category, string $title, array $overrides = []): array
    {
        return array_merge([
            'title' => $title,
            'description' => 'Deskripsi survei.',
            'category_id' => $category->id,
            'student_target_mode' => 'none',
            'teacher_target_mode' => 'none',
        ], $overrides);
    }

    /**
     * @return array{question: Question, option: QuestionOption, response: Response, answer: Answer, answer_option: ?AnswerOption}
     */
    private function makeResponseGraph(Survey $survey, User $user, bool $withAnswerOption = true): array
    {
        $question = Question::create([
            'survey_id' => $survey->id,
            'question_text' => 'Pertanyaan uji?',
            'type' => 'single_choice',
            'is_required' => true,
            'position' => 0,
        ]);
        $option = QuestionOption::create([
            'question_id' => $question->id,
            'option_text' => 'Pilihan uji',
            'position' => 0,
        ]);
        $response = Response::create([
            'survey_id' => $survey->id,
            'user_id' => $user->id,
            'submitted_at' => now(),
        ]);
        $answer = Answer::create([
            'response_id' => $response->id,
            'question_id' => $question->id,
            'answer_text' => null,
        ]);
        $answerOption = $withAnswerOption
            ? AnswerOption::create([
                'answer_id' => $answer->id,
                'question_id' => $question->id,
                'option_id' => $option->id,
            ])
            : null;

        return compact('question', 'option', 'response', 'answer', 'answerOption');
    }

    /**
     * @return array<string, mixed>
     */
    private function questionPayload(array $overrides = []): array
    {
        return array_merge([
            'question_text' => 'Pertanyaan '.uniqid(),
            'type' => 'short_text',
            'is_required' => '1',
            'option_texts' => [],
        ], $overrides);
    }

    private function makeQuestion(Survey $survey, array $overrides = []): Question
    {
        return Question::create(array_merge([
            'survey_id' => $survey->id,
            'question_text' => 'Pertanyaan '.uniqid(),
            'type' => 'short_text',
            'is_required' => true,
            'position' => ((int) $survey->questions()->max('position')) + 1,
        ], $overrides));
    }

    /**
     * @return array<string, string>
     */
    private function publishPayload(array $overrides = []): array
    {
        return array_merge([
            'starts_at' => now()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addDays(7)->format('Y-m-d H:i:s'),
        ], $overrides);
    }

    private function makePublishableSurvey(
        User $creator,
        SurveyCategory $category,
        SchoolClass $class,
        array $overrides = [],
    ): Survey {
        $survey = $this->makeSurvey($creator, $category, 'Survei Siap Publikasi '.uniqid(), array_merge([
            'student_target_mode' => 'classes',
        ], $overrides));

        $survey->targetClasses()->attach($class->id);

        $question = $this->makeQuestion($survey, ['type' => 'single_choice']);
        $question->options()->create(['option_text' => 'Opsi A', 'position' => 1]);
        $question->options()->create(['option_text' => 'Opsi B', 'position' => 2]);

        return $survey;
    }

    public function test_admin_can_view_all_surveys(): void
    {
        $admin = $this->admin();
        $teacher = $this->teacherUser();
        $category = $this->makeCategory();
        $adminSurvey = $this->makeSurvey($admin, $category, 'Survei Admin '.uniqid());
        $teacherSurvey = $this->makeSurvey($teacher, $category, 'Survei Guru '.uniqid());

        $this->actingAs($admin)
            ->get('/surveys')
            ->assertOk()
            ->assertViewIs('surveys.index')
            ->assertSee($adminSurvey->title)
            ->assertSee($teacherSurvey->title);
    }

    public function test_teacher_only_sees_own_surveys(): void
    {
        $admin = $this->admin();
        $teacher = $this->teacherUser();
        $category = $this->makeCategory();
        $ownSurvey = $this->makeSurvey($teacher, $category, 'Survei Milik Guru '.uniqid());
        $otherSurvey = $this->makeSurvey($admin, $category, 'Survei Milik Admin '.uniqid());

        $this->actingAs($teacher)
            ->get('/surveys')
            ->assertOk()
            ->assertSee($ownSurvey->title)
            ->assertDontSee($otherSurvey->title);
    }

    public function test_student_cannot_access_survey_management(): void
    {
        $student = $this->student();
        $category = $this->makeCategory();

        $this->actingAs($student)->get('/surveys')->assertForbidden();
        $this->actingAs($student)->get('/surveys/create')->assertForbidden();
        $this->actingAs($student)
            ->post('/surveys', $this->payload($category, 'Survei Siswa '.uniqid()))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/surveys')->assertRedirect('/login');
        $this->get('/surveys/create')->assertRedirect('/login');
        $this->post('/surveys', [])->assertRedirect('/login');
    }

    public function test_admin_can_view_create_survey_form(): void
    {
        $this->actingAs($this->admin())
            ->get('/surveys/create')
            ->assertOk()
            ->assertViewIs('surveys.create')
            ->assertSee('Tambah Survei');
    }

    public function test_admin_can_create_draft_survey(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $title = 'Draft Admin '.uniqid();

        $this->actingAs($admin)
            ->post('/surveys', $this->payload($category, $title))
            ->assertRedirect('/surveys')
            ->assertSessionHas('status');

        $survey = Survey::where('title', $title)->firstOrFail();

        $this->assertSame($admin->id, $survey->creator_id);
        $this->assertSame($category->id, $survey->category_id);
        $this->assertSame('draft', $survey->status);
        $this->assertSame('none', $survey->student_target_mode);
        $this->assertSame('none', $survey->teacher_target_mode);
        $this->assertNull($survey->starts_at);
        $this->assertNull($survey->ends_at);
        $this->assertFalse($survey->is_result_published);
    }

    public function test_teacher_with_profile_can_create_draft_survey(): void
    {
        $teacher = $this->teacherUser();
        $category = $this->makeCategory();
        $title = 'Draft Guru '.uniqid();

        $this->actingAs($teacher)
            ->post('/surveys', $this->payload($category, $title))
            ->assertRedirect('/surveys')
            ->assertSessionHas('status');

        $survey = Survey::where('title', $title)->firstOrFail();

        $this->assertSame($teacher->id, $survey->creator_id);
        $this->assertSame('draft', $survey->status);
    }

    public function test_teacher_without_profile_is_forbidden(): void
    {
        $teacher = $this->teacherUser(false);
        $category = $this->makeCategory();
        $title = 'Draft Tanpa Profil '.uniqid();

        $this->actingAs($teacher)->get('/surveys')->assertForbidden();
        $this->actingAs($teacher)->get('/surveys/create')->assertForbidden();
        $this->actingAs($teacher)
            ->post('/surveys', $this->payload($category, $title))
            ->assertForbidden();

        $this->assertDatabaseMissing('surveys', ['title' => $title]);
    }

    public function test_creator_id_always_uses_authenticated_user(): void
    {
        $admin = $this->admin();
        $otherAdmin = $this->admin();
        $category = $this->makeCategory();
        $title = 'Creator Aman '.uniqid();

        $this->actingAs($admin)
            ->post('/surveys', $this->payload($category, $title, [
                'creator_id' => $otherAdmin->id,
            ]))
            ->assertRedirect('/surveys');

        $survey = Survey::where('title', $title)->firstOrFail();

        $this->assertSame($admin->id, $survey->creator_id);
        $this->assertNotSame($otherAdmin->id, $survey->creator_id);
    }

    public function test_status_is_always_draft(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $title = 'Status Aman '.uniqid();

        $this->actingAs($admin)
            ->post('/surveys', $this->payload($category, $title, [
                'status' => 'published',
            ]))
            ->assertRedirect('/surveys');

        $this->assertSame('draft', Survey::where('title', $title)->firstOrFail()->status);
    }

    public function test_is_result_published_is_always_false(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $title = 'Hasil Aman '.uniqid();

        $this->actingAs($admin)
            ->post('/surveys', $this->payload($category, $title, [
                'is_result_published' => true,
            ]))
            ->assertRedirect('/surveys');

        $this->assertFalse(Survey::where('title', $title)->firstOrFail()->is_result_published);
    }

    public function test_dates_are_always_null(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $title = 'Tanggal Aman '.uniqid();

        $this->actingAs($admin)
            ->post('/surveys', $this->payload($category, $title, [
                'starts_at' => '2026-11-01 08:00:00',
                'ends_at' => '2026-11-30 17:00:00',
            ]))
            ->assertRedirect('/surveys');

        $survey = Survey::where('title', $title)->firstOrFail();

        $this->assertNull($survey->starts_at);
        $this->assertNull($survey->ends_at);
    }

    public function test_invalid_category_is_rejected(): void
    {
        $admin = $this->admin();
        $title = 'Kategori Tidak Ada '.uniqid();

        $this->actingAs($admin)
            ->post('/surveys', [
                'title' => $title,
                'description' => 'Deskripsi.',
                'category_id' => 999999,
            ])
            ->assertSessionHasErrors('category_id');

        $this->assertDatabaseMissing('surveys', ['title' => $title]);
    }

    public function test_empty_title_is_rejected(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $title = '   ';

        $this->actingAs($admin)
            ->post('/surveys', $this->payload($category, $title))
            ->assertSessionHasErrors('title');

        $this->assertDatabaseMissing('surveys', ['title' => $title]);
    }

    public function test_title_over_255_characters_is_rejected(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $title = str_repeat('a', 256);

        $this->actingAs($admin)
            ->post('/surveys', $this->payload($category, $title))
            ->assertSessionHasErrors('title');

        $this->assertDatabaseMissing('surveys', ['title' => $title]);
    }

    public function test_description_over_5000_characters_is_rejected(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $title = 'Deskripsi Panjang '.uniqid();

        $this->actingAs($admin)
            ->post('/surveys', $this->payload($category, $title, [
                'description' => str_repeat('a', 5001),
            ]))
            ->assertSessionHasErrors('description');

        $this->assertDatabaseMissing('surveys', ['title' => $title]);
    }

    public function test_extra_request_fields_do_not_change_protected_data(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $title = 'Field Tambahan '.uniqid();

        $this->actingAs($admin)
            ->post('/surveys', $this->payload($category, $title, [
                'id' => 999999,
                'created_at' => '2000-01-01 00:00:00',
                'updated_at' => '2000-01-01 00:00:00',
                'creator_id' => $admin->id,
                'status' => 'closed',
                'is_result_published' => true,
                'starts_at' => '2026-11-01 08:00:00',
                'ends_at' => '2026-11-30 17:00:00',
            ]))
            ->assertRedirect('/surveys');

        $survey = Survey::where('title', $title)->firstOrFail();

        $this->assertNotSame(999999, $survey->id);
        $this->assertNotSame('2000-01-01 00:00:00', $survey->created_at->format('Y-m-d H:i:s'));
        $this->assertSame('draft', $survey->status);
        $this->assertFalse($survey->is_result_published);
        $this->assertSame('none', $survey->student_target_mode);
        $this->assertSame('none', $survey->teacher_target_mode);
        $this->assertNull($survey->starts_at);
        $this->assertNull($survey->ends_at);
    }

    public function test_search_filters_by_title(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $prefix = 'Pencarian '.uniqid();
        $matching = $this->makeSurvey($admin, $category, $prefix.' Survei Utama');
        $other = $this->makeSurvey($admin, $category, 'Survei Lain '.uniqid());

        $this->actingAs($admin)
            ->get('/surveys?search='.urlencode($prefix))
            ->assertOk()
            ->assertSee($matching->title)
            ->assertDontSee($other->title);
    }

    public function test_status_filter_works(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $draft = $this->makeSurvey($admin, $category, 'Draft Filter '.uniqid(), ['status' => 'draft']);
        $published = $this->makeSurvey($admin, $category, 'Published Filter '.uniqid(), ['status' => 'published']);

        $this->actingAs($admin)
            ->get('/surveys?status=published')
            ->assertOk()
            ->assertSee($published->title)
            ->assertDontSee($draft->title)
            ->assertViewHas('surveys', fn ($surveys) => $surveys->getCollection()->every(
                fn (Survey $survey) => $survey->status === 'published',
            ));
    }

    public function test_category_filter_works(): void
    {
        $admin = $this->admin();
        $firstCategory = $this->makeCategory();
        $secondCategory = $this->makeCategory();
        $first = $this->makeSurvey($admin, $firstCategory, 'Kategori Pertama '.uniqid());
        $second = $this->makeSurvey($admin, $secondCategory, 'Kategori Kedua '.uniqid());

        $this->actingAs($admin)
            ->get('/surveys?category_id='.$firstCategory->id)
            ->assertOk()
            ->assertSee($first->title)
            ->assertDontSee($second->title)
            ->assertViewHas('surveys', fn ($surveys) => $surveys->getCollection()->every(
                fn (Survey $survey) => $survey->category_id === $firstCategory->id,
            ));
    }

    public function test_surveys_are_paginated_ten_per_page(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $prefix = 'Paginasi '.uniqid();

        foreach (range(1, 11) as $number) {
            $this->makeSurvey($admin, $category, $prefix.' '.$number);
        }

        $this->actingAs($admin)
            ->get('/surveys?search='.urlencode($prefix))
            ->assertOk()
            ->assertViewHas('surveys', fn ($surveys) => $surveys->count() === 10 && $surveys->total() === 11);

        $this->actingAs($admin)
            ->get('/surveys?search='.urlencode($prefix).'&page=2')
            ->assertOk()
            ->assertViewHas('surveys', fn ($surveys) => $surveys->count() === 1 && $surveys->total() === 11);
    }

    public function test_teacher_cannot_see_survey_created_by_another_teacher(): void
    {
        $teacherA = $this->teacherUser();
        $teacherB = $this->teacherUser();
        $category = $this->makeCategory();
        $surveyB = $this->makeSurvey($teacherB, $category, 'Survei Guru B '.uniqid());

        $this->actingAs($teacherA)
            ->get('/surveys')
            ->assertOk()
            ->assertDontSee($surveyB->title);
    }

    public function test_teacher_creator_query_parameter_does_not_expand_scope(): void
    {
        $teacherA = $this->teacherUser();
        $teacherB = $this->teacherUser();
        $category = $this->makeCategory();
        $surveyB = $this->makeSurvey($teacherB, $category, 'Survei Scope Guru B '.uniqid());

        $this->actingAs($teacherA)
            ->get('/surveys?creator_id='.$teacherB->id)
            ->assertOk()
            ->assertDontSee($surveyB->title);
    }

    public function test_invalid_listing_query_parameters_do_not_cause_server_error(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get('/surveys?search[]=x&status[]=draft&category_id[]=1')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/surveys?search=%5B%5D&status=unknown&category_id=abc')
            ->assertOk();
    }

    public function test_title_exactly_255_characters_is_accepted(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $title = substr('Judul '.uniqid().str_repeat('a', 255), 0, 255);

        $this->assertSame(255, mb_strlen($title));

        $this->actingAs($admin)
            ->post('/surveys', $this->payload($category, $title))
            ->assertRedirect('/surveys');

        $this->assertDatabaseHas('surveys', ['title' => $title]);
    }

    public function test_description_exactly_5000_characters_is_accepted(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $title = 'Deskripsi Tepat '.uniqid();
        $description = str_repeat('d', 5000);

        $this->actingAs($admin)
            ->post('/surveys', $this->payload($category, $title, [
                'description' => $description,
            ]))
            ->assertRedirect('/surveys');

        $this->assertDatabaseHas('surveys', [
            'title' => $title,
            'description' => $description,
        ]);
    }

    public function test_missing_description_is_stored_as_null(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $title = 'Tanpa Deskripsi '.uniqid();
        $payload = $this->payload($category, $title);
        unset($payload['description']);

        $this->actingAs($admin)
            ->post('/surveys', $payload)
            ->assertRedirect('/surveys');

        $this->assertNull(Survey::where('title', $title)->firstOrFail()->description);
    }

    public function test_admin_can_target_selected_active_classes(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $firstClass = $this->makeClass();
        $secondClass = $this->makeClass();
        $title = 'Target Kelas Admin '.uniqid();

        $this->actingAs($admin)
            ->post('/surveys', $this->payload($category, $title, [
                'student_target_mode' => 'classes',
                'target_class_ids' => [$firstClass->id, $secondClass->id],
            ]))
            ->assertRedirect('/surveys');

        $survey = Survey::where('title', $title)->firstOrFail();

        $this->assertSame('classes', $survey->student_target_mode);
        $this->assertSame('none', $survey->teacher_target_mode);
        $this->assertDatabaseHas('survey_target_classes', ['survey_id' => $survey->id, 'class_id' => $firstClass->id]);
        $this->assertDatabaseHas('survey_target_classes', ['survey_id' => $survey->id, 'class_id' => $secondClass->id]);
    }

    public function test_teacher_can_only_target_classes_from_own_teaching_assignments(): void
    {
        $teacher = $this->teacherUser();
        $taughtClass = $this->makeClass();
        $otherClass = $this->makeClass();
        $this->makeAssignment($teacher->teacher, $taughtClass);

        $category = $this->makeCategory();
        $title = 'Kelas Diajar '.uniqid();

        $this->actingAs($teacher)
            ->post('/surveys', $this->payload($category, $title, [
                'student_target_mode' => 'classes',
                'target_class_ids' => [$taughtClass->id],
            ]))
            ->assertRedirect('/surveys');

        $survey = Survey::where('title', $title)->firstOrFail();

        $this->assertDatabaseHas('survey_target_classes', [
            'survey_id' => $survey->id,
            'class_id' => $taughtClass->id,
        ]);

        $forbiddenTitle = 'Kelas Bukan Milik Guru '.uniqid();

        $this->actingAs($teacher)
            ->post('/surveys', $this->payload($category, $forbiddenTitle, [
                'student_target_mode' => 'classes',
                'target_class_ids' => [$otherClass->id],
            ]))
            ->assertSessionHasErrors();

        $this->assertDatabaseMissing('surveys', ['title' => $forbiddenTitle]);
    }

    public function test_teacher_create_form_only_lists_taught_active_classes(): void
    {
        $teacher = $this->teacherUser();
        $taughtClass = $this->makeClass();
        $otherClass = $this->makeClass();
        $inactiveClass = $this->makeClass(false);
        $this->makeAssignment($teacher->teacher, $taughtClass);
        $this->makeAssignment($teacher->teacher, $inactiveClass);

        $this->actingAs($teacher)
            ->get('/surveys/create')
            ->assertOk()
            ->assertSee($taughtClass->name)
            ->assertDontSee($otherClass->name)
            ->assertDontSee($inactiveClass->name);
    }

    public function test_teacher_cannot_target_all_students(): void
    {
        $teacher = $this->teacherUser();
        $category = $this->makeCategory();
        $title = 'Semua Siswa Guru '.uniqid();

        $this->actingAs($teacher)
            ->post('/surveys', $this->payload($category, $title, [
                'student_target_mode' => 'all',
            ]))
            ->assertSessionHasErrors('student_target_mode');

        $this->assertDatabaseMissing('surveys', ['title' => $title]);
    }

    public function test_classes_mode_requires_at_least_one_class(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $title = 'Kelas Kosong '.uniqid();

        $this->actingAs($admin)
            ->post('/surveys', $this->payload($category, $title, [
                'student_target_mode' => 'classes',
                'target_class_ids' => [],
            ]))
            ->assertSessionHasErrors('target_class_ids');

        $this->assertDatabaseMissing('surveys', ['title' => $title]);
    }

    public function test_selected_teacher_mode_requires_at_least_one_teacher(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $title = 'Guru Kosong '.uniqid();

        $this->actingAs($admin)
            ->post('/surveys', $this->payload($category, $title, [
                'teacher_target_mode' => 'selected',
                'target_teacher_ids' => [],
            ]))
            ->assertSessionHasErrors('target_teacher_ids');

        $this->assertDatabaseMissing('surveys', ['title' => $title]);
    }

    public function test_inactive_class_cannot_be_targeted(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $inactiveClass = $this->makeClass(false);
        $title = 'Kelas Nonaktif '.uniqid();

        $this->actingAs($admin)
            ->post('/surveys', $this->payload($category, $title, [
                'student_target_mode' => 'classes',
                'target_class_ids' => [$inactiveClass->id],
            ]))
            ->assertSessionHasErrors();

        $this->assertDatabaseMissing('surveys', ['title' => $title]);
    }

    public function test_inactive_teacher_cannot_be_targeted(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $inactiveTeacher = $this->teacherUser(true, false)->teacher;
        $title = 'Guru Nonaktif '.uniqid();

        $this->actingAs($admin)
            ->post('/surveys', $this->payload($category, $title, [
                'teacher_target_mode' => 'selected',
                'target_teacher_ids' => [$inactiveTeacher->id],
            ]))
            ->assertSessionHasErrors();

        $this->assertDatabaseMissing('surveys', ['title' => $title]);
    }

    public function test_non_teacher_account_cannot_be_targeted_as_teacher(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $studentUser = $this->student();
        $invalidTeacher = Teacher::create([
            'user_id' => $studentUser->id,
            'name' => 'Profil Bukan Guru '.uniqid(),
        ]);
        $title = 'Bukan Guru '.uniqid();

        $this->actingAs($admin)
            ->post('/surveys', $this->payload($category, $title, [
                'teacher_target_mode' => 'selected',
                'target_teacher_ids' => [$invalidTeacher->id],
            ]))
            ->assertSessionHasErrors();

        $this->assertDatabaseMissing('surveys', ['title' => $title]);
    }

    public function test_target_pivots_are_saved_for_classes_and_selected_teachers(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $class = $this->makeClass();
        $teacher = $this->teacherUser()->teacher;
        $title = 'Pivot Lengkap '.uniqid();

        $this->actingAs($admin)
            ->post('/surveys', $this->payload($category, $title, [
                'student_target_mode' => 'classes',
                'target_class_ids' => [$class->id],
                'teacher_target_mode' => 'selected',
                'target_teacher_ids' => [$teacher->id],
            ]))
            ->assertRedirect('/surveys');

        $survey = Survey::where('title', $title)->firstOrFail();

        $this->assertSame('classes', $survey->student_target_mode);
        $this->assertSame('selected', $survey->teacher_target_mode);
        $this->assertDatabaseHas('survey_target_classes', ['survey_id' => $survey->id, 'class_id' => $class->id]);
        $this->assertDatabaseHas('survey_target_teachers', ['survey_id' => $survey->id, 'teacher_id' => $teacher->id]);
    }

    public function test_teacher_can_target_self_as_selected_teacher(): void
    {
        $teacherUser = $this->teacherUser();
        $category = $this->makeCategory();
        $title = 'Target Diri Sendiri '.uniqid();

        $this->actingAs($teacherUser)
            ->post('/surveys', $this->payload($category, $title, [
                'teacher_target_mode' => 'selected',
                'target_teacher_ids' => [$teacherUser->teacher->id],
            ]))
            ->assertRedirect('/surveys');

        $survey = Survey::where('title', $title)->firstOrFail();

        $this->assertDatabaseHas('survey_target_teachers', [
            'survey_id' => $survey->id,
            'teacher_id' => $teacherUser->teacher->id,
        ]);
    }

    public function test_teacher_can_target_all_teachers(): void
    {
        $teacherUser = $this->teacherUser();
        $category = $this->makeCategory();
        $title = 'Semua Guru '.uniqid();

        $this->actingAs($teacherUser)
            ->post('/surveys', $this->payload($category, $title, [
                'teacher_target_mode' => 'all',
            ]))
            ->assertRedirect('/surveys');

        $survey = Survey::where('title', $title)->firstOrFail();

        $this->assertSame('all', $survey->teacher_target_mode);
        $this->assertSame(0, DB::table('survey_target_teachers')->where('survey_id', $survey->id)->count());
    }

    public function test_none_and_all_modes_keep_target_pivots_empty(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $class = $this->makeClass();
        $teacher = $this->teacherUser()->teacher;

        $noneTitle = 'Tanpa Target '.uniqid();

        $this->actingAs($admin)
            ->post('/surveys', $this->payload($category, $noneTitle))
            ->assertRedirect('/surveys');

        $noneSurvey = Survey::where('title', $noneTitle)->firstOrFail();

        $this->assertSame(0, DB::table('survey_target_classes')->where('survey_id', $noneSurvey->id)->count());
        $this->assertSame(0, DB::table('survey_target_teachers')->where('survey_id', $noneSurvey->id)->count());

        $allTitle = 'Semua Target '.uniqid();

        $this->actingAs($admin)
            ->post('/surveys', $this->payload($category, $allTitle, [
                'student_target_mode' => 'all',
                'teacher_target_mode' => 'all',
                'target_class_ids' => [$class->id],
                'target_teacher_ids' => [$teacher->id],
            ]))
            ->assertRedirect('/surveys');

        $allSurvey = Survey::where('title', $allTitle)->firstOrFail();

        $this->assertSame('all', $allSurvey->student_target_mode);
        $this->assertSame('all', $allSurvey->teacher_target_mode);
        $this->assertSame(0, DB::table('survey_target_classes')->where('survey_id', $allSurvey->id)->count());
        $this->assertSame(0, DB::table('survey_target_teachers')->where('survey_id', $allSurvey->id)->count());
    }

    public function test_invalid_target_selection_does_not_create_partial_survey(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $class = $this->makeClass();
        $title = 'Target Tidak Valid '.uniqid();

        $this->actingAs($admin)
            ->post('/surveys', $this->payload($category, $title, [
                'student_target_mode' => 'classes',
                'target_class_ids' => [$class->id, 999999],
            ]))
            ->assertSessionHasErrors();

        $this->assertDatabaseMissing('surveys', ['title' => $title]);
        $this->assertSame(0, DB::table('survey_target_classes')->count());
    }

    public function test_pivot_failure_rolls_back_survey_creation(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $class = $this->makeClass();
        $title = 'Rollback Pivot '.uniqid();

        DB::listen(function ($query): void {
            if (str_starts_with(strtolower($query->sql), 'insert into')
                && str_contains($query->sql, 'survey_target_classes')) {
                throw new RuntimeException('Simulasi kegagalan penyimpanan pivot.');
            }
        });

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($admin)->post('/surveys', $this->payload($category, $title, [
                'student_target_mode' => 'classes',
                'target_class_ids' => [$class->id],
            ]));

            $this->fail('Penyimpanan pivot seharusnya gagal.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulasi kegagalan penyimpanan pivot.', $exception->getMessage());
        }

        $this->assertDatabaseMissing('surveys', ['title' => $title]);
        $this->assertSame(0, DB::table('survey_target_classes')->count());
    }

    public function test_teacher_pivot_failure_rolls_back_survey_and_class_pivot(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $class = $this->makeClass();
        $teacher = $this->teacherUser()->teacher;
        $title = 'Rollback Pivot Guru '.uniqid();

        DB::listen(function ($query): void {
            if (str_starts_with(strtolower($query->sql), 'insert into')
                && str_contains($query->sql, 'survey_target_teachers')) {
                throw new RuntimeException('Simulasi kegagalan penyimpanan pivot guru.');
            }
        });

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($admin)->post('/surveys', $this->payload($category, $title, [
                'student_target_mode' => 'classes',
                'target_class_ids' => [$class->id],
                'teacher_target_mode' => 'selected',
                'target_teacher_ids' => [$teacher->id],
            ]));

            $this->fail('Penyimpanan pivot guru seharusnya gagal.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulasi kegagalan penyimpanan pivot guru.', $exception->getMessage());
        }

        $this->assertDatabaseMissing('surveys', ['title' => $title]);
        $this->assertSame(0, DB::table('survey_target_classes')->count());
        $this->assertSame(0, DB::table('survey_target_teachers')->count());
    }

    public function test_admin_create_form_only_lists_active_classes(): void
    {
        $admin = $this->admin();
        $activeClass = $this->makeClass();
        $inactiveClass = $this->makeClass(false);

        $this->actingAs($admin)
            ->get('/surveys/create')
            ->assertOk()
            ->assertSee($activeClass->name)
            ->assertDontSee($inactiveClass->name)
            ->assertSee('Semua siswa aktif');
    }

    public function test_teacher_target_list_only_lists_active_teachers(): void
    {
        $admin = $this->admin();
        $activeTeacher = $this->teacherUser(true, true)->teacher;
        $inactiveTeacher = $this->teacherUser(true, false)->teacher;

        $this->actingAs($admin)
            ->get('/surveys/create')
            ->assertOk()
            ->assertSee($activeTeacher->name)
            ->assertDontSee($inactiveTeacher->name);
    }

    public function test_teacher_create_form_does_not_offer_all_students_mode(): void
    {
        $teacher = $this->teacherUser();

        $this->actingAs($teacher)
            ->get('/surveys/create')
            ->assertOk()
            ->assertDontSee('Semua siswa aktif');
    }

    public function test_array_target_modes_are_rejected_without_server_error(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $title = 'Mode Array '.uniqid();

        $this->actingAs($admin)
            ->post('/surveys', $this->payload($category, $title, [
                'student_target_mode' => ['all'],
                'teacher_target_mode' => ['all'],
            ]))
            ->assertStatus(302)
            ->assertSessionHasErrors(['student_target_mode', 'teacher_target_mode']);

        $this->assertDatabaseMissing('surveys', ['title' => $title]);
    }

    public function test_duplicate_target_class_ids_are_rejected(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $class = $this->makeClass();
        $title = 'Kelas Duplikat '.uniqid();

        $this->actingAs($admin)
            ->post('/surveys', $this->payload($category, $title, [
                'student_target_mode' => 'classes',
                'target_class_ids' => [$class->id, $class->id],
            ]))
            ->assertSessionHasErrors();

        $this->assertDatabaseMissing('surveys', ['title' => $title]);
        $this->assertSame(0, DB::table('survey_target_classes')->count());
    }

    public function test_duplicate_target_teacher_ids_are_rejected(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $teacher = $this->teacherUser()->teacher;
        $title = 'Guru Duplikat '.uniqid();

        $this->actingAs($admin)
            ->post('/surveys', $this->payload($category, $title, [
                'teacher_target_mode' => 'selected',
                'target_teacher_ids' => [$teacher->id, $teacher->id],
            ]))
            ->assertSessionHasErrors();

        $this->assertDatabaseMissing('surveys', ['title' => $title]);
        $this->assertSame(0, DB::table('survey_target_teachers')->count());
    }

    public function test_teacher_cannot_target_inactive_own_class_via_post(): void
    {
        $teacher = $this->teacherUser();
        $inactiveClass = $this->makeClass(false);
        $this->makeAssignment($teacher->teacher, $inactiveClass);

        $category = $this->makeCategory();
        $title = 'Kelas Nonaktif Guru '.uniqid();

        $this->actingAs($teacher)
            ->post('/surveys', $this->payload($category, $title, [
                'student_target_mode' => 'classes',
                'target_class_ids' => [$inactiveClass->id],
            ]))
            ->assertSessionHasErrors();

        $this->assertDatabaseMissing('surveys', ['title' => $title]);
        $this->assertSame(0, DB::table('survey_target_classes')->count());
    }

    public function test_mixed_valid_and_invalid_teacher_targets_do_not_create_partial_survey(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $teacher = $this->teacherUser()->teacher;
        $title = 'Guru Campuran '.uniqid();

        $this->actingAs($admin)
            ->post('/surveys', $this->payload($category, $title, [
                'teacher_target_mode' => 'selected',
                'target_teacher_ids' => [$teacher->id, 999999],
            ]))
            ->assertSessionHasErrors();

        $this->assertDatabaseMissing('surveys', ['title' => $title]);
        $this->assertSame(0, DB::table('survey_target_teachers')->count());
    }

    public function test_target_pivot_counts_match_selected_targets(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $firstClass = $this->makeClass();
        $secondClass = $this->makeClass();
        $firstTeacher = $this->teacherUser()->teacher;
        $secondTeacher = $this->teacherUser()->teacher;
        $title = 'Hitung Pivot '.uniqid();

        $this->actingAs($admin)
            ->post('/surveys', $this->payload($category, $title, [
                'student_target_mode' => 'classes',
                'target_class_ids' => [$firstClass->id, $secondClass->id],
                'teacher_target_mode' => 'selected',
                'target_teacher_ids' => [$firstTeacher->id, $secondTeacher->id],
            ]))
            ->assertRedirect('/surveys');

        $survey = Survey::where('title', $title)->firstOrFail();

        $this->assertSame(2, DB::table('survey_target_classes')->where('survey_id', $survey->id)->count());
        $this->assertSame(2, DB::table('survey_target_teachers')->where('survey_id', $survey->id)->count());
        $this->assertEqualsCanonicalizing(
            [$firstClass->id, $secondClass->id],
            DB::table('survey_target_classes')->where('survey_id', $survey->id)->pluck('class_id')->all(),
        );
        $this->assertEqualsCanonicalizing(
            [$firstTeacher->id, $secondTeacher->id],
            DB::table('survey_target_teachers')->where('survey_id', $survey->id)->pluck('teacher_id')->all(),
        );
    }

    public function test_admin_can_edit_teachers_draft_survey(): void
    {
        $teacher = $this->teacherUser();
        $admin = $this->admin();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($teacher, $category, 'Draft Guru '.uniqid());

        $this->actingAs($admin)
            ->get('/surveys/'.$survey->id.'/edit')
            ->assertOk()
            ->assertViewIs('surveys.edit')
            ->assertSee($survey->title);

        $newTitle = 'Draft Diperbarui Admin '.uniqid();

        $this->actingAs($admin)
            ->put('/surveys/'.$survey->id, $this->payload($category, $newTitle))
            ->assertRedirect('/surveys');

        $survey->refresh();

        $this->assertSame($newTitle, $survey->title);
        $this->assertSame($teacher->id, $survey->creator_id);
    }

    public function test_teacher_can_edit_own_draft_survey(): void
    {
        $teacher = $this->teacherUser();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($teacher, $category, 'Draft Sendiri '.uniqid());
        $newTitle = 'Draft Sendiri Diperbarui '.uniqid();

        $this->actingAs($teacher)
            ->get('/surveys/'.$survey->id.'/edit')
            ->assertOk()
            ->assertSee($survey->title);

        $this->actingAs($teacher)
            ->put('/surveys/'.$survey->id, $this->payload($category, $newTitle))
            ->assertRedirect('/surveys');

        $this->assertSame($newTitle, $survey->refresh()->title);
    }

    public function test_teacher_cannot_edit_another_teachers_draft(): void
    {
        $owner = $this->teacherUser();
        $other = $this->teacherUser();
        $category = $this->makeCategory();
        $title = 'Milik Guru Lain '.uniqid();
        $survey = $this->makeSurvey($owner, $category, $title);

        $this->actingAs($other)
            ->get('/surveys/'.$survey->id.'/edit')
            ->assertForbidden();

        $this->actingAs($other)
            ->put('/surveys/'.$survey->id, $this->payload($category, 'Diambil Alih '.uniqid()))
            ->assertForbidden();

        $this->assertSame($title, $survey->refresh()->title);
    }

    public function test_published_and_closed_surveys_cannot_be_edited(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $published = $this->makeSurvey($admin, $category, 'Survei Published '.uniqid(), ['status' => 'published']);
        $closed = $this->makeSurvey($admin, $category, 'Survei Closed '.uniqid(), ['status' => 'closed']);

        foreach ([$published, $closed] as $survey) {
            $this->actingAs($admin)
                ->get('/surveys/'.$survey->id.'/edit')
                ->assertForbidden();

            $this->actingAs($admin)
                ->put('/surveys/'.$survey->id, $this->payload($category, 'Tidak Boleh '.uniqid()))
                ->assertForbidden();
        }

        $this->assertSame('published', $published->refresh()->status);
        $this->assertSame('closed', $closed->refresh()->status);
    }

    public function test_student_and_guest_cannot_edit_surveys(): void
    {
        $teacher = $this->teacherUser();
        $student = $this->student();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($teacher, $category, 'Tidak Untuk Siswa '.uniqid());

        $this->actingAs($student)
            ->get('/surveys/'.$survey->id.'/edit')
            ->assertForbidden();

        $this->actingAs($student)
            ->put('/surveys/'.$survey->id, $this->payload($category, 'Siswa '.uniqid()))
            ->assertForbidden();

        auth()->logout();

        $this->get('/surveys/'.$survey->id.'/edit')->assertRedirect('/login');
    }

    public function test_teacher_without_profile_cannot_edit_survey(): void
    {
        $teacher = $this->teacherUser(false);
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($teacher, $category, 'Tanpa Profil '.uniqid());

        $this->actingAs($teacher)
            ->get('/surveys/'.$survey->id.'/edit')
            ->assertForbidden();

        $this->actingAs($teacher)
            ->put('/surveys/'.$survey->id, $this->payload($category, 'Tanpa Profil Baru '.uniqid()))
            ->assertForbidden();
    }

    public function test_edit_updates_title_description_and_category(): void
    {
        $admin = $this->admin();
        $oldCategory = $this->makeCategory();
        $newCategory = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $oldCategory, 'Judul Lama '.uniqid());
        $newTitle = 'Judul Baru '.uniqid();

        $this->actingAs($admin)
            ->put('/surveys/'.$survey->id, [
                'title' => $newTitle,
                'description' => 'Deskripsi baru hasil edit.',
                'category_id' => $newCategory->id,
                'student_target_mode' => 'none',
                'teacher_target_mode' => 'none',
            ])
            ->assertRedirect('/surveys');

        $survey->refresh();

        $this->assertSame($newTitle, $survey->title);
        $this->assertSame('Deskripsi baru hasil edit.', $survey->description);
        $this->assertSame($newCategory->id, $survey->category_id);
    }

    public function test_edit_updates_target_pivots(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $oldClass = $this->makeClass();
        $newClass = $this->makeClass();
        $oldTeacher = $this->teacherUser()->teacher;
        $newTeacher = $this->teacherUser()->teacher;
        $survey = $this->makeSurvey($admin, $category, 'Pivot Edit '.uniqid(), [
            'student_target_mode' => 'classes',
            'teacher_target_mode' => 'selected',
        ]);
        $survey->targetClasses()->sync([$oldClass->id]);
        $survey->targetTeachers()->sync([$oldTeacher->id]);

        $this->actingAs($admin)
            ->put('/surveys/'.$survey->id, $this->payload($category, $survey->title, [
                'student_target_mode' => 'classes',
                'target_class_ids' => [$newClass->id],
                'teacher_target_mode' => 'selected',
                'target_teacher_ids' => [$newTeacher->id],
            ]))
            ->assertRedirect('/surveys');

        $this->assertDatabaseHas('survey_target_classes', ['survey_id' => $survey->id, 'class_id' => $newClass->id]);
        $this->assertDatabaseHas('survey_target_teachers', ['survey_id' => $survey->id, 'teacher_id' => $newTeacher->id]);
        $this->assertDatabaseMissing('survey_target_classes', ['survey_id' => $survey->id, 'class_id' => $oldClass->id]);
        $this->assertDatabaseMissing('survey_target_teachers', ['survey_id' => $survey->id, 'teacher_id' => $oldTeacher->id]);
        $this->assertSame(1, DB::table('survey_target_classes')->where('survey_id', $survey->id)->count());
        $this->assertSame(1, DB::table('survey_target_teachers')->where('survey_id', $survey->id)->count());
    }

    public function test_edit_switching_target_modes_to_none_and_all_clears_pivots(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $class = $this->makeClass();
        $teacher = $this->teacherUser()->teacher;
        $survey = $this->makeSurvey($admin, $category, 'Kosongkan Pivot '.uniqid(), [
            'student_target_mode' => 'classes',
            'teacher_target_mode' => 'selected',
        ]);
        $survey->targetClasses()->sync([$class->id]);
        $survey->targetTeachers()->sync([$teacher->id]);

        $this->actingAs($admin)
            ->put('/surveys/'.$survey->id, $this->payload($category, $survey->title, [
                'student_target_mode' => 'none',
                'teacher_target_mode' => 'all',
            ]))
            ->assertRedirect('/surveys');

        $survey->refresh();

        $this->assertSame('none', $survey->student_target_mode);
        $this->assertSame('all', $survey->teacher_target_mode);
        $this->assertSame(0, DB::table('survey_target_classes')->where('survey_id', $survey->id)->count());
        $this->assertSame(0, DB::table('survey_target_teachers')->where('survey_id', $survey->id)->count());
    }

    public function test_failed_validation_keeps_existing_survey_and_pivots(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $class = $this->makeClass();
        $teacher = $this->teacherUser()->teacher;
        $title = 'Tetap Utuh '.uniqid();
        $survey = $this->makeSurvey($admin, $category, $title, [
            'description' => 'Deskripsi asli.',
            'student_target_mode' => 'classes',
            'teacher_target_mode' => 'selected',
        ]);
        $survey->targetClasses()->sync([$class->id]);
        $survey->targetTeachers()->sync([$teacher->id]);

        $this->actingAs($admin)
            ->put('/surveys/'.$survey->id, [
                'title' => '   ',
                'description' => 'Deskripsi gagal.',
                'category_id' => $category->id,
                'student_target_mode' => 'classes',
                'target_class_ids' => [$class->id, 999999],
                'teacher_target_mode' => 'selected',
                'target_teacher_ids' => [999999],
            ])
            ->assertSessionHasErrors(['title', 'target_class_ids.1', 'target_teacher_ids.0']);

        $survey->refresh();

        $this->assertSame($title, $survey->title);
        $this->assertSame('Deskripsi asli.', $survey->description);
        $this->assertSame('classes', $survey->student_target_mode);
        $this->assertSame('selected', $survey->teacher_target_mode);
        $this->assertDatabaseHas('survey_target_classes', ['survey_id' => $survey->id, 'class_id' => $class->id]);
        $this->assertDatabaseHas('survey_target_teachers', ['survey_id' => $survey->id, 'teacher_id' => $teacher->id]);
    }

    public function test_update_rolls_back_when_class_pivot_storage_fails(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $oldClass = $this->makeClass();
        $newClass = $this->makeClass();
        $oldTitle = 'Judul Sebelum Rollback '.uniqid();
        $survey = $this->makeSurvey($admin, $category, $oldTitle, [
            'student_target_mode' => 'classes',
        ]);
        $survey->targetClasses()->sync([$oldClass->id]);

        DB::listen(function ($query): void {
            if (str_starts_with(strtolower($query->sql), 'insert into')
                && str_contains($query->sql, 'survey_target_classes')) {
                throw new RuntimeException('Simulasi kegagalan pivot kelas saat update.');
            }
        });

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($admin)->put('/surveys/'.$survey->id, $this->payload($category, 'Judul Baru Rollback '.uniqid(), [
                'student_target_mode' => 'classes',
                'target_class_ids' => [$newClass->id],
            ]));

            $this->fail('Penyimpanan pivot kelas seharusnya gagal.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulasi kegagalan pivot kelas saat update.', $exception->getMessage());
        }

        $survey->refresh();

        $this->assertSame($oldTitle, $survey->title);
        $this->assertDatabaseHas('survey_target_classes', ['survey_id' => $survey->id, 'class_id' => $oldClass->id]);
        $this->assertDatabaseMissing('survey_target_classes', ['survey_id' => $survey->id, 'class_id' => $newClass->id]);
    }

    public function test_update_rolls_back_when_teacher_pivot_storage_fails(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $teacher = $this->teacherUser()->teacher;
        $oldTitle = 'Judul Sebelum Rollback Guru '.uniqid();
        $survey = $this->makeSurvey($admin, $category, $oldTitle);

        DB::listen(function ($query): void {
            if (str_starts_with(strtolower($query->sql), 'insert into')
                && str_contains($query->sql, 'survey_target_teachers')) {
                throw new RuntimeException('Simulasi kegagalan pivot guru saat update.');
            }
        });

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($admin)->put('/surveys/'.$survey->id, $this->payload($category, 'Judul Baru Rollback Guru '.uniqid(), [
                'teacher_target_mode' => 'selected',
                'target_teacher_ids' => [$teacher->id],
            ]));

            $this->fail('Penyimpanan pivot guru seharusnya gagal.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulasi kegagalan pivot guru saat update.', $exception->getMessage());
        }

        $survey->refresh();

        $this->assertSame($oldTitle, $survey->title);
        $this->assertSame('none', $survey->teacher_target_mode);
        $this->assertSame(0, DB::table('survey_target_teachers')->where('survey_id', $survey->id)->count());
    }

    public function test_protected_fields_cannot_be_manipulated_on_update(): void
    {
        $teacher = $this->teacherUser();
        $admin = $this->admin();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($teacher, $category, 'Field Terlindungi '.uniqid());

        $this->actingAs($admin)
            ->put('/surveys/'.$survey->id, $this->payload($category, $survey->title, [
                'creator_id' => $admin->id,
                'status' => 'closed',
                'is_result_published' => true,
                'starts_at' => '2026-11-01 08:00:00',
                'ends_at' => '2026-11-30 17:00:00',
            ]))
            ->assertRedirect('/surveys');

        $survey->refresh();

        $this->assertSame($teacher->id, $survey->creator_id);
        $this->assertSame('draft', $survey->status);
        $this->assertFalse($survey->is_result_published);
        $this->assertNull($survey->starts_at);
        $this->assertNull($survey->ends_at);
    }

    public function test_invalid_classes_and_teachers_are_rejected_on_update(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $class = $this->makeClass();
        $teacher = $this->teacherUser()->teacher;
        $inactiveClass = $this->makeClass(false);
        $inactiveTeacher = $this->teacherUser(true, false)->teacher;
        $survey = $this->makeSurvey($admin, $category, 'Target Lama '.uniqid(), [
            'student_target_mode' => 'classes',
            'teacher_target_mode' => 'selected',
        ]);
        $survey->targetClasses()->sync([$class->id]);
        $survey->targetTeachers()->sync([$teacher->id]);

        $this->actingAs($admin)
            ->put('/surveys/'.$survey->id, $this->payload($category, $survey->title, [
                'student_target_mode' => 'classes',
                'target_class_ids' => [$inactiveClass->id],
            ]))
            ->assertSessionHasErrors();

        $this->actingAs($admin)
            ->put('/surveys/'.$survey->id, $this->payload($category, $survey->title, [
                'teacher_target_mode' => 'selected',
                'target_teacher_ids' => [$inactiveTeacher->id],
            ]))
            ->assertSessionHasErrors();

        $survey->refresh();

        $this->assertSame('classes', $survey->student_target_mode);
        $this->assertSame('selected', $survey->teacher_target_mode);
        $this->assertDatabaseHas('survey_target_classes', ['survey_id' => $survey->id, 'class_id' => $class->id]);
        $this->assertDatabaseHas('survey_target_teachers', ['survey_id' => $survey->id, 'teacher_id' => $teacher->id]);
    }

    public function test_edit_form_prefills_saved_values_and_targets(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $class = $this->makeClass();
        $teacher = $this->teacherUser()->teacher;
        $title = 'Form Terisi '.uniqid();
        $survey = $this->makeSurvey($admin, $category, $title, [
            'description' => 'Deskripsi terisi dari database.',
            'student_target_mode' => 'classes',
            'teacher_target_mode' => 'selected',
        ]);
        $survey->targetClasses()->sync([$class->id]);
        $survey->targetTeachers()->sync([$teacher->id]);

        $response = $this->actingAs($admin)
            ->get('/surveys/'.$survey->id.'/edit')
            ->assertOk()
            ->assertSee($title)
            ->assertSee('Deskripsi terisi dari database.')
            ->assertSee($category->name)
            ->assertSee($class->name)
            ->assertSee($teacher->name)
            ->assertSee('Simpan Perubahan')
            ->assertSee(route('surveys.index'));

        $html = $response->getContent();

        $this->assertMatchesRegularExpression('/name="student_target_mode" value="classes"[^>]*checked/', $html);
        $this->assertMatchesRegularExpression('/name="teacher_target_mode" value="selected"[^>]*checked/', $html);
        $this->assertMatchesRegularExpression('/name="target_class_ids\[\]" value="'.$class->id.'"[^>]*checked/', $html);
        $this->assertMatchesRegularExpression('/name="target_teacher_ids\[\]" value="'.$teacher->id.'"[^>]*checked/', $html);
    }

    public function test_edit_link_only_shown_for_manageable_draft_surveys(): void
    {
        $admin = $this->admin();
        $owner = $this->teacherUser();
        $other = $this->teacherUser();
        $category = $this->makeCategory();
        $draft = $this->makeSurvey($owner, $category, 'Draft Dengan Edit '.uniqid());
        $published = $this->makeSurvey($owner, $category, 'Published Tanpa Edit '.uniqid(), ['status' => 'published']);

        $this->actingAs($admin)
            ->get('/surveys?search='.urlencode('Edit'))
            ->assertOk()
            ->assertSee(route('surveys.edit', $draft))
            ->assertDontSee(route('surveys.edit', $published));

        $this->actingAs($owner)
            ->get('/surveys')
            ->assertOk()
            ->assertSee(route('surveys.edit', $draft));

        $this->actingAs($other)
            ->get('/surveys')
            ->assertOk()
            ->assertDontSee(route('surveys.edit', $draft));
    }

    public function test_update_without_student_target_mode_is_rejected_and_keeps_data(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $class = $this->makeClass();
        $teacher = $this->teacherUser()->teacher;
        $title = 'Tanpa Mode Siswa '.uniqid();
        $survey = $this->makeSurvey($admin, $category, $title, [
            'description' => 'Deskripsi asli.',
            'student_target_mode' => 'classes',
            'teacher_target_mode' => 'selected',
        ]);
        $survey->targetClasses()->sync([$class->id]);
        $survey->targetTeachers()->sync([$teacher->id]);

        $this->actingAs($admin)
            ->put('/surveys/'.$survey->id, [
                'title' => 'Judul Baru Tanpa Mode Siswa '.uniqid(),
                'description' => 'Deskripsi baru.',
                'category_id' => $category->id,
                'teacher_target_mode' => 'selected',
                'target_teacher_ids' => [$teacher->id],
            ])
            ->assertSessionHasErrors('student_target_mode');

        $survey->refresh();

        $this->assertSame($title, $survey->title);
        $this->assertSame('Deskripsi asli.', $survey->description);
        $this->assertSame('classes', $survey->student_target_mode);
        $this->assertSame('selected', $survey->teacher_target_mode);
        $this->assertDatabaseHas('survey_target_classes', ['survey_id' => $survey->id, 'class_id' => $class->id]);
        $this->assertDatabaseHas('survey_target_teachers', ['survey_id' => $survey->id, 'teacher_id' => $teacher->id]);
        $this->assertSame(1, DB::table('survey_target_classes')->where('survey_id', $survey->id)->count());
        $this->assertSame(1, DB::table('survey_target_teachers')->where('survey_id', $survey->id)->count());
    }

    public function test_update_without_teacher_target_mode_is_rejected_and_keeps_data(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $class = $this->makeClass();
        $teacher = $this->teacherUser()->teacher;
        $title = 'Tanpa Mode Guru '.uniqid();
        $survey = $this->makeSurvey($admin, $category, $title, [
            'description' => 'Deskripsi asli.',
            'student_target_mode' => 'classes',
            'teacher_target_mode' => 'selected',
        ]);
        $survey->targetClasses()->sync([$class->id]);
        $survey->targetTeachers()->sync([$teacher->id]);

        $this->actingAs($admin)
            ->put('/surveys/'.$survey->id, [
                'title' => 'Judul Baru Tanpa Mode Guru '.uniqid(),
                'description' => 'Deskripsi baru.',
                'category_id' => $category->id,
                'student_target_mode' => 'classes',
                'target_class_ids' => [$class->id],
            ])
            ->assertSessionHasErrors('teacher_target_mode');

        $survey->refresh();

        $this->assertSame($title, $survey->title);
        $this->assertSame('Deskripsi asli.', $survey->description);
        $this->assertSame('classes', $survey->student_target_mode);
        $this->assertSame('selected', $survey->teacher_target_mode);
        $this->assertDatabaseHas('survey_target_classes', ['survey_id' => $survey->id, 'class_id' => $class->id]);
        $this->assertDatabaseHas('survey_target_teachers', ['survey_id' => $survey->id, 'teacher_id' => $teacher->id]);
        $this->assertSame(1, DB::table('survey_target_classes')->where('survey_id', $survey->id)->count());
        $this->assertSame(1, DB::table('survey_target_teachers')->where('survey_id', $survey->id)->count());
    }

    public function test_update_without_both_target_modes_is_rejected_and_keeps_data(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $class = $this->makeClass();
        $teacher = $this->teacherUser()->teacher;
        $title = 'Tanpa Kedua Mode '.uniqid();
        $survey = $this->makeSurvey($admin, $category, $title, [
            'description' => 'Deskripsi asli.',
            'student_target_mode' => 'classes',
            'teacher_target_mode' => 'selected',
        ]);
        $survey->targetClasses()->sync([$class->id]);
        $survey->targetTeachers()->sync([$teacher->id]);

        $this->actingAs($admin)
            ->put('/surveys/'.$survey->id, [
                'title' => 'Judul Baru Tanpa Kedua Mode '.uniqid(),
                'description' => 'Deskripsi baru.',
                'category_id' => $category->id,
            ])
            ->assertSessionHasErrors(['student_target_mode', 'teacher_target_mode']);

        $survey->refresh();

        $this->assertSame($title, $survey->title);
        $this->assertSame('Deskripsi asli.', $survey->description);
        $this->assertSame('classes', $survey->student_target_mode);
        $this->assertSame('selected', $survey->teacher_target_mode);
        $this->assertDatabaseHas('survey_target_classes', ['survey_id' => $survey->id, 'class_id' => $class->id]);
        $this->assertDatabaseHas('survey_target_teachers', ['survey_id' => $survey->id, 'teacher_id' => $teacher->id]);
        $this->assertSame(1, DB::table('survey_target_classes')->where('survey_id', $survey->id)->count());
        $this->assertSame(1, DB::table('survey_target_teachers')->where('survey_id', $survey->id)->count());
    }

    public function test_update_with_both_target_modes_is_accepted(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $oldClass = $this->makeClass();
        $newClass = $this->makeClass();
        $oldTeacher = $this->teacherUser()->teacher;
        $newTeacher = $this->teacherUser()->teacher;
        $survey = $this->makeSurvey($admin, $category, 'Mode Eksplisit '.uniqid(), [
            'student_target_mode' => 'classes',
            'teacher_target_mode' => 'selected',
        ]);
        $survey->targetClasses()->sync([$oldClass->id]);
        $survey->targetTeachers()->sync([$oldTeacher->id]);

        $this->actingAs($admin)
            ->put('/surveys/'.$survey->id, [
                'title' => $survey->title,
                'description' => 'Deskripsi diperbarui.',
                'category_id' => $category->id,
                'student_target_mode' => 'classes',
                'target_class_ids' => [$newClass->id],
                'teacher_target_mode' => 'selected',
                'target_teacher_ids' => [$newTeacher->id],
            ])
            ->assertRedirect('/surveys');

        $survey->refresh();

        $this->assertSame('classes', $survey->student_target_mode);
        $this->assertSame('selected', $survey->teacher_target_mode);
        $this->assertDatabaseHas('survey_target_classes', ['survey_id' => $survey->id, 'class_id' => $newClass->id]);
        $this->assertDatabaseHas('survey_target_teachers', ['survey_id' => $survey->id, 'teacher_id' => $newTeacher->id]);
        $this->assertDatabaseMissing('survey_target_classes', ['survey_id' => $survey->id, 'class_id' => $oldClass->id]);
        $this->assertDatabaseMissing('survey_target_teachers', ['survey_id' => $survey->id, 'teacher_id' => $oldTeacher->id]);
    }

    public function test_create_without_target_modes_defaults_to_none(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $title = 'Create Tanpa Mode '.uniqid();

        $this->actingAs($admin)
            ->post('/surveys', [
                'title' => $title,
                'description' => 'Deskripsi create.',
                'category_id' => $category->id,
            ])
            ->assertRedirect('/surveys');

        $survey = Survey::where('title', $title)->firstOrFail();

        $this->assertSame('none', $survey->student_target_mode);
        $this->assertSame('none', $survey->teacher_target_mode);
        $this->assertSame(0, DB::table('survey_target_classes')->where('survey_id', $survey->id)->count());
        $this->assertSame(0, DB::table('survey_target_teachers')->where('survey_id', $survey->id)->count());
    }

    public function test_admin_can_delete_own_draft_survey(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Draft Admin Hapus '.uniqid());

        $this->actingAs($admin)
            ->delete('/surveys/'.$survey->id)
            ->assertRedirect('/surveys')
            ->assertSessionHas('status', 'Survei berhasil dihapus.');

        $this->assertDatabaseMissing('surveys', ['id' => $survey->id]);
    }

    public function test_admin_can_delete_teachers_draft_survey(): void
    {
        $admin = $this->admin();
        $teacher = $this->teacherUser();
        $student = $this->student();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($teacher, $category, 'Draft Guru Hapus Admin '.uniqid());
        $graph = $this->makeResponseGraph($survey, $student);

        $this->actingAs($admin)
            ->delete('/surveys/'.$survey->id)
            ->assertRedirect('/surveys')
            ->assertSessionHas('status', 'Survei berhasil dihapus.');

        $this->assertDatabaseMissing('surveys', ['id' => $survey->id]);
        $this->assertDatabaseMissing('responses', ['id' => $graph['response']->id]);
        $this->assertDatabaseMissing('answers', ['id' => $graph['answer']->id]);
        $this->assertDatabaseMissing('answer_options', ['answer_id' => $graph['answer']->id]);
    }

    public function test_teacher_can_delete_own_draft_survey(): void
    {
        $teacher = $this->teacherUser();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($teacher, $category, 'Draft Guru Hapus '.uniqid());

        $this->actingAs($teacher)
            ->delete('/surveys/'.$survey->id)
            ->assertRedirect('/surveys')
            ->assertSessionHas('status', 'Survei berhasil dihapus.');

        $this->assertDatabaseMissing('surveys', ['id' => $survey->id]);
    }

    public function test_teacher_cannot_delete_another_teachers_draft_survey(): void
    {
        $owner = $this->teacherUser();
        $other = $this->teacherUser();
        $student = $this->student();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($owner, $category, 'Draft Guru Lain '.uniqid());
        $graph = $this->makeResponseGraph($survey, $student);

        $this->actingAs($other)
            ->delete('/surveys/'.$survey->id)
            ->assertForbidden();

        $this->assertDatabaseHas('surveys', ['id' => $survey->id, 'creator_id' => $owner->id, 'status' => 'draft']);
        $this->assertDatabaseHas('responses', ['id' => $graph['response']->id]);
        $this->assertDatabaseHas('answers', ['id' => $graph['answer']->id]);
    }

    public function test_teacher_cannot_delete_published_or_closed_survey(): void
    {
        $teacher = $this->teacherUser();
        $student = $this->student();
        $category = $this->makeCategory();
        $published = $this->makeSurvey($teacher, $category, 'Draft Guru Published '.uniqid(), ['status' => 'published']);
        $closed = $this->makeSurvey($teacher, $category, 'Draft Guru Closed '.uniqid(), ['status' => 'closed']);
        $publishedGraph = $this->makeResponseGraph($published, $student);
        $closedGraph = $this->makeResponseGraph($closed, $student);

        $this->actingAs($teacher)->delete('/surveys/'.$published->id)->assertForbidden();
        $this->actingAs($teacher)->delete('/surveys/'.$closed->id)->assertForbidden();

        $this->assertDatabaseHas('surveys', ['id' => $published->id, 'status' => 'published']);
        $this->assertDatabaseHas('surveys', ['id' => $closed->id, 'status' => 'closed']);
        $this->assertDatabaseHas('responses', ['id' => $publishedGraph['response']->id]);
        $this->assertDatabaseHas('answers', ['id' => $publishedGraph['answer']->id]);
        $this->assertDatabaseHas('responses', ['id' => $closedGraph['response']->id]);
        $this->assertDatabaseHas('answers', ['id' => $closedGraph['answer']->id]);
    }

    public function test_admin_cannot_delete_published_or_closed_survey(): void
    {
        $admin = $this->admin();
        $teacher = $this->teacherUser();
        $student = $this->student();
        $category = $this->makeCategory();
        $published = $this->makeSurvey($teacher, $category, 'Admin Published '.uniqid(), ['status' => 'published']);
        $closed = $this->makeSurvey($teacher, $category, 'Admin Closed '.uniqid(), ['status' => 'closed']);
        $publishedGraph = $this->makeResponseGraph($published, $student);
        $closedGraph = $this->makeResponseGraph($closed, $student);

        $this->actingAs($admin)->delete('/surveys/'.$published->id)->assertForbidden();
        $this->actingAs($admin)->delete('/surveys/'.$closed->id)->assertForbidden();

        $this->assertDatabaseHas('surveys', ['id' => $published->id, 'status' => 'published']);
        $this->assertDatabaseHas('surveys', ['id' => $closed->id, 'status' => 'closed']);
        $this->assertDatabaseHas('responses', ['id' => $publishedGraph['response']->id]);
        $this->assertDatabaseHas('answers', ['id' => $publishedGraph['answer']->id]);
        $this->assertDatabaseHas('responses', ['id' => $closedGraph['response']->id]);
        $this->assertDatabaseHas('answers', ['id' => $closedGraph['answer']->id]);
    }

    public function test_student_cannot_delete_draft_survey(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Draft Dengan Siswa '.uniqid());

        $this->actingAs($student)
            ->delete('/surveys/'.$survey->id)
            ->assertForbidden();

        $this->assertDatabaseHas('surveys', ['id' => $survey->id]);
    }

    public function test_guest_is_redirected_to_login_when_deleting_survey(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Draft Dengan Guest '.uniqid());

        $this->delete('/surveys/'.$survey->id)->assertRedirect('/login');

        $this->assertDatabaseHas('surveys', ['id' => $survey->id]);
    }

    public function test_teacher_without_profile_cannot_delete_draft_survey(): void
    {
        $teacher = $this->teacherUser(withProfile: false);
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($teacher, $category, 'Draft Tanpa Profil '.uniqid());

        $this->actingAs($teacher)
            ->delete('/surveys/'.$survey->id)
            ->assertForbidden();

        $this->assertDatabaseHas('surveys', ['id' => $survey->id]);
    }

    public function test_delete_removes_survey_and_descendants(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $class = $this->makeClass();
        $teacher = $this->teacherUser()->teacher;
        $survey = $this->makeSurvey($admin, $category, 'Draft Dengan Keturunan '.uniqid());
        $survey->targetClasses()->sync([$class->id]);
        $survey->targetTeachers()->sync([$teacher->id]);

        $question = Question::create([
            'survey_id' => $survey->id,
            'question_text' => 'Pertanyaan uji?',
            'type' => 'single_choice',
            'is_required' => true,
            'position' => 0,
        ]);
        $option = QuestionOption::create([
            'question_id' => $question->id,
            'option_text' => 'Pilihan uji',
            'position' => 0,
        ]);

        $this->actingAs($admin)
            ->delete('/surveys/'.$survey->id)
            ->assertRedirect('/surveys')
            ->assertSessionHas('status', 'Survei berhasil dihapus.');

        $this->assertDatabaseMissing('surveys', ['id' => $survey->id]);
        $this->assertDatabaseMissing('survey_target_classes', ['survey_id' => $survey->id]);
        $this->assertDatabaseMissing('survey_target_teachers', ['survey_id' => $survey->id]);
        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
        $this->assertDatabaseMissing('question_options', ['id' => $option->id]);
    }

    public function test_delete_removes_responses_answers_and_answer_options(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Draft Dengan Jawaban '.uniqid());
        $graph = $this->makeResponseGraph($survey, $student);

        $this->actingAs($admin)
            ->delete('/surveys/'.$survey->id)
            ->assertRedirect('/surveys')
            ->assertSessionHas('status', 'Survei berhasil dihapus.');

        $this->assertDatabaseMissing('surveys', ['id' => $survey->id]);
        $this->assertDatabaseMissing('questions', ['id' => $graph['question']->id]);
        $this->assertDatabaseMissing('question_options', ['id' => $graph['option']->id]);
        $this->assertDatabaseMissing('responses', ['id' => $graph['response']->id]);
        $this->assertDatabaseMissing('answers', ['id' => $graph['answer']->id]);
        $this->assertDatabaseMissing('answer_options', ['answer_id' => $graph['answer']->id]);
    }

    public function test_delete_removes_answers_without_answer_options(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Draft Jawaban Teks '.uniqid());
        $graph = $this->makeResponseGraph($survey, $student, withAnswerOption: false);

        $this->assertDatabaseHas('answers', ['id' => $graph['answer']->id]);

        $this->actingAs($admin)
            ->delete('/surveys/'.$survey->id)
            ->assertRedirect('/surveys')
            ->assertSessionHas('status', 'Survei berhasil dihapus.');

        $this->assertDatabaseMissing('surveys', ['id' => $survey->id]);
        $this->assertDatabaseMissing('responses', ['id' => $graph['response']->id]);
        $this->assertDatabaseMissing('answers', ['id' => $graph['answer']->id]);
    }

    public function test_delete_removes_answer_options_linked_by_question_or_option(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Draft Referensi Silang '.uniqid());
        $graph = $this->makeResponseGraph($survey, $student, withAnswerOption: false);

        $otherSurvey = $this->makeSurvey($admin, $category, 'Survei Lain Referensi '.uniqid());
        $otherResponse = Response::create([
            'survey_id' => $otherSurvey->id,
            'user_id' => $student->id,
            'submitted_at' => now(),
        ]);
        $crossAnswer = Answer::create([
            'response_id' => $otherResponse->id,
            'question_id' => $graph['question']->id,
            'answer_text' => null,
        ]);
        AnswerOption::create([
            'answer_id' => $crossAnswer->id,
            'question_id' => $graph['question']->id,
            'option_id' => $graph['option']->id,
        ]);

        $this->actingAs($admin)
            ->delete('/surveys/'.$survey->id)
            ->assertRedirect('/surveys');

        $this->assertDatabaseMissing('surveys', ['id' => $survey->id]);
        $this->assertDatabaseMissing('question_options', ['id' => $graph['option']->id]);
        $this->assertDatabaseMissing('answers', ['id' => $crossAnswer->id]);
        $this->assertDatabaseMissing('answer_options', ['answer_id' => $crossAnswer->id]);
        $this->assertDatabaseHas('surveys', ['id' => $otherSurvey->id]);
        $this->assertDatabaseHas('responses', ['id' => $otherResponse->id]);
    }

    public function test_delete_does_not_remove_other_survey_data(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Draft Yang Dihapus '.uniqid());
        $graph = $this->makeResponseGraph($survey, $student);

        $otherSurvey = $this->makeSurvey($admin, $category, 'Draft Yang Dipertahankan '.uniqid());
        $otherGraph = $this->makeResponseGraph($otherSurvey, $student);

        $this->actingAs($admin)
            ->delete('/surveys/'.$survey->id)
            ->assertRedirect('/surveys');

        $this->assertDatabaseMissing('surveys', ['id' => $survey->id]);
        $this->assertDatabaseMissing('questions', ['id' => $graph['question']->id]);
        $this->assertDatabaseHas('surveys', ['id' => $otherSurvey->id]);
        $this->assertDatabaseHas('questions', ['id' => $otherGraph['question']->id]);
        $this->assertDatabaseHas('question_options', ['id' => $otherGraph['option']->id]);
        $this->assertDatabaseHas('responses', ['id' => $otherGraph['response']->id]);
        $this->assertDatabaseHas('answers', ['id' => $otherGraph['answer']->id]);
        $this->assertDatabaseHas('answer_options', ['answer_id' => $otherGraph['answer']->id]);
    }

    public function test_delete_rolls_back_all_changes_when_transaction_fails(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Draft Rollback '.uniqid());
        $graph = $this->makeResponseGraph($survey, $student);

        DB::listen(function ($query): void {
            if (str_contains($query->sql, 'delete from `questions`')) {
                throw new RuntimeException('forced rollback');
            }
        });

        $this->withoutExceptionHandling();

        $caught = null;

        try {
            $this->actingAs($admin)->delete('/surveys/'.$survey->id);
        } catch (RuntimeException $e) {
            $caught = $e;
        }

        $this->assertNotNull($caught);
        $this->assertSame('forced rollback', $caught->getMessage());
        $this->assertDatabaseHas('surveys', ['id' => $survey->id, 'status' => 'draft']);
        $this->assertDatabaseHas('questions', ['id' => $graph['question']->id]);
        $this->assertDatabaseHas('question_options', ['id' => $graph['option']->id]);
        $this->assertDatabaseHas('responses', ['id' => $graph['response']->id]);
        $this->assertDatabaseHas('answers', ['id' => $graph['answer']->id]);
        $this->assertDatabaseHas('answer_options', ['answer_id' => $graph['answer']->id]);
    }

    public function test_delete_confirmation_shows_correct_response_count(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $firstStudent = $this->student();
        $secondStudent = $this->student();
        $this->makeSurvey($admin, $category, 'Draft Tanpa Respons '.uniqid());
        $withResponses = $this->makeSurvey($admin, $category, 'Draft Dua Respons '.uniqid());

        Response::create([
            'survey_id' => $withResponses->id,
            'user_id' => $firstStudent->id,
            'submitted_at' => now(),
        ]);
        Response::create([
            'survey_id' => $withResponses->id,
            'user_id' => $secondStudent->id,
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/surveys')
            ->assertOk()
            ->assertSee('Hapus survei ini? Tindakan ini tidak dapat dibatalkan.')
            ->assertSee('Survei ini memiliki 2 respons. Menghapus survei juga akan menghapus seluruh respons dan jawaban terkait. Tindakan ini tidak dapat dibatalkan.');
    }

    public function test_delete_request_cannot_manipulate_ownership_or_status(): void
    {
        $owner = $this->teacherUser();
        $other = $this->teacherUser();
        $admin = $this->admin();
        $category = $this->makeCategory();
        $draft = $this->makeSurvey($owner, $category, 'Draft Manipulasi '.uniqid());
        $published = $this->makeSurvey($owner, $category, 'Published Manipulasi '.uniqid(), ['status' => 'published']);

        $this->actingAs($other)
            ->delete('/surveys/'.$draft->id, ['creator_id' => $other->id, 'status' => 'draft'])
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete('/surveys/'.$published->id, ['status' => 'draft'])
            ->assertForbidden();

        $this->assertDatabaseHas('surveys', ['id' => $draft->id, 'creator_id' => $owner->id, 'status' => 'draft']);
        $this->assertDatabaseHas('surveys', ['id' => $published->id, 'creator_id' => $owner->id, 'status' => 'published']);
    }

    public function test_delete_via_form_method_override_is_consistent_with_direct_request(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Draft Method Override '.uniqid());

        $this->actingAs($admin)
            ->post('/surveys/'.$survey->id, ['_method' => 'DELETE'])
            ->assertRedirect('/surveys')
            ->assertSessionHas('status', 'Survei berhasil dihapus.');

        $this->assertDatabaseMissing('surveys', ['id' => $survey->id]);
    }

    public function test_delete_button_only_shown_for_authorized_drafts(): void
    {
        $admin = $this->admin();
        $owner = $this->teacherUser();
        $other = $this->teacherUser();
        $category = $this->makeCategory();
        $draft = $this->makeSurvey($owner, $category, 'Draft Tombol Hapus '.uniqid());
        $published = $this->makeSurvey($owner, $category, 'Published Tombol Hapus '.uniqid(), ['status' => 'published']);
        $closed = $this->makeSurvey($owner, $category, 'Closed Tombol Hapus '.uniqid(), ['status' => 'closed']);

        $this->actingAs($admin)
            ->get('/surveys')
            ->assertOk()
            ->assertSee(route('surveys.destroy', $draft))
            ->assertDontSee('action="'.route('surveys.destroy', $published).'"', false)
            ->assertDontSee('action="'.route('surveys.destroy', $closed).'"', false);

        $this->actingAs($owner)
            ->get('/surveys')
            ->assertOk()
            ->assertSee(route('surveys.destroy', $draft))
            ->assertSee('Hapus');

        $this->actingAs($other)
            ->get('/surveys')
            ->assertOk()
            ->assertDontSee(route('surveys.destroy', $draft));
    }

    public function test_admin_can_create_question_with_options(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Draft Pertanyaan Admin '.uniqid());
        $text = 'Pilih jawaban '.uniqid();

        $this->actingAs($admin)
            ->post('/surveys/'.$survey->id.'/questions', $this->questionPayload([
                'question_text' => $text,
                'type' => 'multiple_choice',
                'option_texts' => ['Ya', 'Tidak'],
            ]))
            ->assertRedirect(route('surveys.questions.index', $survey))
            ->assertSessionHas('status', 'Pertanyaan berhasil ditambahkan.');

        $question = Question::where('survey_id', $survey->id)->firstOrFail();

        $this->assertSame($text, $question->question_text);
        $this->assertSame('multiple_choice', $question->type);
        $this->assertTrue($question->is_required);
        $this->assertSame(1, $question->position);
        $this->assertSame(['Ya', 'Tidak'], $question->options()->orderBy('position')->pluck('option_text')->all());
        $this->assertSame([1, 2], $question->options()->orderBy('position')->pluck('position')->all());
    }

    public function test_teacher_can_create_question_for_own_draft(): void
    {
        $teacher = $this->teacherUser();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($teacher, $category, 'Draft Pertanyaan Guru '.uniqid());

        $this->actingAs($teacher)
            ->post('/surveys/'.$survey->id.'/questions', $this->questionPayload([
                'question_text' => 'Pertanyaan guru '.uniqid(),
                'type' => 'rating',
            ]))
            ->assertRedirect(route('surveys.questions.index', $survey));

        $this->assertDatabaseHas('questions', ['survey_id' => $survey->id, 'type' => 'rating']);
    }

    public function test_teacher_cannot_manage_another_teachers_questions(): void
    {
        $owner = $this->teacherUser();
        $other = $this->teacherUser();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($owner, $category, 'Draft Pertanyaan Guru Lain '.uniqid());
        $question = $this->makeQuestion($survey);

        $this->actingAs($other)->get('/surveys/'.$survey->id.'/questions')->assertForbidden();
        $this->actingAs($other)->get('/surveys/'.$survey->id.'/questions/'.$question->id.'/edit')->assertForbidden();
        $this->actingAs($other)->put('/surveys/'.$survey->id.'/questions/'.$question->id, $this->questionPayload())->assertForbidden();
        $this->actingAs($other)->delete('/surveys/'.$survey->id.'/questions/'.$question->id)->assertForbidden();

        $this->assertDatabaseHas('questions', ['id' => $question->id, 'question_text' => $question->question_text]);
    }

    public function test_student_cannot_access_question_management(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Draft Pertanyaan Siswa '.uniqid());

        $this->actingAs($student)
            ->get('/surveys/'.$survey->id.'/questions')
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login_for_question_management(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Draft Pertanyaan Guest '.uniqid());

        $this->get('/surveys/'.$survey->id.'/questions')->assertRedirect('/login');
    }

    public function test_teacher_without_profile_cannot_manage_questions(): void
    {
        $teacher = $this->teacherUser(withProfile: false);
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($teacher, $category, 'Draft Pertanyaan Tanpa Profil '.uniqid());

        $this->actingAs($teacher)
            ->get('/surveys/'.$survey->id.'/questions')
            ->assertForbidden();
    }

    public function test_question_text_is_required(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Draft Tanpa Teks '.uniqid());

        $this->actingAs($admin)
            ->post('/surveys/'.$survey->id.'/questions', $this->questionPayload([
                'question_text' => '   ',
            ]))
            ->assertSessionHasErrors('question_text');

        $this->assertSame(0, Question::where('survey_id', $survey->id)->count());
    }

    public function test_invalid_question_type_is_rejected(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Draft Tipe Salah '.uniqid());

        $this->actingAs($admin)
            ->post('/surveys/'.$survey->id.'/questions', $this->questionPayload([
                'type' => 'checkbox',
            ]))
            ->assertSessionHasErrors('type');

        $this->assertSame(0, Question::where('survey_id', $survey->id)->count());
    }

    public function test_empty_option_text_is_rejected(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Draft Opsi Kosong '.uniqid());

        $this->actingAs($admin)
            ->post('/surveys/'.$survey->id.'/questions', $this->questionPayload([
                'type' => 'single_choice',
                'option_texts' => ['Ya', ''],
            ]))
            ->assertSessionHasErrors('option_texts.1');

        $this->assertSame(0, Question::where('survey_id', $survey->id)->count());
    }

    public function test_choice_question_requires_at_least_two_options(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Draft Opsi Kurang '.uniqid());

        $this->actingAs($admin)
            ->post('/surveys/'.$survey->id.'/questions', $this->questionPayload([
                'type' => 'single_choice',
                'option_texts' => ['Hanya satu'],
            ]))
            ->assertSessionHasErrors('option_texts');

        $this->assertSame(0, Question::where('survey_id', $survey->id)->count());
    }

    public function test_question_type_without_options_can_be_saved_without_options(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Draft Tanpa Opsi '.uniqid());

        $this->actingAs($admin)
            ->post('/surveys/'.$survey->id.'/questions', $this->questionPayload([
                'type' => 'long_text',
                'option_texts' => ['Diabaikan', 'Juga diabaikan'],
            ]))
            ->assertRedirect(route('surveys.questions.index', $survey));

        $question = Question::where('survey_id', $survey->id)->firstOrFail();

        $this->assertSame('long_text', $question->type);
        $this->assertSame(0, $question->options()->count());
    }

    public function test_question_can_be_updated_with_replaced_options(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Draft Edit Pertanyaan '.uniqid());
        $question = $this->makeQuestion($survey, ['type' => 'single_choice']);
        $question->options()->create(['option_text' => 'Lama', 'position' => 1]);
        $question->options()->create(['option_text' => 'Lama 2', 'position' => 2]);
        $newText = 'Pertanyaan diperbarui '.uniqid();

        $this->actingAs($admin)
            ->put('/surveys/'.$survey->id.'/questions/'.$question->id, $this->questionPayload([
                'question_text' => $newText,
                'type' => 'multiple_choice',
                'is_required' => '0',
                'option_texts' => ['Baru 1', 'Baru 2', 'Baru 3'],
            ]))
            ->assertRedirect(route('surveys.questions.index', $survey))
            ->assertSessionHas('status', 'Pertanyaan berhasil diperbarui.');

        $question->refresh();

        $this->assertSame($newText, $question->question_text);
        $this->assertSame('multiple_choice', $question->type);
        $this->assertFalse($question->is_required);
        $this->assertSame(['Baru 1', 'Baru 2', 'Baru 3'], $question->options()->orderBy('position')->pluck('option_text')->all());
        $this->assertDatabaseMissing('question_options', ['question_id' => $question->id, 'option_text' => 'Lama']);
    }

    public function test_changing_type_to_text_removes_old_options(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Draft Ganti Tipe '.uniqid());
        $question = $this->makeQuestion($survey, ['type' => 'single_choice']);
        $question->options()->create(['option_text' => 'Opsi lama', 'position' => 1]);
        $question->options()->create(['option_text' => 'Opsi lama 2', 'position' => 2]);

        $this->actingAs($admin)
            ->put('/surveys/'.$survey->id.'/questions/'.$question->id, $this->questionPayload([
                'type' => 'short_text',
                'option_texts' => ['Tetap dikirim', 'Tetap dikirim 2'],
            ]))
            ->assertRedirect(route('surveys.questions.index', $survey));

        $this->assertSame('short_text', $question->refresh()->type);
        $this->assertSame(0, $question->options()->count());
    }

    public function test_question_and_options_can_be_deleted(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Draft Hapus Pertanyaan '.uniqid());
        $question = $this->makeQuestion($survey, ['type' => 'single_choice']);
        $option = $question->options()->create(['option_text' => 'Opsi dihapus', 'position' => 1]);

        $this->actingAs($admin)
            ->delete('/surveys/'.$survey->id.'/questions/'.$question->id)
            ->assertRedirect(route('surveys.questions.index', $survey))
            ->assertSessionHas('status', 'Pertanyaan berhasil dihapus.');

        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
        $this->assertDatabaseMissing('question_options', ['id' => $option->id]);
    }

    public function test_deleting_question_does_not_affect_other_survey(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Draft Hapus Satu '.uniqid());
        $otherSurvey = $this->makeSurvey($admin, $category, 'Draft Tetap Utuh '.uniqid());
        $question = $this->makeQuestion($survey, ['type' => 'single_choice']);
        $question->options()->create(['option_text' => 'Opsi dihapus', 'position' => 1]);
        $otherQuestion = $this->makeQuestion($otherSurvey, ['type' => 'single_choice']);
        $otherOption = $otherQuestion->options()->create(['option_text' => 'Opsi tetap', 'position' => 1]);

        $this->actingAs($admin)
            ->delete('/surveys/'.$survey->id.'/questions/'.$question->id)
            ->assertRedirect();

        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
        $this->assertDatabaseHas('questions', ['id' => $otherQuestion->id]);
        $this->assertDatabaseHas('question_options', ['id' => $otherOption->id]);
    }

    public function test_published_survey_questions_cannot_be_changed(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Published Pertanyaan '.uniqid(), ['status' => 'published']);
        $question = $this->makeQuestion($survey);

        $this->actingAs($admin)->get('/surveys/'.$survey->id.'/questions')->assertForbidden();
        $this->actingAs($admin)->get('/surveys/'.$survey->id.'/questions/create')->assertForbidden();
        $this->actingAs($admin)->post('/surveys/'.$survey->id.'/questions', $this->questionPayload())->assertForbidden();
        $this->actingAs($admin)->put('/surveys/'.$survey->id.'/questions/'.$question->id, $this->questionPayload())->assertForbidden();
        $this->actingAs($admin)->delete('/surveys/'.$survey->id.'/questions/'.$question->id)->assertForbidden();
        $this->actingAs($admin)->patch('/surveys/'.$survey->id.'/questions/'.$question->id.'/move/up')->assertForbidden();

        $this->assertDatabaseHas('questions', ['id' => $question->id, 'question_text' => $question->question_text]);
    }

    public function test_closed_survey_questions_cannot_be_changed(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Closed Pertanyaan '.uniqid(), ['status' => 'closed']);
        $question = $this->makeQuestion($survey);

        $this->actingAs($admin)->get('/surveys/'.$survey->id.'/questions')->assertForbidden();
        $this->actingAs($admin)->put('/surveys/'.$survey->id.'/questions/'.$question->id, $this->questionPayload())->assertForbidden();
        $this->actingAs($admin)->delete('/surveys/'.$survey->id.'/questions/'.$question->id)->assertForbidden();

        $this->assertDatabaseHas('questions', ['id' => $question->id]);
    }

    public function test_option_ids_from_another_survey_cannot_be_manipulated(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Draft Opsi Target '.uniqid());
        $otherSurvey = $this->makeSurvey($admin, $category, 'Draft Opsi Lain '.uniqid());
        $question = $this->makeQuestion($survey, ['type' => 'single_choice']);
        $question->options()->create(['option_text' => 'Opsi target lama', 'position' => 1]);
        $otherQuestion = $this->makeQuestion($otherSurvey, ['type' => 'single_choice']);
        $otherOption = $otherQuestion->options()->create(['option_text' => 'Opsi lain aman', 'position' => 1]);

        $this->actingAs($admin)
            ->put('/surveys/'.$survey->id.'/questions/'.$question->id, $this->questionPayload([
                'type' => 'single_choice',
                'option_texts' => ['Opsi target baru', 'Opsi target baru 2'],
                'option_ids' => [$otherOption->id],
            ]))
            ->assertRedirect(route('surveys.questions.index', $survey));

        $this->assertSame(['Opsi target baru', 'Opsi target baru 2'], $question->options()->orderBy('position')->pluck('option_text')->all());
        $this->assertDatabaseHas('question_options', ['id' => $otherOption->id, 'option_text' => 'Opsi lain aman']);
        $this->assertDatabaseHas('questions', ['id' => $otherQuestion->id]);
    }

    public function test_question_creation_rolls_back_when_option_save_fails(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Draft Rollback Pertanyaan '.uniqid());

        DB::listen(function ($query): void {
            if (str_contains($query->sql, 'insert into `question_options`')) {
                throw new RuntimeException('forced option failure');
            }
        });

        $this->withoutExceptionHandling();

        $caught = null;

        try {
            $this->actingAs($admin)->post('/surveys/'.$survey->id.'/questions', $this->questionPayload([
                'type' => 'single_choice',
                'option_texts' => ['Satu', 'Dua'],
            ]));
        } catch (RuntimeException $e) {
            $caught = $e;
        }

        $this->assertNotNull($caught);
        $this->assertSame('forced option failure', $caught->getMessage());
        $this->assertSame(0, Question::where('survey_id', $survey->id)->count());
        $this->assertSame(0, DB::table('question_options')->whereIn(
            'question_id',
            DB::table('questions')->select('id')->where('survey_id', $survey->id),
        )->count());
    }

    public function test_question_ordering_can_be_changed(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Draft Urutan Pertanyaan '.uniqid());
        $first = $this->makeQuestion($survey, ['question_text' => 'Pertanyaan A '.uniqid(), 'position' => 1]);
        $second = $this->makeQuestion($survey, ['question_text' => 'Pertanyaan B '.uniqid(), 'position' => 2]);
        $third = $this->makeQuestion($survey, ['question_text' => 'Pertanyaan C '.uniqid(), 'position' => 3]);

        $this->actingAs($admin)
            ->patch('/surveys/'.$survey->id.'/questions/'.$third->id.'/move/up')
            ->assertRedirect(route('surveys.questions.index', $survey));

        $this->assertSame(1, $first->refresh()->position);
        $this->assertSame(2, $third->refresh()->position);
        $this->assertSame(3, $second->refresh()->position);

        $this->actingAs($admin)
            ->get('/surveys/'.$survey->id.'/questions')
            ->assertOk()
            ->assertSeeInOrder([$first->question_text, $third->question_text, $second->question_text]);

        $this->actingAs($admin)
            ->patch('/surveys/'.$survey->id.'/questions/'.$first->id.'/move/down')
            ->assertRedirect();

        $this->assertSame(1, $third->refresh()->position);
        $this->assertSame(2, $first->refresh()->position);
        $this->assertSame(3, $second->refresh()->position);
    }

    public function test_question_from_another_survey_returns_not_found(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Draft Survei A '.uniqid());
        $otherSurvey = $this->makeSurvey($admin, $category, 'Draft Survei B '.uniqid());
        $otherQuestion = $this->makeQuestion($otherSurvey);

        $this->actingAs($admin)
            ->get('/surveys/'.$survey->id.'/questions/'.$otherQuestion->id.'/edit')
            ->assertNotFound();

        $this->actingAs($admin)
            ->put('/surveys/'.$survey->id.'/questions/'.$otherQuestion->id, $this->questionPayload())
            ->assertNotFound();

        $this->assertDatabaseHas('questions', ['id' => $otherQuestion->id]);
    }

    public function test_question_index_shows_survey_status_type_and_option_count(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Draft Tampilan Pertanyaan '.uniqid());
        $question = $this->makeQuestion($survey, ['type' => 'multiple_choice']);
        $question->options()->create(['option_text' => 'Opsi A', 'position' => 1]);
        $question->options()->create(['option_text' => 'Opsi B', 'position' => 2]);

        $this->actingAs($admin)
            ->get('/surveys/'.$survey->id.'/questions')
            ->assertOk()
            ->assertSee($survey->title)
            ->assertSee('Draft')
            ->assertSee($question->question_text)
            ->assertSee('Pilihan ganda')
            ->assertSee('2 opsi')
            ->assertSee(route('surveys.index'));
    }

    public function test_admin_can_publish_own_draft(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $class = $this->makeClass();
        $survey = $this->makePublishableSurvey($admin, $category, $class);

        $this->actingAs($admin)
            ->patch('/surveys/'.$survey->id.'/publish', $this->publishPayload())
            ->assertRedirect(route('surveys.index'))
            ->assertSessionHas('status', 'Survei berhasil dipublikasikan.');

        $survey->refresh();

        $this->assertSame('published', $survey->status);
        $this->assertNotNull($survey->starts_at);
        $this->assertNotNull($survey->ends_at);
        $this->assertFalse($survey->is_result_published);
    }

    public function test_admin_can_publish_teacher_draft(): void
    {
        $admin = $this->admin();
        $teacher = $this->teacherUser();
        $category = $this->makeCategory();
        $class = $this->makeClass();
        $this->makeAssignment($teacher->teacher, $class);
        $survey = $this->makePublishableSurvey($teacher, $category, $class);

        $this->actingAs($admin)
            ->patch('/surveys/'.$survey->id.'/publish', $this->publishPayload())
            ->assertRedirect(route('surveys.index'));

        $this->assertSame('published', $survey->refresh()->status);
    }

    public function test_teacher_can_publish_own_draft(): void
    {
        $teacher = $this->teacherUser();
        $category = $this->makeCategory();
        $class = $this->makeClass();
        $this->makeAssignment($teacher->teacher, $class);
        $survey = $this->makePublishableSurvey($teacher, $category, $class);

        $this->actingAs($teacher)
            ->patch('/surveys/'.$survey->id.'/publish', $this->publishPayload())
            ->assertRedirect(route('surveys.index'));

        $this->assertSame('published', $survey->refresh()->status);
    }

    public function test_teacher_cannot_publish_another_teachers_draft(): void
    {
        $owner = $this->teacherUser();
        $other = $this->teacherUser();
        $category = $this->makeCategory();
        $class = $this->makeClass();
        $this->makeAssignment($owner->teacher, $class);
        $survey = $this->makePublishableSurvey($owner, $category, $class);

        $this->actingAs($other)
            ->patch('/surveys/'.$survey->id.'/publish', $this->publishPayload())
            ->assertForbidden();

        $this->assertSame('draft', $survey->refresh()->status);
    }

    public function test_student_cannot_publish_survey(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $category = $this->makeCategory();
        $class = $this->makeClass();
        $survey = $this->makePublishableSurvey($admin, $category, $class);

        $this->actingAs($student)
            ->patch('/surveys/'.$survey->id.'/publish', $this->publishPayload())
            ->assertForbidden();

        $this->assertSame('draft', $survey->refresh()->status);
    }

    public function test_guest_is_redirected_to_login_when_publishing(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $class = $this->makeClass();
        $survey = $this->makePublishableSurvey($admin, $category, $class);

        $this->patch('/surveys/'.$survey->id.'/publish', $this->publishPayload())
            ->assertRedirect('/login');
    }

    public function test_teacher_without_profile_cannot_publish(): void
    {
        $teacher = $this->teacherUser(withProfile: false);
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($teacher, $category, 'Draft Tanpa Profil Publish '.uniqid());
        $this->makeQuestion($survey);

        $this->actingAs($teacher)
            ->patch('/surveys/'.$survey->id.'/publish', $this->publishPayload())
            ->assertForbidden();

        $this->assertSame('draft', $survey->refresh()->status);
    }

    public function test_draft_without_questions_cannot_be_published(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $class = $this->makeClass();
        $survey = $this->makeSurvey($admin, $category, 'Draft Tanpa Pertanyaan '.uniqid(), [
            'student_target_mode' => 'classes',
        ]);
        $survey->targetClasses()->attach($class->id);

        $this->actingAs($admin)
            ->patch('/surveys/'.$survey->id.'/publish', $this->publishPayload())
            ->assertSessionHasErrors();

        $this->assertSame('draft', $survey->refresh()->status);
        $this->assertNull($survey->starts_at);
    }

    public function test_choice_question_with_less_than_two_options_cannot_be_published(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $class = $this->makeClass();
        $survey = $this->makeSurvey($admin, $category, 'Draft Opsi Kurang '.uniqid(), [
            'student_target_mode' => 'classes',
        ]);
        $survey->targetClasses()->attach($class->id);

        $question = $this->makeQuestion($survey, ['type' => 'single_choice']);
        $question->options()->create(['option_text' => 'Hanya satu', 'position' => 1]);

        $this->actingAs($admin)
            ->patch('/surveys/'.$survey->id.'/publish', $this->publishPayload())
            ->assertSessionHasErrors();

        $this->assertSame('draft', $survey->refresh()->status);
    }

    public function test_valid_choice_question_can_be_published(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $class = $this->makeClass();
        $survey = $this->makeSurvey($admin, $category, 'Draft Opsi Valid '.uniqid(), [
            'student_target_mode' => 'classes',
        ]);
        $survey->targetClasses()->attach($class->id);

        $question = $this->makeQuestion($survey, ['type' => 'multiple_choice']);
        $question->options()->create(['option_text' => 'Pilihan A', 'position' => 1]);
        $question->options()->create(['option_text' => 'Pilihan B', 'position' => 2]);

        $this->actingAs($admin)
            ->patch('/surveys/'.$survey->id.'/publish', $this->publishPayload())
            ->assertRedirect(route('surveys.index'));

        $this->assertSame('published', $survey->refresh()->status);
    }

    public function test_both_target_modes_none_cannot_be_published(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Draft Target Kosong '.uniqid());
        $this->makeQuestion($survey);

        $this->actingAs($admin)
            ->patch('/surveys/'.$survey->id.'/publish', $this->publishPayload())
            ->assertSessionHasErrors();

        $this->assertSame('draft', $survey->refresh()->status);
    }

    public function test_classes_mode_without_pivot_cannot_be_published(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Draft Kelas Kosong '.uniqid(), [
            'student_target_mode' => 'classes',
        ]);
        $this->makeQuestion($survey);

        $this->actingAs($admin)
            ->patch('/surveys/'.$survey->id.'/publish', $this->publishPayload())
            ->assertSessionHasErrors();

        $this->assertSame('draft', $survey->refresh()->status);
    }

    public function test_inactive_target_class_cannot_be_published(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $class = $this->makeClass(active: false);
        $survey = $this->makeSurvey($admin, $category, 'Draft Kelas Nonaktif '.uniqid(), [
            'student_target_mode' => 'classes',
        ]);
        $survey->targetClasses()->attach($class->id);
        $this->makeQuestion($survey);

        $this->actingAs($admin)
            ->patch('/surveys/'.$survey->id.'/publish', $this->publishPayload())
            ->assertSessionHasErrors();

        $this->assertSame('draft', $survey->refresh()->status);
    }

    public function test_inactive_target_teacher_cannot_be_published(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $class = $this->makeClass();
        $inactiveTeacherUser = $this->teacherUser(active: false);
        $survey = $this->makeSurvey($admin, $category, 'Draft Guru Nonaktif '.uniqid(), [
            'student_target_mode' => 'classes',
            'teacher_target_mode' => 'selected',
        ]);
        $survey->targetClasses()->attach($class->id);
        $survey->targetTeachers()->attach($inactiveTeacherUser->teacher->id);
        $this->makeQuestion($survey);

        $this->actingAs($admin)
            ->patch('/surveys/'.$survey->id.'/publish', $this->publishPayload())
            ->assertSessionHasErrors();

        $this->assertSame('draft', $survey->refresh()->status);
    }

    public function test_teacher_who_no_longer_teaches_target_class_cannot_publish(): void
    {
        $teacher = $this->teacherUser();
        $category = $this->makeCategory();
        $class = $this->makeClass();
        $survey = $this->makePublishableSurvey($teacher, $category, $class);

        $this->actingAs($teacher)
            ->patch('/surveys/'.$survey->id.'/publish', $this->publishPayload())
            ->assertSessionHasErrors();

        $this->assertSame('draft', $survey->refresh()->status);
    }

    public function test_target_mode_all_does_not_require_pivot(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Draft Semua Target '.uniqid(), [
            'student_target_mode' => 'all',
            'teacher_target_mode' => 'all',
        ]);
        $this->makeQuestion($survey);

        $this->actingAs($admin)
            ->patch('/surveys/'.$survey->id.'/publish', $this->publishPayload())
            ->assertRedirect(route('surveys.index'));

        $this->assertSame('published', $survey->refresh()->status);
        $this->assertSame(0, $survey->targetClasses()->count());
        $this->assertSame(0, $survey->targetTeachers()->count());
    }

    public function test_publish_payload_cannot_manipulate_status_creator_or_result_flag(): void
    {
        $admin = $this->admin();
        $other = $this->teacherUser();
        $category = $this->makeCategory();
        $class = $this->makeClass();
        $survey = $this->makePublishableSurvey($admin, $category, $class);

        $this->actingAs($admin)
            ->patch('/surveys/'.$survey->id.'/publish', $this->publishPayload([
                'creator_id' => $other->id,
                'status' => 'closed',
                'is_result_published' => '1',
            ]))
            ->assertRedirect(route('surveys.index'));

        $survey->refresh();

        $this->assertSame('published', $survey->status);
        $this->assertSame($admin->id, $survey->creator_id);
        $this->assertFalse($survey->is_result_published);
    }

    public function test_ends_at_must_be_greater_than_starts_at(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $class = $this->makeClass();
        $survey = $this->makePublishableSurvey($admin, $category, $class);
        $starts = now()->startOfMinute();

        $this->actingAs($admin)
            ->patch('/surveys/'.$survey->id.'/publish', $this->publishPayload([
                'starts_at' => $starts->format('Y-m-d H:i:s'),
                'ends_at' => $starts->format('Y-m-d H:i:s'),
            ]))
            ->assertSessionHasErrors('ends_at');

        $this->assertSame('draft', $survey->refresh()->status);
    }

    public function test_publish_before_starts_at_is_allowed(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $class = $this->makeClass();
        $survey = $this->makePublishableSurvey($admin, $category, $class);
        $startsAt = now()->addDay()->startOfMinute();
        $endsAt = $startsAt->copy()->addDays(7);

        $this->actingAs($admin)
            ->patch('/surveys/'.$survey->id.'/publish', $this->publishPayload([
                'starts_at' => $startsAt->format('Y-m-d H:i:s'),
                'ends_at' => $endsAt->format('Y-m-d H:i:s'),
            ]))
            ->assertRedirect(route('surveys.index'));

        $survey->refresh();

        $this->assertSame('published', $survey->status);
        $this->assertSame($startsAt->format('Y-m-d H:i:s'), $survey->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame($endsAt->format('Y-m-d H:i:s'), $survey->ends_at->format('Y-m-d H:i:s'));
    }

    public function test_survey_cannot_be_published_twice(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $class = $this->makeClass();
        $survey = $this->makePublishableSurvey($admin, $category, $class);

        $this->actingAs($admin)
            ->patch('/surveys/'.$survey->id.'/publish', $this->publishPayload())
            ->assertRedirect(route('surveys.index'));

        $this->actingAs($admin)
            ->patch('/surveys/'.$survey->id.'/publish', $this->publishPayload())
            ->assertForbidden();

        $this->assertSame('published', $survey->refresh()->status);
    }

    public function test_published_and_closed_surveys_cannot_be_published_again(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $published = $this->makeSurvey($admin, $category, 'Published Ulang '.uniqid(), ['status' => 'published']);
        $closed = $this->makeSurvey($admin, $category, 'Closed Ulang '.uniqid(), ['status' => 'closed']);

        $this->actingAs($admin)
            ->patch('/surveys/'.$published->id.'/publish', $this->publishPayload())
            ->assertForbidden();

        $this->actingAs($admin)
            ->patch('/surveys/'.$closed->id.'/publish', $this->publishPayload())
            ->assertForbidden();

        $this->assertSame('published', $published->refresh()->status);
        $this->assertSame('closed', $closed->refresh()->status);
    }

    public function test_publish_transaction_failure_leaves_no_partial_change(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $class = $this->makeClass();
        $survey = $this->makePublishableSurvey($admin, $category, $class);

        DB::listen(function ($query): void {
            if (str_contains($query->sql, 'update `surveys`')) {
                throw new RuntimeException('forced publish failure');
            }
        });

        $this->withoutExceptionHandling();

        $caught = null;

        try {
            $this->actingAs($admin)->patch('/surveys/'.$survey->id.'/publish', $this->publishPayload());
        } catch (RuntimeException $e) {
            $caught = $e;
        }

        $this->assertNotNull($caught);
        $this->assertSame('forced publish failure', $caught->getMessage());

        $survey->refresh();

        $this->assertSame('draft', $survey->status);
        $this->assertNull($survey->starts_at);
        $this->assertNull($survey->ends_at);
    }

    public function test_admin_can_close_teacher_published_survey(): void
    {
        $admin = $this->admin();
        $teacher = $this->teacherUser();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($teacher, $category, 'Published Tutup Admin '.uniqid(), [
            'status' => 'published',
            'starts_at' => now(),
            'ends_at' => now()->addDay(),
        ]);

        $this->actingAs($admin)
            ->patch('/surveys/'.$survey->id.'/close')
            ->assertRedirect(route('surveys.index'))
            ->assertSessionHas('status', 'Survei berhasil ditutup.');

        $this->assertSame('closed', $survey->refresh()->status);
    }

    public function test_teacher_can_close_own_published_survey(): void
    {
        $teacher = $this->teacherUser();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($teacher, $category, 'Published Tutup Guru '.uniqid(), [
            'status' => 'published',
        ]);

        $this->actingAs($teacher)
            ->patch('/surveys/'.$survey->id.'/close')
            ->assertRedirect(route('surveys.index'));

        $this->assertSame('closed', $survey->refresh()->status);
    }

    public function test_teacher_cannot_close_another_teachers_survey(): void
    {
        $owner = $this->teacherUser();
        $other = $this->teacherUser();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($owner, $category, 'Published Guru Lain '.uniqid(), [
            'status' => 'published',
        ]);

        $this->actingAs($other)
            ->patch('/surveys/'.$survey->id.'/close')
            ->assertForbidden();

        $this->assertSame('published', $survey->refresh()->status);
    }

    public function test_draft_cannot_be_closed(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Draft Tidak Bisa Close '.uniqid());

        $this->actingAs($admin)
            ->patch('/surveys/'.$survey->id.'/close')
            ->assertForbidden();

        $this->assertSame('draft', $survey->refresh()->status);
    }

    public function test_closed_survey_cannot_be_closed_again(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Closed Tidak Bisa Close '.uniqid(), [
            'status' => 'closed',
        ]);

        $this->actingAs($admin)
            ->patch('/surveys/'.$survey->id.'/close')
            ->assertForbidden();

        $this->assertSame('closed', $survey->refresh()->status);
    }

    public function test_close_does_not_delete_responses_or_answers(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Published Respons Aman '.uniqid(), [
            'status' => 'published',
        ]);
        $graph = $this->makeResponseGraph($survey, $student);

        $this->actingAs($admin)
            ->patch('/surveys/'.$survey->id.'/close')
            ->assertRedirect(route('surveys.index'));

        $this->assertSame('closed', $survey->refresh()->status);
        $this->assertDatabaseHas('responses', ['id' => $graph['response']->id]);
        $this->assertDatabaseHas('answers', ['id' => $graph['answer']->id]);
        $this->assertDatabaseHas('answer_options', ['id' => $graph['answerOption']->id]);
    }

    public function test_close_is_not_repeatable(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Close Berulang '.uniqid(), [
            'status' => 'published',
        ]);

        $this->actingAs($admin)
            ->patch('/surveys/'.$survey->id.'/close')
            ->assertRedirect(route('surveys.index'));

        $this->actingAs($admin)
            ->patch('/surveys/'.$survey->id.'/close')
            ->assertForbidden();

        $this->assertSame('closed', $survey->refresh()->status);
    }

    public function test_reopen_route_does_not_exist(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Tanpa Reopen '.uniqid(), [
            'status' => 'closed',
        ]);

        $this->assertFalse(Route::has('surveys.reopen'));

        $this->actingAs($admin)
            ->patch('/surveys/'.$survey->id.'/reopen')
            ->assertNotFound();
    }

    public function test_published_survey_cannot_be_edited(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Published Tidak Bisa Edit '.uniqid(), [
            'status' => 'published',
        ]);
        $title = $survey->title;

        $this->actingAs($admin)
            ->put('/surveys/'.$survey->id, $this->payload($category, 'Judul Diubah '.uniqid()))
            ->assertForbidden();

        $this->assertSame($title, $survey->refresh()->title);
    }

    public function test_closed_survey_cannot_be_edited(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Closed Tidak Bisa Edit '.uniqid(), [
            'status' => 'closed',
        ]);
        $title = $survey->title;

        $this->actingAs($admin)
            ->put('/surveys/'.$survey->id, $this->payload($category, 'Judul Diubah '.uniqid()))
            ->assertForbidden();

        $this->assertSame($title, $survey->refresh()->title);
    }

    public function test_published_and_closed_surveys_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $published = $this->makeSurvey($admin, $category, 'Published Tidak Bisa Hapus '.uniqid(), [
            'status' => 'published',
        ]);
        $closed = $this->makeSurvey($admin, $category, 'Closed Tidak Bisa Hapus '.uniqid(), [
            'status' => 'closed',
        ]);

        $this->actingAs($admin)->delete('/surveys/'.$published->id)->assertForbidden();
        $this->actingAs($admin)->delete('/surveys/'.$closed->id)->assertForbidden();

        $this->assertDatabaseHas('surveys', ['id' => $published->id]);
        $this->assertDatabaseHas('surveys', ['id' => $closed->id]);
    }

    public function test_published_survey_cannot_change_questions_after_publish(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Published Pertanyaan Kunci '.uniqid(), [
            'status' => 'published',
        ]);
        $question = $this->makeQuestion($survey);
        $text = $question->question_text;

        $this->actingAs($admin)
            ->post('/surveys/'.$survey->id.'/questions', $this->questionPayload())
            ->assertForbidden();

        $this->actingAs($admin)
            ->put('/surveys/'.$survey->id.'/questions/'.$question->id, $this->questionPayload(['question_text' => 'Diubah']))
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete('/surveys/'.$survey->id.'/questions/'.$question->id)
            ->assertForbidden();

        $this->assertSame($text, $question->refresh()->question_text);
    }

    public function test_closed_survey_cannot_change_questions_after_close(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Closed Pertanyaan Kunci '.uniqid(), [
            'status' => 'closed',
        ]);
        $question = $this->makeQuestion($survey);

        $this->actingAs($admin)
            ->post('/surveys/'.$survey->id.'/questions', $this->questionPayload())
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete('/surveys/'.$survey->id.'/questions/'.$question->id)
            ->assertForbidden();

        $this->assertDatabaseHas('questions', ['id' => $question->id]);
    }

    public function test_target_pivots_remain_when_publish_fails(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $class = $this->makeClass();
        $survey = $this->makeSurvey($admin, $category, 'Target Tidak Berubah '.uniqid(), [
            'student_target_mode' => 'classes',
        ]);
        $survey->targetClasses()->attach($class->id);

        $this->actingAs($admin)
            ->patch('/surveys/'.$survey->id.'/publish', $this->publishPayload())
            ->assertSessionHasErrors();

        $this->assertDatabaseHas('survey_target_classes', [
            'survey_id' => $survey->id,
            'class_id' => $class->id,
        ]);
        $this->assertSame('draft', $survey->refresh()->status);
    }

    public function test_update_cannot_bypass_status_guard_through_race_condition(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Race Update '.uniqid());
        $fired = false;

        Event::listen(TransactionBeginning::class, function () use (&$fired, $survey): void {
            if (! $fired) {
                $fired = true;
                DB::table('surveys')->where('id', $survey->id)->update(['status' => 'published']);
            }
        });

        $newTitle = 'Judul Race '.uniqid();

        $this->actingAs($admin)
            ->put('/surveys/'.$survey->id, $this->payload($category, $newTitle))
            ->assertForbidden();

        $survey->refresh();

        $this->assertSame('draft', $survey->status);
        $this->assertNotSame($newTitle, $survey->title);
    }

    public function test_delete_cannot_bypass_status_guard_through_race_condition(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($admin, $category, 'Race Delete '.uniqid());
        $fired = false;

        Event::listen(TransactionBeginning::class, function () use (&$fired, $survey): void {
            if (! $fired) {
                $fired = true;
                DB::table('surveys')->where('id', $survey->id)->update(['status' => 'published']);
            }
        });

        $this->actingAs($admin)
            ->delete('/surveys/'.$survey->id)
            ->assertForbidden();

        $this->assertDatabaseHas('surveys', ['id' => $survey->id]);
        $this->assertSame('draft', $survey->refresh()->status);
    }
}
