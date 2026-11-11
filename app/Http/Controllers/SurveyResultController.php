<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesSurveyDraft;
use App\Models\Survey;
use App\Support\SurveyResultAggregator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Hasil survei untuk admin, creator, dan responden.
 */
class SurveyResultController extends Controller
{
    use AuthorizesSurveyDraft;

    private const TEXT_PRIVACY_THRESHOLD = 5;

    public function show(Request $request, Survey $survey): View
    {
        $this->ensureTeacherProfile($request);
        $this->ensureOwnsSurvey($request, $survey);

        $survey->load(['category', 'creator:id,username', 'questions.options']);

        $totalResponses = $survey->responses()->count();

        $pages = $this->extractQuestionPages($request);

        $results = $survey->questions->map(function ($question) use ($pages) {
            return [
                'question' => $question,
                'aggregate' => SurveyResultAggregator::aggregateForQuestion($question, $pages),
            ];
        });

        return view('surveys.results', [
            'survey' => $survey,
            'totalResponses' => $totalResponses,
            'results' => $results,
        ]);
    }

    public function updatePublication(Request $request, Survey $survey): RedirectResponse
    {
        $this->ensureTeacherProfile($request);
        $this->ensureOwnsSurvey($request, $survey);

        $validated = $request->validate([
            'is_result_published' => ['required', 'boolean'],
        ]);

        $survey->update([
            'is_result_published' => $validated['is_result_published'],
        ]);

        $message = $validated['is_result_published']
            ? 'Hasil survei berhasil dipublikasikan.'
            : 'Hasil survei berhasil disembunyikan.';

        return redirect()->route('surveys.results.show', $survey)
            ->with('status', $message);
    }

    public function showForRespondent(Request $request, Survey $survey): View
    {
        $user = $request->user();

        abort_if(! $user->is_active, 403);

        if ($user->role === 'student') {
            abort_if($user->student === null, 403);
        } elseif ($user->role === 'teacher') {
            abort_if($user->teacher === null, 403);
        } else {
            abort(403);
        }

        abort_if(! in_array($survey->status, ['published', 'closed'], true), 403);
        abort_if(! $survey->is_result_published, 403);

        $hasResponse = $survey->responses()->where('user_id', $user->id)->exists();
        abort_if(! $hasResponse, 403);

        $survey->load(['category', 'questions.options']);

        $totalResponses = $survey->responses()->count();
        $hideTextAnswers = $totalResponses < self::TEXT_PRIVACY_THRESHOLD;

        $pages = $this->extractQuestionPages($request);

        $results = $survey->questions->map(function ($question) use ($pages, $hideTextAnswers) {
            $aggregate = SurveyResultAggregator::aggregateForQuestion($question, $pages);

            if (in_array($question->type, ['short_text', 'long_text'], true) && $hideTextAnswers) {
                $aggregate['answers'] = $aggregate['answers']->setCollection(collect([]));
            }

            return [
                'question' => $question,
                'aggregate' => $aggregate,
            ];
        });

        return view('surveys.results', [
            'survey' => $survey,
            'totalResponses' => $totalResponses,
            'results' => $results,
            'isRespondentView' => true,
            'hideTextAnswers' => $hideTextAnswers,
        ]);
    }

    /**
     * @return array<int, int>
     */
    private function extractQuestionPages(Request $request): array
    {
        $pages = [];

        foreach ($request->query() as $key => $value) {
            if (preg_match('/^q(\d+)_page$/', $key, $matches)) {
                $position = (int) $matches[1];
                $pages[$position] = max(1, (int) $value);
            }
        }

        return $pages;
    }
}
