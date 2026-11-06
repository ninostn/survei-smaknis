<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\DetectsDuplicateEntries;
use App\Http\Controllers\Controller;
use App\Http\Requests\TeachingAssignmentRequest;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeachingAssignmentController extends Controller
{
    use DetectsDuplicateEntries;

    public function index(Request $request): View
    {
        $teacherId = $request->integer('teacher_id') ?: null;
        $classId = $request->integer('class_id') ?: null;
        $subjectId = $request->integer('subject_id') ?: null;
        $year = trim((string) $request->query('academic_year'));

        $assignments = TeachingAssignment::query()
            ->with(['teacher', 'schoolClass', 'subject'])
            ->when($teacherId !== null, fn ($q) => $q->where('teacher_id', $teacherId))
            ->when($classId !== null, fn ($q) => $q->where('class_id', $classId))
            ->when($subjectId !== null, fn ($q) => $q->where('subject_id', $subjectId))
            ->when($year !== '', fn ($q) => $q->where('academic_year', $year))
            ->orderByDesc('academic_year')
            ->orderBy('teacher_id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.teaching-assignments.index', [
            'assignments' => $assignments,
            'teacherId' => $teacherId,
            'classId' => $classId,
            'subjectId' => $subjectId,
            'year' => $year,
            'teachers' => Teacher::orderBy('name')->get(),
            'classes' => SchoolClass::orderBy('name')->get(),
            'subjects' => Subject::orderBy('code')->get(),
            'years' => TeachingAssignment::query()->distinct()->orderByDesc('academic_year')->pluck('academic_year'),
        ]);
    }

    public function create(): View
    {
        return view('admin.teaching-assignments.create', $this->formOptions());
    }

    public function store(TeachingAssignmentRequest $request): RedirectResponse
    {
        try {
            TeachingAssignment::create($this->withAcademicYear($request));
        } catch (QueryException $e) {
            return $this->duplicateResponse($e);
        }

        return redirect()
            ->route('admin.teaching-assignments.index')
            ->with('status', 'Penugasan mengajar berhasil dibuat.');
    }

    public function edit(TeachingAssignment $assignment): View
    {
        $assignment->load(['teacher.user', 'schoolClass', 'subject']);

        return view('admin.teaching-assignments.edit', $this->formOptions() + [
            'assignment' => $assignment,
        ]);
    }

    public function update(TeachingAssignmentRequest $request, TeachingAssignment $assignment): RedirectResponse
    {
        try {
            $assignment->update($this->withAcademicYear($request));
        } catch (QueryException $e) {
            return $this->duplicateResponse($e);
        }

        return redirect()
            ->route('admin.teaching-assignments.index')
            ->with('status', 'Penugasan mengajar berhasil diperbarui.');
    }

    /**
     * Penugasan boleh dihapus: tidak ada tabel lain yang mereferensikannya.
     */
    public function destroy(TeachingAssignment $assignment): RedirectResponse
    {
        $assignment->delete();

        return redirect()
            ->route('admin.teaching-assignments.index')
            ->with('status', 'Penugasan mengajar dihapus.');
    }

    /**
     * Opsi form. Hanya guru dengan akun aktif, kelas aktif, dan semua
     * mata pelajaran (skema belum memiliki status aktif untuk mapel).
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'teachers' => Teacher::query()
                ->with('user')
                ->whereHas('user', fn ($q) => $q->where('role', 'teacher')->where('is_active', true))
                ->orderBy('name')
                ->get(),
            'classes' => SchoolClass::where('is_active', true)->orderBy('name')->get(),
            'subjects' => Subject::orderBy('code')->get(),
        ];
    }

    /**
     * `academic_year` selalu diturunkan dari kelas terpilih, bukan dari input
     * browser. Ini menjaga konsistensi dengan `classes.academic_year`.
     *
     * @return array<string, mixed>
     */
    private function withAcademicYear(TeachingAssignmentRequest $request): array
    {
        $data = $request->validated();
        $data['academic_year'] = SchoolClass::findOrFail($data['class_id'])->academic_year;

        return $data;
    }

    /**
     * Backstop untuk race condition pada unique kombinasi penugasan.
     */
    private function duplicateResponse(QueryException $e): RedirectResponse
    {
        if (! $this->isDuplicateEntry($e)) {
            throw $e;
        }

        return back()
            ->withInput()
            ->withErrors(['teacher_id' => 'Kombinasi guru, kelas, mata pelajaran, dan tahun akademik sudah ada.']);
    }
}
