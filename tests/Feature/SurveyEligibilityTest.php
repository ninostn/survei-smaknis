<?php

namespace Tests\Feature;

use App\Models\Response;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Survey;
use App\Models\SurveyCategory;
use App\Models\Teacher;
use App\Models\User;
use App\Support\SurveyEligibility;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Test helper eligibility. Diletakkan di Feature karena membutuhkan database
 * dan transaksi, mengikuti pola seluruh test proyek.
 */
class SurveyEligibilityTest extends TestCase
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

    private function makeUser(string $role, bool $active = true): User
    {
        return User::create([
            'username' => $role.'_'.uniqid(),
            'password' => self::PASSWORD,
            'role' => $role,
            'is_active' => $active,
        ]);
    }

    private function makeClass(bool $active = true): SchoolClass
    {
        return SchoolClass::create([
            'name' => 'Kelas '.uniqid(),
            'academic_year' => '2026/2027',
            'is_active' => $active,
        ]);
    }

    private function makeStudent(?SchoolClass $class = null, bool $active = true, bool $withProfile = true): User
    {
        $user = $this->makeUser('student', $active);

        if ($withProfile) {
            Student::create([
                'user_id' => $user->id,
                'nis' => 'NIS'.uniqid(),
                'name' => 'Siswa '.uniqid(),
                'class_id' => ($class ?? $this->makeClass())->id,
            ]);
        }

        return $user;
    }

    private function makeTeacher(bool $active = true, bool $withProfile = true): User
    {
        $user = $this->makeUser('teacher', $active);

        if ($withProfile) {
            Teacher::create([
                'user_id' => $user->id,
                'nip' => 'NIP'.uniqid(),
                'name' => 'Guru '.uniqid(),
            ]);
        }

        return $user;
    }

    private function makeCategory(): SurveyCategory
    {
        return $this->category ??= SurveyCategory::create([
            'name' => 'Kategori '.uniqid(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeSurvey(array $overrides = []): Survey
    {
        $creator = $overrides['creator_id'] ?? $this->makeUser('admin')->id;
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

    private function admit(Survey $survey, User $user): Response
    {
        return Response::create([
            'survey_id' => $survey->id,
            'user_id' => $user->id,
            'submitted_at' => now(),
        ]);
    }

    public function test_student_with_active_class_and_all_target_is_eligible(): void
    {
        $student = $this->makeStudent();
        $survey = $this->makeSurvey(['student_target_mode' => 'all']);

        $this->assertTrue(SurveyEligibility::allows($student, $survey));
        $this->assertSame([$survey->id], SurveyEligibility::availableFor($student)->pluck('id')->all());
    }

    public function test_student_matching_target_class_is_eligible(): void
    {
        $class = $this->makeClass();
        $student = $this->makeStudent($class);
        $survey = $this->makeSurvey(['student_target_mode' => 'classes']);
        $survey->targetClasses()->attach($class->id);

        $this->assertTrue(SurveyEligibility::allows($student, $survey));
        $this->assertCount(1, SurveyEligibility::availableFor($student)->get());
    }

    public function test_student_with_other_class_is_not_eligible(): void
    {
        $student = $this->makeStudent($this->makeClass());
        $survey = $this->makeSurvey(['student_target_mode' => 'classes']);
        $survey->targetClasses()->attach($this->makeClass()->id);

        $this->assertFalse(SurveyEligibility::allows($student, $survey));
        $this->assertCount(0, SurveyEligibility::availableFor($student)->get());
    }

    public function test_student_without_profile_is_not_eligible(): void
    {
        $student = $this->makeStudent(withProfile: false);
        $survey = $this->makeSurvey(['student_target_mode' => 'all']);

        $this->assertFalse(SurveyEligibility::allows($student, $survey));
        $this->assertCount(0, SurveyEligibility::availableFor($student)->get());
    }

    public function test_student_without_class_id_is_not_eligible(): void
    {
        $student = $this->makeStudent();
        $student->setRelation('student', new Student([
            'user_id' => $student->id,
            'nis' => 'NIS-TANPA-KELAS',
            'name' => 'Siswa Tanpa Kelas',
            'class_id' => null,
        ]));

        $survey = $this->makeSurvey(['student_target_mode' => 'all']);

        $this->assertFalse(SurveyEligibility::allows($student, $survey));
        $this->assertCount(0, SurveyEligibility::availableFor($student)->get());
    }

    public function test_student_with_inactive_class_is_not_eligible(): void
    {
        $student = $this->makeStudent($this->makeClass(active: false));
        $survey = $this->makeSurvey(['student_target_mode' => 'all']);

        $this->assertFalse(SurveyEligibility::allows($student, $survey));
        $this->assertCount(0, SurveyEligibility::availableFor($student)->get());
    }

    public function test_teacher_with_all_target_is_eligible(): void
    {
        $teacher = $this->makeTeacher();
        $survey = $this->makeSurvey(['teacher_target_mode' => 'all']);

        $this->assertTrue(SurveyEligibility::allows($teacher, $survey));
        $this->assertSame([$survey->id], SurveyEligibility::availableFor($teacher)->pluck('id')->all());
    }

    public function test_teacher_selected_as_target_is_eligible(): void
    {
        $teacher = $this->makeTeacher();
        $survey = $this->makeSurvey(['teacher_target_mode' => 'selected']);
        $survey->targetTeachers()->attach($teacher->teacher->id);

        $this->assertTrue(SurveyEligibility::allows($teacher, $survey));
        $this->assertCount(1, SurveyEligibility::availableFor($teacher)->get());
    }

    public function test_teacher_not_selected_is_not_eligible(): void
    {
        $teacher = $this->makeTeacher();
        $other = $this->makeTeacher();
        $survey = $this->makeSurvey(['teacher_target_mode' => 'selected']);
        $survey->targetTeachers()->attach($other->teacher->id);

        $this->assertFalse(SurveyEligibility::allows($teacher, $survey));
        $this->assertCount(0, SurveyEligibility::availableFor($teacher)->get());
    }

    public function test_teacher_without_profile_is_not_eligible(): void
    {
        $teacher = $this->makeTeacher(withProfile: false);
        $survey = $this->makeSurvey(['teacher_target_mode' => 'all']);

        $this->assertFalse(SurveyEligibility::allows($teacher, $survey));
        $this->assertCount(0, SurveyEligibility::availableFor($teacher)->get());
    }

    public function test_teacher_creator_without_target_does_not_get_special_access(): void
    {
        $teacher = $this->makeTeacher();
        $survey = $this->makeSurvey([
            'creator_id' => $teacher->id,
            'teacher_target_mode' => 'selected',
        ]);

        $this->assertSame($teacher->id, $survey->creator_id);
        $this->assertFalse(SurveyEligibility::allows($teacher, $survey));
        $this->assertCount(0, SurveyEligibility::availableFor($teacher)->get());
    }

    public function test_target_mode_none_is_not_eligible(): void
    {
        $student = $this->makeStudent();
        $teacher = $this->makeTeacher();
        $survey = $this->makeSurvey();

        $this->assertFalse(SurveyEligibility::allows($student, $survey));
        $this->assertFalse(SurveyEligibility::allows($teacher, $survey));
    }

    public function test_draft_and_closed_surveys_are_not_eligible(): void
    {
        $student = $this->makeStudent();
        $draft = $this->makeSurvey(['student_target_mode' => 'all', 'status' => 'draft']);
        $closed = $this->makeSurvey(['student_target_mode' => 'all', 'status' => 'closed']);

        $this->assertFalse(SurveyEligibility::allows($student, $draft));
        $this->assertFalse(SurveyEligibility::allows($student, $closed));
        $this->assertCount(0, SurveyEligibility::availableFor($student)->get());
    }

    public function test_survey_before_starts_at_is_not_eligible(): void
    {
        $student = $this->makeStudent();
        $survey = $this->makeSurvey([
            'student_target_mode' => 'all',
            'starts_at' => $this->base->copy()->addMinute(),
            'ends_at' => $this->base->copy()->addHour(),
        ]);

        $this->assertFalse(SurveyEligibility::allows($student, $survey));
        $this->assertCount(0, SurveyEligibility::availableFor($student)->get());
    }

    public function test_survey_at_exactly_starts_at_is_eligible(): void
    {
        $student = $this->makeStudent();
        $survey = $this->makeSurvey([
            'student_target_mode' => 'all',
            'starts_at' => $this->base->copy(),
            'ends_at' => $this->base->copy()->addHour(),
        ]);

        $this->assertTrue(SurveyEligibility::allows($student, $survey));
        $this->assertCount(1, SurveyEligibility::availableFor($student)->get());
    }

    public function test_survey_at_exactly_ends_at_is_eligible(): void
    {
        $student = $this->makeStudent();
        $survey = $this->makeSurvey([
            'student_target_mode' => 'all',
            'starts_at' => $this->base->copy()->subHour(),
            'ends_at' => $this->base->copy(),
        ]);

        $this->assertTrue(SurveyEligibility::allows($student, $survey));
        $this->assertCount(1, SurveyEligibility::availableFor($student)->get());
    }

    public function test_survey_after_ends_at_is_not_eligible(): void
    {
        $student = $this->makeStudent();
        $survey = $this->makeSurvey([
            'student_target_mode' => 'all',
            'starts_at' => $this->base->copy()->subHours(2),
            'ends_at' => $this->base->copy()->subSecond(),
        ]);

        $this->assertFalse(SurveyEligibility::allows($student, $survey));
        $this->assertCount(0, SurveyEligibility::availableFor($student)->get());
    }

    public function test_survey_without_dates_is_not_eligible(): void
    {
        $student = $this->makeStudent();
        $survey = $this->makeSurvey([
            'student_target_mode' => 'all',
            'starts_at' => null,
            'ends_at' => null,
        ]);

        $this->assertFalse(SurveyEligibility::allows($student, $survey));
        $this->assertCount(0, SurveyEligibility::availableFor($student)->get());
    }

    public function test_user_who_already_responded_is_not_eligible(): void
    {
        $student = $this->makeStudent();
        $survey = $this->makeSurvey(['student_target_mode' => 'all']);
        $this->admit($survey, $student);

        $this->assertFalse(SurveyEligibility::allows($student, $survey));
        $this->assertCount(0, SurveyEligibility::availableFor($student)->get());
    }

    public function test_inactive_user_is_not_eligible(): void
    {
        $student = $this->makeStudent(active: false);
        $teacher = $this->makeTeacher(active: false);
        $survey = $this->makeSurvey([
            'student_target_mode' => 'all',
            'teacher_target_mode' => 'all',
        ]);

        $this->assertFalse(SurveyEligibility::allows($student, $survey));
        $this->assertFalse(SurveyEligibility::allows($teacher, $survey));
        $this->assertCount(0, SurveyEligibility::availableFor($student)->get());
        $this->assertCount(0, SurveyEligibility::availableFor($teacher)->get());
    }

    public function test_admin_is_not_a_respondent(): void
    {
        $admin = $this->makeUser('admin');
        $survey = $this->makeSurvey([
            'student_target_mode' => 'all',
            'teacher_target_mode' => 'all',
        ]);

        $this->assertFalse(SurveyEligibility::allows($admin, $survey));
        $this->assertCount(0, SurveyEligibility::availableFor($admin)->get());
    }

    public function test_unknown_role_gets_empty_result(): void
    {
        $unknown = new User([
            'username' => 'guardian_'.uniqid(),
            'role' => 'guardian',
            'is_active' => true,
        ]);

        $survey = $this->makeSurvey([
            'student_target_mode' => 'all',
            'teacher_target_mode' => 'all',
        ]);

        $this->assertFalse(SurveyEligibility::allows($unknown, $survey));
        $this->assertCount(0, SurveyEligibility::availableFor($unknown)->get());
    }

    public function test_available_for_and_allows_are_consistent(): void
    {
        $class = $this->makeClass();
        $student = $this->makeStudent($class);

        $eligible = $this->makeSurvey(['student_target_mode' => 'all']);
        $byClass = $this->makeSurvey(['student_target_mode' => 'classes']);
        $byClass->targetClasses()->attach($class->id);

        $wrongClass = $this->makeSurvey(['student_target_mode' => 'classes']);
        $wrongClass->targetClasses()->attach($this->makeClass()->id);

        $draft = $this->makeSurvey(['student_target_mode' => 'all', 'status' => 'draft']);
        $closed = $this->makeSurvey(['student_target_mode' => 'all', 'status' => 'closed']);
        $notStarted = $this->makeSurvey([
            'student_target_mode' => 'all',
            'starts_at' => $this->base->copy()->addHour(),
            'ends_at' => $this->base->copy()->addHours(2),
        ]);
        $ended = $this->makeSurvey([
            'student_target_mode' => 'all',
            'starts_at' => $this->base->copy()->subHours(2),
            'ends_at' => $this->base->copy()->subHour(),
        ]);
        $answered = $this->makeSurvey(['student_target_mode' => 'all']);
        $this->admit($answered, $student);

        $surveys = [$eligible, $byClass, $wrongClass, $draft, $closed, $notStarted, $ended, $answered];

        $available = SurveyEligibility::availableFor($student)->pluck('id')->all();

        foreach ($surveys as $survey) {
            $this->assertSame(
                in_array($survey->id, $available, true),
                SurveyEligibility::allows($student, $survey->fresh()),
                'Keputusan availableFor() dan allows() berbeda untuk survei id '.$survey->id,
            );
        }

        $this->assertCount(2, $available);
    }

    public function test_available_for_does_not_grow_queries_with_more_surveys(): void
    {
        $student = $this->makeStudent();
        $studentId = $student->id;

        $measure = function () use ($studentId): array {
            $fresh = User::query()->findOrFail($studentId);

            DB::enableQueryLog();
            DB::flushQueryLog();

            $surveys = SurveyEligibility::availableFor($fresh)->get();

            $queries = count(DB::getQueryLog());
            DB::disableQueryLog();
            DB::flushQueryLog();

            return [$queries, $surveys];
        };

        $this->makeSurvey(['student_target_mode' => 'all']);
        [$queriesForOne] = $measure();

        foreach (range(1, 4) as $ignored) {
            $this->makeSurvey(['student_target_mode' => 'all']);
        }

        [$queriesForFive, $surveys] = $measure();

        $this->assertCount(5, $surveys);
        $this->assertSame($queriesForOne, $queriesForFive);
    }
}
