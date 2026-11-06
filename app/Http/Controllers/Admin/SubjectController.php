<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\DetectsDuplicateEntries;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubjectRequest;
use App\Models\Subject;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubjectController extends Controller
{
    use DetectsDuplicateEntries;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));

        $subjects = Subject::query()
            ->when($search !== '', function ($q) use ($search) {
                $like = '%'.$search.'%';
                $q->where(fn ($w) => $w->where('code', 'like', $like)->orWhere('name', 'like', $like));
            })
            ->withCount('teachingAssignments')
            ->orderBy('code')
            ->paginate(10)
            ->withQueryString();

        return view('admin.subjects.index', [
            'subjects' => $subjects,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('admin.subjects.create');
    }

    public function store(SubjectRequest $request): RedirectResponse
    {
        try {
            Subject::create($request->validated());
        } catch (QueryException $e) {
            return $this->duplicateResponse($e);
        }

        return redirect()
            ->route('admin.subjects.index')
            ->with('status', 'Mata pelajaran berhasil dibuat.');
    }

    public function edit(Subject $subject): View
    {
        return view('admin.subjects.edit', [
            'subject' => $subject,
        ]);
    }

    public function update(SubjectRequest $request, Subject $subject): RedirectResponse
    {
        try {
            $subject->update($request->validated());
        } catch (QueryException $e) {
            return $this->duplicateResponse($e);
        }

        return redirect()
            ->route('admin.subjects.index')
            ->with('status', 'Mata pelajaran berhasil diperbarui.');
    }

    /**
     * Backstop untuk race condition pada unique `code`.
     */
    private function duplicateResponse(QueryException $e): RedirectResponse
    {
        if (! $this->isDuplicateEntry($e)) {
            throw $e;
        }

        return back()
            ->withInput()
            ->withErrors(['code' => 'Kode mata pelajaran sudah ada.']);
    }

    // Tidak ada aksi hapus: subjects tidak memiliki kolom is_active, dan
    // penghapusan data master yang dirujuk penugasan mengajar dilarang.
}
