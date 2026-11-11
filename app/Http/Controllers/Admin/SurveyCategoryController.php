<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SurveyCategoryRequest;
use App\Models\SurveyCategory;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SurveyCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));

        $categories = SurveyCategory::query()
            ->when($search !== '', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
            ->withCount('surveys')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.survey-categories.index', [
            'categories' => $categories,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('admin.survey-categories.create');
    }

    public function store(SurveyCategoryRequest $request): RedirectResponse
    {
        SurveyCategory::create($request->validated());

        return redirect()
            ->route('admin.survey-categories.index')
            ->with('status', 'Kategori survei berhasil dibuat.');
    }

    public function edit(SurveyCategory $category): View
    {
        return view('admin.survey-categories.edit', [
            'category' => $category,
        ]);
    }

    public function update(SurveyCategoryRequest $request, SurveyCategory $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()
            ->route('admin.survey-categories.index')
            ->with('status', 'Kategori survei berhasil diperbarui.');
    }

    /**
     * Kategori yang sudah dipakai survei tidak boleh dihapus. Pemeriksaan
     * aplikasi menjadi pesan utama; foreign key restrict tetap menjadi backstop.
     */
    public function destroy(SurveyCategory $category): RedirectResponse
    {
        if ($category->surveys()->exists()) {
            return $this->usedResponse();
        }

        try {
            $category->delete();
        } catch (QueryException $e) {
            // MySQL 1451: baris induk masih direferensikan foreign key.
            if ((int) ($e->errorInfo[1] ?? 0) !== 1451) {
                throw $e;
            }

            return $this->usedResponse();
        }

        return redirect()
            ->route('admin.survey-categories.index')
            ->with('status', 'Kategori survei dihapus.');
    }

    private function usedResponse(): RedirectResponse
    {
        return redirect()
            ->route('admin.survey-categories.index')
            ->withErrors(['category' => 'Kategori survei tidak dapat dihapus karena masih digunakan oleh survei.']);
    }
}
