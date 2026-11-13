<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AcademicManagementTest extends TestCase
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

    private function makeClass(bool $active = true, string $year = '2026/2027'): SchoolClass
    {
        return SchoolClass::create([
            'name' => 'X TKJ '.uniqid(),
            'major' => 'TJKT',
            'academic_year' => $year,
            'is_active' => $active,
        ]);
    }

    private function makeSubject(): Subject
    {
        $suffix = uniqid();

        return Subject::create([
            'code' => 'MP-'.$suffix,
            'name' => 'Mata Pelajaran '.$suffix,
        ]);
    }

    private function makeStudent(SchoolClass $class): Student
    {
        $suffix = uniqid();

        $user = User::create([
            'username' => 'siswa_'.$suffix,
            'password' => self::PASSWORD,
            'role' => 'student',
            'is_active' => true,
        ]);

        return Student::create([
            'user_id' => $user->id,
            'nis' => 'NIS-'.$suffix,
            'name' => 'Siswa Uji '.$suffix,
            'class_id' => $class->id,
        ]);
    }

    private function makeTeacher(string $role = 'teacher', bool $active = true): Teacher
    {
        $user = User::create([
            'username' => 'guru_'.uniqid(),
            'password' => self::PASSWORD,
            'role' => $role,
            'is_active' => $active,
        ]);

        return Teacher::create([
            'user_id' => $user->id,
            'name' => 'Guru Uji '.uniqid(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function assignmentPayload(Teacher $teacher, SchoolClass $class, Subject $subject, array $overrides = []): array
    {
        return array_merge([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'academic_year' => '2026/2027',
        ], $overrides);
    }

    public function test_admin_can_view_class_list(): void
    {
        $class = $this->makeClass();

        $this->actingAs($this->admin())
            ->get('/admin/classes')
            ->assertOk()
            ->assertViewIs('admin.classes.index')
            ->assertSee('Manajemen Kelas')
            ->assertSee($class->name);
    }

    public function test_admin_can_create_class(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/classes', [
                'name' => 'XI RPL 1',
                'major' => 'PPLG',
                'academic_year' => '2026/2027',
            ])
            ->assertRedirect('/admin/classes')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('classes', [
            'name' => 'XI RPL 1',
            'academic_year' => '2026/2027',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_class(): void
    {
        $class = $this->makeClass();

        $this->actingAs($this->admin())
            ->put('/admin/classes/'.$class->id, [
                'name' => 'XII RPL 2',
                'academic_year' => '2027/2028',
            ])
            ->assertRedirect('/admin/classes')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('classes', [
            'id' => $class->id,
            'name' => 'XII RPL 2',
            'academic_year' => '2027/2028',
            'is_active' => true,
        ]);
    }

    public function test_duplicate_class_name_and_year_is_rejected(): void
    {
        $existing = $this->makeClass();

        $this->actingAs($this->admin())
            ->post('/admin/classes', [
                'name' => $existing->name,
                'academic_year' => $existing->academic_year,
            ])
            ->assertSessionHasErrors('name');

        $this->assertSame(1, SchoolClass::where('name', $existing->name)->count());
    }

    public function test_updating_class_ignores_its_own_unique_row(): void
    {
        $class = $this->makeClass();

        $this->actingAs($this->admin())
            ->put('/admin/classes/'.$class->id, [
                'name' => $class->name,
                'academic_year' => $class->academic_year,
            ])
            ->assertRedirect('/admin/classes')
            ->assertSessionHasNoErrors();
    }

    public function test_class_can_be_deactivated_and_activated_again(): void
    {
        $class = $this->makeClass();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->patch('/admin/classes/'.$class->id.'/toggle')
            ->assertRedirect('/admin/classes');

        $this->assertFalse($class->refresh()->is_active);

        $this->actingAs($admin)->patch('/admin/classes/'.$class->id.'/toggle');

        $this->assertTrue($class->refresh()->is_active);
    }

    public function test_inactive_class_cannot_be_used_for_new_student_account(): void
    {
        $class = $this->makeClass();
        $admin = $this->admin();

        $this->actingAs($admin)->patch('/admin/classes/'.$class->id.'/toggle');
        $this->assertFalse($class->refresh()->is_active);

        $payload = [
            'username' => 'siswa_'.uniqid(),
            'name' => 'Siswa Uji',
            'nis' => 'NIS-'.uniqid(),
            'class_id' => $class->id,
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
        ];

        $this->actingAs($admin)
            ->post('/admin/users/students', $payload)
            ->assertSessionHasErrors('class_id');

        $this->assertNull(User::where('username', $payload['username'])->first());
    }

    public function test_class_academic_year_can_change_without_relations(): void
    {
        $class = $this->makeClass(true, '2026/2027');

        $this->actingAs($this->admin())
            ->put('/admin/classes/'.$class->id, [
                'name' => $class->name,
                'academic_year' => '2030/2031',
            ])
            ->assertRedirect('/admin/classes')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('classes', [
            'id' => $class->id,
            'academic_year' => '2030/2031',
        ]);
    }

    public function test_class_academic_year_cannot_change_when_class_has_students(): void
    {
        $class = $this->makeClass(true, '2026/2027');
        $student = $this->makeStudent($class);

        $this->actingAs($this->admin())
            ->put('/admin/classes/'.$class->id, [
                'name' => $class->name,
                'academic_year' => '2030/2031',
            ])
            ->assertSessionHasErrors('academic_year');

        $this->assertSame('2026/2027', $class->refresh()->academic_year);
        $this->assertSame($class->id, $student->refresh()->class_id);
    }

    public function test_class_academic_year_cannot_change_when_class_has_teaching_assignment(): void
    {
        $class = $this->makeClass(true, '2026/2027');
        $assignment = TeachingAssignment::create($this->assignmentPayload(
            $this->makeTeacher(),
            $class,
            $this->makeSubject(),
            ['academic_year' => $class->academic_year],
        ));

        $this->actingAs($this->admin())
            ->put('/admin/classes/'.$class->id, [
                'name' => $class->name,
                'academic_year' => '2030/2031',
            ])
            ->assertSessionHasErrors('academic_year');

        $this->assertSame('2026/2027', $class->refresh()->academic_year);
        $this->assertSame($class->id, $assignment->refresh()->class_id);
        $this->assertSame('2026/2027', $assignment->academic_year);
    }

    public function test_class_academic_year_cannot_change_when_class_has_students_and_assignment(): void
    {
        $class = $this->makeClass(true, '2026/2027');
        $this->makeStudent($class);
        TeachingAssignment::create($this->assignmentPayload(
            $this->makeTeacher(),
            $class,
            $this->makeSubject(),
            ['academic_year' => $class->academic_year],
        ));

        $this->actingAs($this->admin())
            ->put('/admin/classes/'.$class->id, [
                'name' => $class->name,
                'academic_year' => '2030/2031',
            ])
            ->assertSessionHasErrors('academic_year');

        $this->assertSame('2026/2027', $class->refresh()->academic_year);
    }

    public function test_class_name_can_change_without_changing_academic_year(): void
    {
        $class = $this->makeClass(true, '2026/2027');
        $this->makeStudent($class);
        $assignment = TeachingAssignment::create($this->assignmentPayload(
            $this->makeTeacher(),
            $class,
            $this->makeSubject(),
            ['academic_year' => $class->academic_year],
        ));

        $this->actingAs($this->admin())
            ->put('/admin/classes/'.$class->id, [
                'name' => 'Nama Kelas Baru '.uniqid(),
                'academic_year' => '2026/2027',
            ])
            ->assertRedirect('/admin/classes')
            ->assertSessionHasNoErrors();

        $class->refresh();
        $this->assertStringStartsWith('Nama Kelas Baru', $class->name);
        $this->assertSame('2026/2027', $class->academic_year);
        $this->assertSame('2026/2027', $assignment->refresh()->academic_year);
    }

    public function test_same_academic_year_is_allowed_even_with_relations(): void
    {
        $class = $this->makeClass(true, '2026/2027');
        $this->makeStudent($class);
        TeachingAssignment::create($this->assignmentPayload(
            $this->makeTeacher(),
            $class,
            $this->makeSubject(),
            ['academic_year' => $class->academic_year],
        ));

        $this->actingAs($this->admin())
            ->put('/admin/classes/'.$class->id, [
                'name' => $class->name,
                'academic_year' => '2026/2027',
            ])
            ->assertRedirect('/admin/classes')
            ->assertSessionHasNoErrors();

        $this->assertSame('2026/2027', $class->refresh()->academic_year);
    }

    public function test_rejected_academic_year_change_keeps_class_and_assignment_intact(): void
    {
        $class = $this->makeClass(true, '2026/2027');
        $originalName = $class->name;
        $assignment = TeachingAssignment::create($this->assignmentPayload(
            $this->makeTeacher(),
            $class,
            $this->makeSubject(),
            ['academic_year' => $class->academic_year],
        ));
        $assignmentYear = $assignment->academic_year;

        $this->actingAs($this->admin())
            ->put('/admin/classes/'.$class->id, [
                'name' => $originalName.' diubah',
                'academic_year' => '2030/2031',
            ])
            ->assertSessionHasErrors('academic_year');

        $class->refresh();
        $this->assertSame($originalName, $class->name);
        $this->assertSame('2026/2027', $class->academic_year);

        $assignment->refresh();
        $this->assertSame($class->id, $assignment->class_id);
        $this->assertSame($assignmentYear, $assignment->academic_year);
    }

    public function test_editing_class_still_rejects_duplicate_name_and_year(): void
    {
        $first = $this->makeClass(true, '2026/2027');
        $second = $this->makeClass(true, '2026/2027');
        $secondName = $second->name;

        $this->actingAs($this->admin())
            ->put('/admin/classes/'.$second->id, [
                'name' => $first->name,
                'academic_year' => '2026/2027',
            ])
            ->assertSessionHasErrors('name');

        $this->assertSame($secondName, $second->refresh()->name);
        $this->assertSame('2026/2027', $second->academic_year);
    }

    public function test_admin_can_view_subject_list(): void
    {
        $subject = $this->makeSubject();

        $this->actingAs($this->admin())
            ->get('/admin/subjects')
            ->assertOk()
            ->assertViewIs('admin.subjects.index')
            ->assertSee('Mata Pelajaran')
            ->assertSee($subject->code);
    }

    public function test_admin_can_create_subject(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/subjects', [
                'code' => 'MTK',
                'name' => 'Matematika',
            ])
            ->assertRedirect('/admin/subjects')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('subjects', [
            'code' => 'MTK',
            'name' => 'Matematika',
        ]);
    }

    public function test_admin_can_update_subject(): void
    {
        $subject = $this->makeSubject();

        $this->actingAs($this->admin())
            ->put('/admin/subjects/'.$subject->id, [
                'code' => 'BIND',
                'name' => 'Bahasa Indonesia',
            ])
            ->assertRedirect('/admin/subjects')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('subjects', [
            'id' => $subject->id,
            'code' => 'BIND',
            'name' => 'Bahasa Indonesia',
        ]);
    }

    public function test_duplicate_subject_code_is_rejected(): void
    {
        $existing = $this->makeSubject();

        $this->actingAs($this->admin())
            ->post('/admin/subjects', [
                'code' => $existing->code,
                'name' => 'Nama Lain',
            ])
            ->assertSessionHasErrors('code');

        $this->assertSame(1, Subject::where('code', $existing->code)->count());
    }

    public function test_subject_referenced_by_assignment_cannot_be_deleted_through_module(): void
    {
        $subject = $this->makeSubject();
        $assignment = TeachingAssignment::create($this->assignmentPayload(
            $this->makeTeacher(),
            $this->makeClass(),
            $subject,
        ));

        // Modul tidak menyediakan route hapus untuk mata pelajaran.
        $this->actingAs($this->admin())
            ->delete('/admin/subjects/'.$subject->id)
            ->assertStatus(405);

        $this->assertDatabaseHas('subjects', ['id' => $subject->id]);
        $this->assertDatabaseHas('teaching_assignments', ['id' => $assignment->id]);
    }

    public function test_admin_can_create_assignment_with_valid_teacher_profile(): void
    {
        $teacher = $this->makeTeacher();
        $class = $this->makeClass();
        $subject = $this->makeSubject();

        $this->actingAs($this->admin())
            ->post('/admin/teaching-assignments', $this->assignmentPayload($teacher, $class, $subject))
            ->assertRedirect('/admin/teaching-assignments')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('teaching_assignments', [
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'academic_year' => '2026/2027',
        ]);
    }

    public function test_teacher_account_created_through_module_can_be_assigned(): void
    {
        $admin = $this->admin();
        $class = $this->makeClass();
        $subject = $this->makeSubject();

        $payload = [
            'username' => 'guru_'.uniqid(),
            'name' => 'Guru Baru',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
        ];

        $this->actingAs($admin)->post('/admin/users/teachers', $payload);

        $teacher = Teacher::where('name', 'Guru Baru')->firstOrFail();

        $this->actingAs($admin)
            ->post('/admin/teaching-assignments', $this->assignmentPayload($teacher, $class, $subject))
            ->assertRedirect('/admin/teaching-assignments')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('teaching_assignments', [
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
        ]);
    }

    public function test_user_without_teacher_role_is_rejected_as_assignment_teacher(): void
    {
        $fakeTeacher = $this->makeTeacher('student');
        $class = $this->makeClass();
        $subject = $this->makeSubject();

        $this->actingAs($this->admin())
            ->post('/admin/teaching-assignments', $this->assignmentPayload($fakeTeacher, $class, $subject))
            ->assertSessionHasErrors('teacher_id');

        $this->assertDatabaseMissing('teaching_assignments', [
            'teacher_id' => $fakeTeacher->id,
        ]);
    }

    public function test_inactive_class_is_rejected_for_assignment(): void
    {
        $teacher = $this->makeTeacher();
        $inactive = $this->makeClass(false);
        $subject = $this->makeSubject();

        $this->actingAs($this->admin())
            ->post('/admin/teaching-assignments', $this->assignmentPayload($teacher, $inactive, $subject))
            ->assertSessionHasErrors('class_id');

        $this->assertDatabaseMissing('teaching_assignments', [
            'class_id' => $inactive->id,
        ]);
    }

    public function test_duplicate_assignment_combination_is_rejected(): void
    {
        $teacher = $this->makeTeacher();
        $class = $this->makeClass();
        $subject = $this->makeSubject();
        $payload = $this->assignmentPayload($teacher, $class, $subject);

        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/teaching-assignments', $payload);

        $this->actingAs($admin)
            ->post('/admin/teaching-assignments', $payload)
            ->assertSessionHasErrors('teacher_id');

        $this->assertSame(1, TeachingAssignment::where([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'academic_year' => '2026/2027',
        ])->count());
    }

    public function test_updating_assignment_ignores_its_own_unique_row(): void
    {
        $teacher = $this->makeTeacher();
        $class = $this->makeClass();
        $subject = $this->makeSubject();
        $payload = $this->assignmentPayload($teacher, $class, $subject);

        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/teaching-assignments', $payload);
        $assignment = TeachingAssignment::where('teacher_id', $teacher->id)->firstOrFail();

        $this->actingAs($admin)
            ->put('/admin/teaching-assignments/'.$assignment->id, $payload)
            ->assertRedirect('/admin/teaching-assignments')
            ->assertSessionHasNoErrors();
    }

    public function test_admin_can_filter_assignments(): void
    {
        $teacherA = $this->makeTeacher();
        $teacherB = $this->makeTeacher();
        $class = $this->makeClass();
        $subject = $this->makeSubject();

        TeachingAssignment::create($this->assignmentPayload($teacherA, $class, $subject));
        TeachingAssignment::create($this->assignmentPayload($teacherB, $class, $subject));

        $this->actingAs($this->admin())
            ->get('/admin/teaching-assignments?teacher_id='.$teacherA->id)
            ->assertOk()
            ->assertViewIs('admin.teaching-assignments.index')
            ->assertViewHas(
                'assignments',
                fn ($assignments) => $assignments->total() === 1
                    && $assignments->first()->teacher_id === $teacherA->id,
            );
    }

    public function test_assignment_can_be_deleted(): void
    {
        $assignment = TeachingAssignment::create($this->assignmentPayload(
            $this->makeTeacher(),
            $this->makeClass(),
            $this->makeSubject(),
        ));

        $this->actingAs($this->admin())
            ->delete('/admin/teaching-assignments/'.$assignment->id)
            ->assertRedirect('/admin/teaching-assignments')
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('teaching_assignments', ['id' => $assignment->id]);
    }

    public function test_duplicate_class_is_handled_at_database_layer(): void
    {
        $admin = $this->admin();

        // Simulasi race: baris duplikat muncul setelah validasi Form Request
        // lolos, tepat sebelum INSERT. Unique constraint DB yang menolak.
        SchoolClass::creating(function (): void {
            DB::table('classes')->insert([
                'name' => 'XI RPL 9',
                'academic_year' => '2026/2027',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        try {
            $this->from('/admin/classes/create')
                ->actingAs($admin)
                ->post('/admin/classes', [
                    'name' => 'XI RPL 9',
                    'major' => 'PPLG',
                    'academic_year' => '2026/2027',
                ])
                ->assertRedirect('/admin/classes/create')
                ->assertSessionHasErrors('name');
        } finally {
            SchoolClass::flushEventListeners();
        }

        $this->assertSame(1, SchoolClass::where('name', 'XI RPL 9')->count());
    }

    public function test_duplicate_assignment_is_handled_at_database_layer(): void
    {
        $teacher = $this->makeTeacher();
        $class = $this->makeClass();
        $subject = $this->makeSubject();
        $admin = $this->admin();

        TeachingAssignment::creating(function () use ($teacher, $class, $subject): void {
            DB::table('teaching_assignments')->insert([
                'teacher_id' => $teacher->id,
                'class_id' => $class->id,
                'subject_id' => $subject->id,
                'academic_year' => $class->academic_year,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        try {
            $this->from('/admin/teaching-assignments/create')
                ->actingAs($admin)
                ->post('/admin/teaching-assignments', $this->assignmentPayload($teacher, $class, $subject))
                ->assertRedirect('/admin/teaching-assignments/create')
                ->assertSessionHasErrors('teacher_id');
        } finally {
            TeachingAssignment::flushEventListeners();
        }

        $this->assertSame(1, TeachingAssignment::count());
    }

    public function test_inactive_teacher_account_is_rejected_for_new_assignment(): void
    {
        $inactiveTeacher = $this->makeTeacher('teacher', false);
        $class = $this->makeClass();
        $subject = $this->makeSubject();

        $this->actingAs($this->admin())
            ->post('/admin/teaching-assignments', $this->assignmentPayload($inactiveTeacher, $class, $subject))
            ->assertSessionHasErrors(['teacher_id' => 'Guru yang dipilih tidak aktif.']);

        $this->assertDatabaseMissing('teaching_assignments', [
            'teacher_id' => $inactiveTeacher->id,
        ]);
    }

    public function test_inactive_teacher_cannot_replace_teacher_on_update(): void
    {
        $teacher = $this->makeTeacher();
        $inactiveTeacher = $this->makeTeacher('teacher', false);
        $class = $this->makeClass();
        $subject = $this->makeSubject();

        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/teaching-assignments', $this->assignmentPayload($teacher, $class, $subject));
        $assignment = TeachingAssignment::where('teacher_id', $teacher->id)->firstOrFail();

        $this->actingAs($admin)
            ->put(
                '/admin/teaching-assignments/'.$assignment->id,
                $this->assignmentPayload($inactiveTeacher, $class, $subject),
            )
            ->assertSessionHasErrors(['teacher_id' => 'Guru yang dipilih tidak aktif.']);

        $this->assertSame($teacher->id, $assignment->refresh()->teacher_id);
    }

    public function test_assignment_with_deactivated_teacher_is_still_listed(): void
    {
        $teacher = $this->makeTeacher();
        $class = $this->makeClass();
        $subject = $this->makeSubject();
        $assignment = TeachingAssignment::create($this->assignmentPayload($teacher, $class, $subject));

        $teacher->user->update(['is_active' => false]);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->get('/admin/teaching-assignments')
            ->assertOk()
            ->assertSee($teacher->name);

        $this->actingAs($admin)
            ->get('/admin/teaching-assignments/'.$assignment->id.'/edit')
            ->assertOk()
            ->assertSee('akun nonaktif');
    }

    public function test_inactive_class_can_be_kept_when_updating_assignment(): void
    {
        $teacher = $this->makeTeacher();
        $class = $this->makeClass();
        $firstSubject = $this->makeSubject();
        $secondSubject = $this->makeSubject();

        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/teaching-assignments', $this->assignmentPayload($teacher, $class, $firstSubject));
        $assignment = TeachingAssignment::where('teacher_id', $teacher->id)->firstOrFail();

        $class->update(['is_active' => false]);

        $this->actingAs($admin)
            ->put(
                '/admin/teaching-assignments/'.$assignment->id,
                $this->assignmentPayload($teacher, $class, $secondSubject),
            )
            ->assertRedirect('/admin/teaching-assignments')
            ->assertSessionHasNoErrors();

        $assignment->refresh();
        $this->assertSame($class->id, $assignment->class_id);
        $this->assertSame($secondSubject->id, $assignment->subject_id);
        $this->assertSame($class->academic_year, $assignment->academic_year);
    }

    public function test_switching_to_inactive_class_is_rejected(): void
    {
        $teacher = $this->makeTeacher();
        $currentClass = $this->makeClass();
        $inactiveClass = $this->makeClass(false);
        $subject = $this->makeSubject();

        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/teaching-assignments', $this->assignmentPayload($teacher, $currentClass, $subject));
        $assignment = TeachingAssignment::where('teacher_id', $teacher->id)->firstOrFail();

        $this->actingAs($admin)
            ->put(
                '/admin/teaching-assignments/'.$assignment->id,
                $this->assignmentPayload($teacher, $inactiveClass, $subject),
            )
            ->assertSessionHasErrors('class_id');

        $this->assertSame($currentClass->id, $assignment->refresh()->class_id);
    }

    public function test_assignment_academic_year_is_derived_from_class(): void
    {
        $teacher = $this->makeTeacher();
        $class = $this->makeClass(true, '2029/2030');
        $subject = $this->makeSubject();

        $this->actingAs($this->admin())
            ->post('/admin/teaching-assignments', $this->assignmentPayload(
                $teacher,
                $class,
                $subject,
                ['academic_year' => '1999/2000'],
            ))
            ->assertRedirect('/admin/teaching-assignments');

        $this->assertDatabaseHas('teaching_assignments', [
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'academic_year' => '2029/2030',
        ]);
        $this->assertDatabaseMissing('teaching_assignments', [
            'teacher_id' => $teacher->id,
            'academic_year' => '1999/2000',
        ]);
    }

    public function test_changing_class_updates_assignment_academic_year(): void
    {
        $teacher = $this->makeTeacher();
        $oldClass = $this->makeClass(true, '2026/2027');
        $newClass = $this->makeClass(true, '2027/2028');
        $subject = $this->makeSubject();

        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/teaching-assignments', $this->assignmentPayload($teacher, $oldClass, $subject));
        $assignment = TeachingAssignment::where('teacher_id', $teacher->id)->firstOrFail();
        $this->assertSame('2026/2027', $assignment->academic_year);

        $this->actingAs($admin)
            ->put(
                '/admin/teaching-assignments/'.$assignment->id,
                $this->assignmentPayload($teacher, $newClass, $subject),
            )
            ->assertRedirect('/admin/teaching-assignments')
            ->assertSessionHasNoErrors();

        $assignment->refresh();
        $this->assertSame($newClass->id, $assignment->class_id);
        $this->assertSame('2027/2028', $assignment->academic_year);
    }

    public function test_updating_assignment_keeps_academic_year_consistent_with_class(): void
    {
        $teacher = $this->makeTeacher();
        $class = $this->makeClass(true, '2026/2027');
        $subject = $this->makeSubject();
        $otherSubject = $this->makeSubject();

        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/teaching-assignments', $this->assignmentPayload($teacher, $class, $subject));
        $assignment = TeachingAssignment::where('teacher_id', $teacher->id)->firstOrFail();

        $this->actingAs($admin)
            ->put(
                '/admin/teaching-assignments/'.$assignment->id,
                $this->assignmentPayload($teacher, $class, $otherSubject, ['academic_year' => '2001/2002']),
            )
            ->assertRedirect('/admin/teaching-assignments')
            ->assertSessionHasNoErrors();

        $assignment->refresh();
        $this->assertSame($class->id, $assignment->class_id);
        $this->assertSame('2026/2027', $assignment->academic_year);
        $this->assertSame($otherSubject->id, $assignment->subject_id);
    }

    public function test_assignment_academic_year_filter_still_works(): void
    {
        $classA = $this->makeClass(true, '2026/2027');
        $classB = $this->makeClass(true, '2027/2028');
        $subject = $this->makeSubject();

        TeachingAssignment::create($this->assignmentPayload($this->makeTeacher(), $classA, $subject, ['academic_year' => $classA->academic_year]));
        TeachingAssignment::create($this->assignmentPayload($this->makeTeacher(), $classB, $subject, ['academic_year' => $classB->academic_year]));

        $this->actingAs($this->admin())
            ->get('/admin/teaching-assignments?academic_year=2027/2028')
            ->assertOk()
            ->assertViewHas(
                'assignments',
                fn ($assignments) => $assignments->total() === 1
                    && $assignments->first()->academic_year === '2027/2028',
            );
    }

    public function test_admin_can_open_academic_forms(): void
    {
        $teacher = $this->makeTeacher();
        $class = $this->makeClass();
        $subject = $this->makeSubject();
        $assignment = TeachingAssignment::create($this->assignmentPayload($teacher, $class, $subject));

        $admin = $this->admin();

        foreach ([
            '/admin/classes/create',
            '/admin/classes/'.$class->id.'/edit',
            '/admin/subjects/create',
            '/admin/subjects/'.$subject->id.'/edit',
            '/admin/teaching-assignments/create',
            '/admin/teaching-assignments/'.$assignment->id.'/edit',
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_teacher_cannot_access_academic_modules(): void
    {
        $teacher = User::create([
            'username' => 'guru_block_'.uniqid(),
            'password' => self::PASSWORD,
            'role' => 'teacher',
            'is_active' => true,
        ]);

        foreach (['/admin/classes', '/admin/subjects', '/admin/teaching-assignments'] as $url) {
            $this->actingAs($teacher)->get($url)->assertForbidden();
        }

        $this->actingAs($teacher)
            ->post('/admin/classes', ['name' => 'X', 'academic_year' => '2026/2027'])
            ->assertForbidden();
    }

    public function test_student_cannot_access_academic_modules(): void
    {
        $student = User::create([
            'username' => 'siswa_block_'.uniqid(),
            'password' => self::PASSWORD,
            'role' => 'student',
            'is_active' => true,
        ]);

        foreach (['/admin/classes', '/admin/subjects', '/admin/teaching-assignments'] as $url) {
            $this->actingAs($student)->get($url)->assertForbidden();
        }

        $this->actingAs($student)
            ->post('/admin/subjects', ['code' => 'X', 'name' => 'Y'])
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login_from_academic_modules(): void
    {
        foreach (['/admin/classes', '/admin/subjects', '/admin/teaching-assignments'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }
}
