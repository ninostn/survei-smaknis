<?php

namespace App\Http\Controllers;
use App\Models\Survey;
use App\Models\Response;
use App\Models\Answer;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;


class MuridSurveyController extends Controller
{
    // Menampilkan survei aktif
    public function index()
    {
        $surveys = Survey::where('status', 'aktif')
            ->withCount('questions')
            ->with(['responses' => function ($query) {
                $query->where('user_id', Auth::id());
            }])
            ->latest()
            ->get();

        return view('murid.surveys.index', compact('surveys'));
    }

    // Menampilkan riwayat survei yang sudah diisi murid
    public function history()
    {
        $responses = Response::with('survey')
            ->where('user_id', Auth::id())
            ->latest('submitted_at')
            ->get();

        return view('murid.surveys.history', compact('responses'));
    }

    // Menampilkan soal survei
    public function show(Survey $survey)
    {
        if ($survey->status !== 'aktif') {
            abort(403, 'Survey Ditutup');
        }

        $sudahDiisi = $survey->responses()
            ->where('user_id', Auth::id())
            ->exists();

        if ($sudahDiisi) {
            return redirect()
                ->route('murid.surveys.index')
                ->with('error', 'Kamu sudah mengisi survei ini.');
        }

        $this->loadQuestions($survey);

        return view('murid.surveys.show', compact('survey'));
    }

    // Menyimpan jawaban murid
    public function store(Request $request, Survey $survey)
    {
        if ($survey->status !== 'aktif') {
            abort(403, 'Survey Ditutup');
        }

        $sudahDiisi = $survey->responses()
            ->where('user_id', Auth::id())
            ->exists();

        if ($sudahDiisi) {
            return redirect()
                ->route('murid.surveys.index')
                ->with('error', 'Kamu sudah mengisi survei ini.');
        }

        $this->loadQuestions($survey);

        if ($survey->questions->isEmpty()) {
            return redirect()
                ->route('murid.surveys.index')
                ->with('error', 'Survei ini belum memiliki soal.');
        }

        $rules = [];

        foreach ($survey->questions as $question) {
            $field = 'jawaban.' . $question->id;

            if ($question->type === 'essay') {
                $rules[$field] = 'required|string|max:5000';
            } else {
                $rules[$field] = [
                    'required',
                    'integer',
                    Rule::exists('options', 'id')
                        ->where('question_id', $question->id),
                ];
            }
        }

        $validated = $request->validate($rules);

        try {
            DB::transaction(function () use ($survey, $validated) {
                $response = Response::create([
                    'survey_id' => $survey->id,
                    'user_id' => Auth::id(),
                    'submitted_at' => now(),
                ]);

                foreach ($survey->questions as $question) {
                    $value = $validated['jawaban'][$question->id];

                    Answer::create([
                        'response_id' => $response->id,
                        'question_id' => $question->id,
                        'option_id' => $question->type === 'essay'
                            ? null
                            : (int) $value,
                        'teks' => $question->type === 'essay'
                            ? $value
                            : null,
                    ]);
                }
            });
        } catch (UniqueConstraintViolationException $e) {
            // Dua submit bersamaan: unique (survey_id, user_id) menolak yang kedua.
            if (! str_contains($e->getMessage(), 'uq_response_once')) {
                throw $e;
            }

            return redirect()
                ->route('murid.surveys.index')
                ->with('error', 'Kamu sudah mengisi survei ini.');
        }

        return redirect()
            ->route('murid.surveys.index')
            ->with('success', 'Jawaban survei berhasil disimpan.');
    }

    // Soal selalu tampil urut kolom `urutan`, bukan urut id.
    private function loadQuestions(Survey $survey): void
    {
        $survey->load([
            'questions' => fn ($query) => $query->orderBy('urutan')->orderBy('id'),
            'questions.options' => fn ($query) => $query->orderBy('id'),
        ]);
    }
}

