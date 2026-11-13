<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
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

    private function activeClass(): SchoolClass
    {
        return SchoolClass::create([
            'name' => 'X TKJ '.uniqid(),
            'academic_year' => '2026/2027',
            'is_active' => true,
        ]);
    }

    private function teacherPayload(array $overrides = []): array
    {
        $suffix = uniqid();

        return array_merge([
            'username' => 'guru_'.$suffix,
            'name' => 'Guru Uji '.$suffix,
            'nip' => 'GURU-'.$suffix,
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
        ], $overrides);
    }

    private function studentPayload(SchoolClass $class, array $overrides = []): array
    {
        $suffix = uniqid();

        return array_merge([
            'username' => 'siswa_'.$suffix,
            'name' => 'Siswa Uji '.$suffix,
            'nis' => 'NIS-'.$suffix,
            'class_id' => $class->id,
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
        ], $overrides);
    }

    public function test_admin_can_view_account_list(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/users')
            ->assertOk()
            ->assertViewIs('admin.users.index')
            ->assertSee('Manajemen Akun');
    }

    public function test_admin_can_create_teacher_account(): void
    {
        $payload = $this->teacherPayload();

        $this->actingAs($this->admin())
            ->post('/admin/users/teachers', $payload)
            ->assertRedirect('/admin/users')
            ->assertSessionHas('status');

        $user = User::where('username', $payload['username'])->first();
        $this->assertNotNull($user);
        $this->assertSame('teacher', $user->role);
        $this->assertTrue($user->is_active);
        $this->assertDatabaseHas('teachers', [
            'user_id' => $user->id,
            'name' => $payload['name'],
            'nip' => $payload['nip'],
        ]);
    }

    public function test_admin_can_create_student_account(): void
    {
        $class = $this->activeClass();
        $payload = $this->studentPayload($class);

        $this->actingAs($this->admin())
            ->post('/admin/users/students', $payload)
            ->assertRedirect('/admin/users')
            ->assertSessionHas('status');

        $user = User::where('username', $payload['username'])->first();
        $this->assertNotNull($user);
        $this->assertSame('student', $user->role);
        $this->assertDatabaseHas('students', [
            'user_id' => $user->id,
            'name' => $payload['name'],
            'nis' => $payload['nis'],
            'class_id' => $class->id,
        ]);
    }

    public function test_duplicate_username_is_rejected(): void
    {
        $existing = $this->admin();

        $this->actingAs($existing)
            ->post('/admin/users/teachers', $this->teacherPayload(['username' => $existing->username]))
            ->assertSessionHasErrors('username');

        $this->assertSame(1, User::where('username', $existing->username)->count());
    }

    public function test_duplicate_nip_is_rejected(): void
    {
        $admin = $this->admin();
        $first = $this->teacherPayload();
        $this->actingAs($admin)->post('/admin/users/teachers', $first);

        $second = $this->teacherPayload(['nip' => $first['nip']]);

        $this->actingAs($admin)
            ->post('/admin/users/teachers', $second)
            ->assertSessionHasErrors('nip');

        $this->assertNull(User::where('username', $second['username'])->first());
    }

    public function test_duplicate_nis_is_rejected(): void
    {
        $admin = $this->admin();
        $class = $this->activeClass();
        $first = $this->studentPayload($class);
        $this->actingAs($admin)->post('/admin/users/students', $first);

        $second = $this->studentPayload($class, ['nis' => $first['nis']]);

        $this->actingAs($admin)
            ->post('/admin/users/students', $second)
            ->assertSessionHasErrors('nis');
    }

    public function test_inactive_class_is_rejected(): void
    {
        $inactive = SchoolClass::create([
            'name' => 'XII TKJ '.uniqid(),
            'academic_year' => '2025/2026',
            'is_active' => false,
        ]);

        $payload = $this->studentPayload($inactive);

        $this->actingAs($this->admin())
            ->post('/admin/users/students', $payload)
            ->assertSessionHasErrors('class_id');

        $this->assertNull(User::where('username', $payload['username'])->first());
    }

    public function test_short_password_is_accepted_for_teacher_and_student_and_reset(): void
    {
        $this->actingAs($this->admin());
        $password = 'a';
        $teacher = $this->teacherPayload(['password' => $password, 'password_confirmation' => $password]);
        $student = $this->studentPayload($this->activeClass(), ['password' => $password, 'password_confirmation' => $password]);

        $this->post('/admin/users/teachers', $teacher)->assertSessionHasNoErrors();
        $this->post('/admin/users/students', $student)->assertSessionHasNoErrors();

        foreach ([$teacher, $student] as $payload) {
            $user = User::where('username', $payload['username'])->firstOrFail();
            $this->assertTrue(Hash::check($password, $user->password));
            $this->patch('/admin/users/'.$user->id.'/reset-password', [
                'password' => 'b',
                'password_confirmation' => 'b',
            ])->assertSessionHasNoErrors();
            $this->assertTrue(Hash::check('b', $user->refresh()->password));
        }
    }

    public function test_password_confirmation_mismatch_is_rejected(): void
    {
        $payload = $this->teacherPayload([
            'password_confirmation' => 'Password-Beda-456',
        ]);

        $this->actingAs($this->admin())
            ->post('/admin/users/teachers', $payload)
            ->assertSessionHasErrors('password');

        $this->assertNull(User::where('username', $payload['username'])->first());
    }

    public function test_password_is_stored_hashed(): void
    {
        $payload = $this->teacherPayload();

        $this->actingAs($this->admin())->post('/admin/users/teachers', $payload);

        $user = User::where('username', $payload['username'])->firstOrFail();

        $this->assertNotSame(self::PASSWORD, $user->password);
        $this->assertStringStartsWith('$2y$', $user->password);
        $this->assertTrue(Hash::check(self::PASSWORD, $user->password));
    }

    public function test_editing_profile_does_not_change_password(): void
    {
        $admin = $this->admin();
        $class = $this->activeClass();
        $payload = $this->studentPayload($class);
        $this->actingAs($admin)->post('/admin/users/students', $payload);

        $user = User::where('username', $payload['username'])->firstOrFail();
        $hashBefore = $user->password;

        $this->actingAs($admin)
            ->put('/admin/users/'.$user->id, [
                'username' => $user->username,
                'name' => 'Nama Baru '.uniqid(),
                'nis' => $user->student->nis,
                'class_id' => $class->id,
                'is_active' => '1',
            ])
            ->assertRedirect('/admin/users/'.$user->id.'/edit');

        $this->assertSame($hashBefore, $user->refresh()->password);
    }

    public function test_reset_password_works(): void
    {
        $admin = $this->admin();
        $class = $this->activeClass();
        $payload = $this->studentPayload($class);
        $this->actingAs($admin)->post('/admin/users/students', $payload);

        $user = User::where('username', $payload['username'])->firstOrFail();
        $oldHash = $user->password;
        $newPassword = 'Password-Baru-789';

        $this->actingAs($admin)
            ->patch('/admin/users/'.$user->id.'/reset-password', [
                'password' => $newPassword,
                'password_confirmation' => $newPassword,
            ])
            ->assertRedirect('/admin/users/'.$user->id.'/edit');

        $user->refresh();
        $this->assertNotSame($oldHash, $user->password);
        $this->assertTrue(Hash::check($newPassword, $user->password));
        $this->assertFalse(Hash::check(self::PASSWORD, $user->password));
    }

    public function test_admin_can_deactivate_account(): void
    {
        $admin = $this->admin();
        $class = $this->activeClass();
        $payload = $this->studentPayload($class);
        $this->actingAs($admin)->post('/admin/users/students', $payload);

        $user = User::where('username', $payload['username'])->firstOrFail();
        $this->assertTrue($user->is_active);

        // Checkbox is_active tidak dikirim = nonaktif.
        $this->actingAs($admin)
            ->put('/admin/users/'.$user->id, [
                'username' => $user->username,
                'name' => $user->student->name,
                'nis' => $user->student->nis,
                'class_id' => $class->id,
            ])
            ->assertRedirect('/admin/users/'.$user->id.'/edit');

        $this->assertFalse($user->refresh()->is_active);
    }

    public function test_deactivated_account_cannot_login(): void
    {
        $admin = $this->admin();
        $class = $this->activeClass();
        $payload = $this->studentPayload($class);
        $this->actingAs($admin)->post('/admin/users/students', $payload);

        $user = User::where('username', $payload['username'])->firstOrFail();
        $this->actingAs($admin)->put('/admin/users/'.$user->id, [
            'username' => $user->username,
            'name' => $user->student->name,
            'nis' => $user->student->nis,
            'class_id' => $class->id,
        ]);
        $this->assertFalse($user->refresh()->is_active);

        // Akhiri sesi admin sebelum mencoba login sebagai guest.
        $this->post('/logout');
        $this->assertGuest();
        $this->app['auth']->forgetGuards();

        $this->from('/login')->post('/login', [
            'username' => $user->username,
            'password' => self::PASSWORD,
        ])->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_teacher_cannot_access_module(): void
    {
        $teacher = User::create([
            'username' => 'guru_block_'.uniqid(),
            'password' => self::PASSWORD,
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $this->actingAs($teacher)->get('/admin/users')->assertForbidden();
    }

    public function test_student_cannot_access_module(): void
    {
        $student = User::create([
            'username' => 'siswa_block_'.uniqid(),
            'password' => self::PASSWORD,
            'role' => 'student',
            'is_active' => true,
        ]);

        $this->actingAs($student)->get('/admin/users')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/users')->assertRedirect('/login');
    }

    public function test_admin_account_cannot_be_edited_through_module(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/users/'.$admin->id.'/edit')->assertForbidden();

        $this->actingAs($admin)
            ->put('/admin/users/'.$admin->id, [
                'username' => 'diubah_'.uniqid(),
                'name' => 'Diubah',
                'is_active' => '1',
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->patch('/admin/users/'.$admin->id.'/reset-password', [
                'password' => 'Password-Baru-789',
                'password_confirmation' => 'Password-Baru-789',
            ])
            ->assertForbidden();

        $this->assertSame($admin->username, $admin->refresh()->username);
    }
}
