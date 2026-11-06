<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResetAccountPasswordRequest;
use App\Http\Requests\StoreStudentAccountRequest;
use App\Http\Requests\StoreTeacherAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Daftar akun guru dan siswa.
     *
     * Akun admin tidak pernah ditampilkan di modul ini.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $role = $request->query('role');
        $role = in_array($role, ['teacher', 'student'], true) ? $role : null;
        $status = $request->query('status');
        $status = in_array($status, ['active', 'inactive'], true) ? $status : null;

        $users = User::query()
            ->whereIn('role', ['teacher', 'student'])
            ->with(['student.schoolClass', 'teacher'])
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where(function ($q) use ($like) {
                    $q->where('users.username', 'like', $like)
                        ->orWhereHas('student', fn ($s) => $s->where('name', 'like', $like)->orWhere('nis', 'like', $like))
                        ->orWhereHas('teacher', fn ($t) => $t->where('name', 'like', $like)->orWhere('nip', 'like', $like));
                });
            })
            ->when($role !== null, fn ($query) => $query->where('role', $role))
            ->when($status !== null, fn ($query) => $query->where('is_active', $status === 'active'))
            ->orderBy('username')
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'search' => $search,
            'role' => $role,
            'status' => $status,
        ]);
    }

    public function create(Request $request): View
    {
        $role = $request->query('role') === 'student' ? 'student' : 'teacher';

        return view('admin.users.create', [
            'role' => $role,
            'classes' => SchoolClass::where('is_active', true)->orderBy('academic_year')->orderBy('name')->get(),
        ]);
    }

    /**
     * Buat akun guru beserta profilnya dalam satu transaksi.
     */
    public function storeTeacher(StoreTeacherAccountRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated): void {
            $user = User::create([
                'username' => $validated['username'],
                'password' => $validated['password'], // otomatis di-hash oleh cast 'hashed'
                'role' => 'teacher',
                'is_active' => true,
            ]);

            Teacher::create([
                'user_id' => $user->id,
                'name' => $validated['name'],
                'nip' => $validated['nip'] ?? null,
            ]);
        });

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Akun guru berhasil dibuat.');
    }

    /**
     * Buat akun siswa beserta profilnya dalam satu transaksi.
     */
    public function storeStudent(StoreStudentAccountRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated): void {
            $user = User::create([
                'username' => $validated['username'],
                'password' => $validated['password'],
                'role' => 'student',
                'is_active' => true,
            ]);

            Student::create([
                'user_id' => $user->id,
                'name' => $validated['name'],
                'nis' => $validated['nis'],
                'class_id' => $validated['class_id'],
            ]);
        });

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Akun siswa berhasil dibuat.');
    }

    public function edit(User $user): View
    {
        // Akun admin (termasuk milik sendiri) tidak dapat diedit di modul ini.
        abort_if($user->role === 'admin', 403);

        $user->load(['student.schoolClass', 'teacher']);

        return view('admin.users.edit', [
            'account' => $user,
            'classes' => SchoolClass::where('is_active', true)->orderBy('academic_year')->orderBy('name')->get(),
        ]);
    }

    /**
     * Perbarui profil akun. Password tidak pernah diubah di sini.
     */
    public function update(UpdateAccountRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $user, $validated): void {
            $user->username = $validated['username'];
            $user->is_active = $request->boolean('is_active');

            if ($user->role === 'teacher' && $user->teacher !== null) {
                $user->teacher->name = $validated['name'];
                $user->teacher->nip = $validated['nip'] ?? null;
                $user->teacher->save();
            } elseif ($user->student !== null) {
                $user->student->name = $validated['name'];
                $user->student->nis = $validated['nis'];
                $user->student->class_id = $validated['class_id'];
                $user->student->save();
            }

            $user->save();
        });

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('status', 'Data akun berhasil diperbarui.');
    }

    /**
     * Reset password oleh admin. Password lama tidak pernah diambil atau
     * ditampilkan; password baru tidak ditampilkan setelah disimpan.
     */
    public function resetPassword(ResetAccountPasswordRequest $request, User $user): RedirectResponse
    {
        $user->password = $request->validated('password'); // di-hash oleh cast
        $user->save();

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('status', 'Password berhasil direset.');
    }
}
