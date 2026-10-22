<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ResultController extends Controller
{
    /**
     * Hasil agregat satu survey: jumlah jawaban, rata-rata Likert, dan esai.
     * Hanya admin (semua survey) dan guru pemilik survey.
     */
    public function show(Survey $survey)
    {
        $this->authorizeOwner($survey);

        $counts = DB::table('answers')
            ->join('questions', 'answers.question_id', '=', 'questions.id')
            ->where('questions.survey_id', $survey->id)
            ->select(
                'questions.type as tipe',
                'questions.teks as pertanyaan',
                DB::raw('COUNT(answers.id) as jumlah_jawaban')
            )
            ->groupBy('questions.id', 'questions.type', 'questions.teks')
            ->orderByRaw('MIN(questions.urutan)')
            ->orderBy('questions.id')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        $avgLikert = DB::table('answers')
            ->join('questions', 'answers.question_id', '=', 'questions.id')
            ->join('options', 'answers.option_id', '=', 'options.id')
            ->where('questions.survey_id', $survey->id)
            ->where('questions.type', 'likert')
            ->select(
                'questions.teks as pertanyaan',
                DB::raw('ROUND(AVG(options.nilai), 2) as rata_rata'),
                DB::raw('COUNT(answers.id) as jumlah_penilai')
            )
            ->groupBy('questions.id', 'questions.teks')
            ->orderByRaw('MIN(questions.urutan)')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        $essays = DB::table('answers')
            ->join('questions', 'answers.question_id', '=', 'questions.id')
            ->join('responses', 'answers.response_id', '=', 'responses.id')
            ->leftJoin('users', 'responses.user_id', '=', 'users.id')
            ->where('questions.survey_id', $survey->id)
            ->where('questions.type', 'essay')
            ->select(
                'questions.teks as pertanyaan',
                'users.nama as penjawab',
                'answers.teks as jawaban',
                'responses.submitted_at'
            )
            ->orderByDesc('responses.submitted_at')
            ->limit(20)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        return view('results.show', compact('survey', 'counts', 'avgLikert', 'essays'));
    }

    private function authorizeOwner(Survey $survey): void
    {
        if (Auth::user()->role !== 'admin' && $survey->created_by !== Auth::id()) {
            abort(403, 'Bukan milikmu.');
        }
    }
}
