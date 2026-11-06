<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\DetectsDuplicateEntries;
use App\Http\Controllers\Controller;
use App\Http\Requests\SchoolClassRequest;
use App\Models\SchoolClass;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SchoolClassController extends Controller
{
    use DetectsDuplicateEntries;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $year = trim((string) $request->query('academic_year'));
        $status = $request->query('status');
        $status = in_array($status, ['active', 'inactive'], true) ? $status : null;

        $classes = SchoolClass::query()
            ->when($search !== '', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
            ->when($year !== '', fn ($q) => $q->where('academic_year', $year))
            ->when($status !== null, fn ($q) => $q->where('is_active', $status === 'active'))
            ->withCount(['students', 'teachingAssignments'])
            ->orderByDesc('academic_year')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.classes.index', [
            'classes' => $classes,
            'search' => $search,
            'year' => $year,
            'status' => $status,
            'years' => SchoolClass::query()->distinct()->orderByDesc('academic_year')->pluck('academic_year'),
        ]);
    }

    public function create(): View
    {
        return view('admin.classes.create');
    }

    public function store(SchoolClassRequest $request): RedirectResponse
    {
        try {
            SchoolClass::create($request->validated() + ['is_active' => true]);
        } catch (QueryException $e) {
            return $this->duplicateResponse($e);
        }

        return redirect()
            ->route('admin.classes.index')
            ->with('status', 'Kelas berhasil dibuat.');
    }

    public function edit(SchoolClass $class): View
    {
        return view('admin.classes.edit', [
            'class' => $class,
        ]);
    }

    public function update(SchoolClassRequest $request, SchoolClass $class): RedirectResponse
    {
        try {
            $class->update($request->validated());
        } catch (QueryException $e) {
            return $this->duplicateResponse($e);
        }

        return redirect()
            ->route('admin.classes.index')
            ->with('status', 'Kelas berhasil diperbarui.');
    }

    /**
     * Aktifkan/nonaktifkan kelas. Kelas tidak pernah dihapus karena
     * siswa, penugasan mengajar, dan target survei masih mereferensikannya.
     */
    public function toggle(SchoolClass $class): RedirectResponse
    {
        $class->update(['is_active' => ! $class->is_active]);

        return redirect()
            ->route('admin.classes.index')
            ->with('status', $class->is_active ? 'Kelas diaktifkan.' : 'Kelas dinonaktifkan.');
    }

    /**
     * Backstop untuk race condition: validasi Form Request bisa lolos di dua
     * request bersamaan, lalu INSERT kedua melanggar unique constraint DB.
     */
    private function duplicateResponse(QueryException $e): RedirectResponse
    {
        if (! $this->isDuplicateEntry($e)) {
            throw $e;
        }

        return back()
            ->withInput()
            ->withErrors(['name' => 'Kombinasi nama kelas dan tahun akademik sudah ada.']);
    }
}
