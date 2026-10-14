<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SurveyController extends Controller
{
    public function index()
    {
        if (Auth::user()->role === 'admin') {
            $surveys = Survey::withCount(['questions', 'responses'])
                ->latest()
                ->get();
        } else {
            $surveys = Survey::withCount(['questions', 'responses'])
                ->where('created_by', Auth::id())
                ->latest()
                ->get();
        }

        return view('surveys.index', compact('surveys'));
    }

    public function create()
    {
        return view('surveys.form', ['survey' => null]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'judul' => 'required|string|max:150',
            'deskripsi' => 'nullable|string',
        ]);

        $data['created_by'] = Auth::id();

        Survey::create($data);

        return redirect()->route('surveys.index')->with('ok', 'Survey dibuat.');
    }

    public function edit(Survey $survey)
    {
        $this->authorizeOwner($survey);

        return view('surveys.form', compact('survey'));
    }

    public function update(Request $request, Survey $survey)
    {
        $this->authorizeOwner($survey);

        $data = $request->validate([
            'judul' => 'required|string|max:150',
            'deskripsi' => 'nullable|string',
        ]);

        $survey->update($data);

        return redirect()->route('surveys.index')->with('ok', 'Survey diubah.');
    }

    public function destroy(Survey $survey)
    {
        $this->authorizeOwner($survey);

        $survey->delete();

        return redirect()->route('surveys.index')->with('ok', 'Survey dihapus.');
    }

    public function publish(Request $request, Survey $survey)
    {
        $this->authorizeOwner($survey);

        $data = $request->validate([
            'status' => 'required|in:aktif,nonaktif',
        ]);

        $survey->update($data);

        return redirect()->route('surveys.index')->with('ok', 'Status diubah.');
    }

    private function authorizeOwner(Survey $survey)
    {
        if (Auth::user()->role !== 'admin' && $survey->created_by !== Auth::id()) {
            abort(403, 'Bukan milikmu.');
        }
    }
}