<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use DatabaseTransactions;

    private const PASSWORD = 'Password-Testing-123';

    private array $usernames = [];

    private function makeUser(string $role, bool $isActive = true): User
    {
        $username = 'test_'.str_replace('.', '_', uniqid($role.'_', true));

        $user = User::create([
            'username' => $username,
            'password' => self::PASSWORD,
            'role' => $role,
            'is_active' => $isActive,
        ]);

        $this->usernames[] = $username;

        return $user;
    }

    public function test_admin_can_login_and_is_redirected_to_admin_dashboard(): void
    {
        $user = $this->makeUser('admin');

        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => self::PASSWORD,
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_teacher_can_login_and_is_redirected_to_teacher_dashboard(): void
    {
        $user = $this->makeUser('teacher');

        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => self::PASSWORD,
        ]);

        $response->assertRedirect('/teacher/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_student_can_login_and_is_redirected_to_student_dashboard(): void
    {
        $user = $this->makeUser('student');

        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => self::PASSWORD,
        ]);

        $response->assertRedirect('/student/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_is_rejected_with_generic_message(): void
    {
        $user = $this->makeUser('admin');

        $response = $this->from('/login')->post('/login', [
            'username' => $user->username,
            'password' => 'Salah-Password-123',
        ]);

        $response->assertRedirect('/login');
        $this->assertGuest();
        $response->assertSessionHasErrors('username');
        // Pesan error generik, tidak membedakan password salah vs username tak dikenal.
        $this->assertSame(
            ['Kredensial yang dimasukkan tidak valid.'],
            session('errors')->get('username'),
        );
    }

    public function test_unknown_username_is_rejected_with_generic_message(): void
    {
        $response = $this->from('/login')->post('/login', [
            'username' => 'tidak_ada_semacam_ini',
            'password' => self::PASSWORD,
        ]);

        $response->assertRedirect('/login');
        $this->assertGuest();
        $response->assertSessionHasErrors('username');
        $this->assertSame(
            ['Kredensial yang dimasukkan tidak valid.'],
            session('errors')->get('username'),
        );
    }

    public function test_inactive_account_cannot_login(): void
    {
        $user = $this->makeUser('teacher', false);

        $response = $this->from('/login')->post('/login', [
            'username' => $user->username,
            'password' => self::PASSWORD,
        ]);

        $response->assertRedirect('/login');
        $this->assertGuest();
        $response->assertSessionHasErrors('username');
    }

    public function test_guest_is_redirected_to_login_from_every_dashboard(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
        $this->get('/teacher/dashboard')->assertRedirect('/login');
        $this->get('/student/dashboard')->assertRedirect('/login');
    }

    public function test_admin_cannot_access_other_role_dashboards(): void
    {
        $user = $this->makeUser('admin');
        $this->actingAs($user);

        $this->get('/admin/dashboard')->assertOk();
        $this->get('/teacher/dashboard')->assertForbidden();
        $this->get('/student/dashboard')->assertForbidden();
    }

    public function test_teacher_cannot_access_other_role_dashboards(): void
    {
        $user = $this->makeUser('teacher');
        $this->actingAs($user);

        $this->get('/teacher/dashboard')->assertOk();
        $this->get('/admin/dashboard')->assertForbidden();
        $this->get('/student/dashboard')->assertForbidden();
    }

    public function test_student_cannot_access_other_role_dashboards(): void
    {
        $user = $this->makeUser('student');
        $this->actingAs($user);

        $this->get('/student/dashboard')->assertOk();
        $this->get('/admin/dashboard')->assertForbidden();
        $this->get('/teacher/dashboard')->assertForbidden();
    }

    public function test_logout_invalidates_session(): void
    {
        $user = $this->makeUser('admin');
        $this->actingAs($user);

        $response = $this->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_login_is_rate_limited_after_five_failures(): void
    {
        $user = $this->makeUser('student');

        for ($i = 0; $i < 5; $i++) {
            $this->from('/login')->post('/login', [
                'username' => $user->username,
                'password' => 'Salah-Password-123',
            ]);
            $this->assertGuest();
        }

        // Percobaan ke-6 dengan kredensial BENAR tetap ditolak rate limiter.
        // Pada request web, throttle dibalas redirect dengan pesan error,
        // bukan status HTTP 429.
        $response = $this->from('/login')->post('/login', [
            'username' => $user->username,
            'password' => self::PASSWORD,
        ]);

        $response->assertRedirect('/login');
        $this->assertGuest();
        $response->assertSessionHasErrors('username');
        $this->assertStringContainsString(
            'Terlalu banyak percobaan login',
            implode(' ', session('errors')->get('username')),
        );
    }
}
