<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesSurveyDraft;
use App\Http\Requests\QuestionRequest;
use App\Models\Question;
use App\Models\Survey;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class QuestionController extends Controller
{
    use AuthorizesSurveyDraft;

    public function index(Request $request, Survey $survey): View
    {
        $this->ensureCanManageDraft($request, $survey);

        $survey->load(['questions.options']);

        return view('surveys.questions.index', [
            'survey' => $survey,
        ]);
    }

    public function create(Request $request, Survey $survey): View
    {
        $this->ensureCanManageDraft($request, $survey);

        return view('surveys.questions.create', [
            'survey' => $survey,
        ]);
    }

    public function store(QuestionRequest $request, Survey $survey): RedirectResponse
    {
        $this->ensureCanManageDraft($request, $survey);

        $validated = $request->validated();

        DB::transaction(function () use ($request, $survey, $validated): void {
            $locked = Survey::whereKey($survey->id)->lockForUpdate()->firstOrFail();

            $this->ensureCanManageDraft($request, $locked);

            $question = $locked->questions()->create([
                'question_text' => $validated['question_text'],
                'type' => $validated['type'],
                'is_required' => $validated['is_required'],
                'position' => ((int) $locked->questions()->max('position')) + 1,
            ]);

            $this->replaceOptions($question, $validated['option_texts'] ?? []);
        });

        return redirect()
            ->route('surveys.questions.index', $survey)
            ->with('status', 'Pertanyaan berhasil ditambahkan.');
    }

    public function edit(Request $request, Survey $survey, Question $question): View
    {
        $this->ensureCanManageDraft($request, $survey);
        $this->ensureQuestionBelongsToSurvey($survey, $question);

        $question->load('options');

        return view('surveys.questions.edit', [
            'survey' => $survey,
            'question' => $question,
        ]);
    }

    public function update(QuestionRequest $request, Survey $survey, Question $question): RedirectResponse
    {
        $this->ensureCanManageDraft($request, $survey);
        $this->ensureQuestionBelongsToSurvey($survey, $question);

        $validated = $request->validated();

        try {
            DB::transaction(function () use ($request, $survey, $question, $validated): void {
                $locked = Survey::whereKey($survey->id)->lockForUpdate()->firstOrFail();

                $this->ensureCanManageDraft($request, $locked);

                $lockedQuestion = Question::whereKey($question->id)->lockForUpdate()->firstOrFail();

                $this->ensureQuestionBelongsToSurvey($locked, $lockedQuestion);

                $lockedQuestion->update([
                    'question_text' => $validated['question_text'],
                    'type' => $validated['type'],
                    'is_required' => $validated['is_required'],
                ]);

                $lockedQuestion->options()->delete();
                $this->replaceOptions($lockedQuestion, $validated['option_texts'] ?? []);
            });
        } catch (QueryException $e) {
            return $this->optionRestrictedResponse($e, $survey);
        }

        return redirect()
            ->route('surveys.questions.index', $survey)
            ->with('status', 'Pertanyaan berhasil diperbarui.');
    }

    public function destroy(Request $request, Survey $survey, Question $question): RedirectResponse
    {
        $this->ensureCanManageDraft($request, $survey);
        $this->ensureQuestionBelongsToSurvey($survey, $question);

        try {
            DB::transaction(function () use ($request, $survey, $question): void {
                $locked = Survey::whereKey($survey->id)->lockForUpdate()->firstOrFail();

                $this->ensureCanManageDraft($request, $locked);

                $lockedQuestion = Question::whereKey($question->id)->lockForUpdate()->firstOrFail();

                $this->ensureQuestionBelongsToSurvey($locked, $lockedQuestion);

                $lockedQuestion->delete();
                $this->normalizePositions($locked);
            });
        } catch (QueryException $e) {
            return $this->optionRestrictedResponse($e, $survey);
        }

        return redirect()
            ->route('surveys.questions.index', $survey)
            ->with('status', 'Pertanyaan berhasil dihapus.');
    }

    public function move(Request $request, Survey $survey, Question $question, string $direction): RedirectResponse
    {
        $this->ensureCanManageDraft($request, $survey);
        $this->ensureQuestionBelongsToSurvey($survey, $question);

        abort_unless(in_array($direction, ['up', 'down'], true), 404);

        DB::transaction(function () use ($request, $survey, $question, $direction): void {
            $locked = Survey::whereKey($survey->id)->lockForUpdate()->firstOrFail();

            $this->ensureCanManageDraft($request, $locked);

            $ids = $locked->questions()
                ->orderBy('position')
                ->orderBy('id')
                ->pluck('id')
                ->all();

            $index = array_search($question->id, $ids, true);

            if ($index === false) {
                return;
            }

            $target = $direction === 'up' ? $index - 1 : $index + 1;

            if ($target < 0 || $target >= count($ids)) {
                return;
            }

            [$ids[$index], $ids[$target]] = [$ids[$target], $ids[$index]];

            foreach ($ids as $position => $id) {
                Question::whereKey($id)->update(['position' => $position + 1]);
            }
        });

        return redirect()->route('surveys.questions.index', $survey);
    }

    /**
     * @param  array<int, string>  $optionTexts
     */
    private function replaceOptions(Question $question, array $optionTexts): void
    {
        foreach (array_values($optionTexts) as $position => $optionText) {
            $question->options()->create([
                'option_text' => $optionText,
                'position' => $position + 1,
            ]);
        }
    }

    private function normalizePositions(Survey $survey): void
    {
        $ids = $survey->questions()
            ->orderBy('position')
            ->orderBy('id')
            ->pluck('id');

        foreach ($ids as $position => $id) {
            Question::whereKey($id)->update(['position' => $position + 1]);
        }
    }

    private function ensureQuestionBelongsToSurvey(Survey $survey, Question $question): void
    {
        abort_unless($question->survey_id === $survey->id, 404);
    }

    private function optionRestrictedResponse(QueryException $e, Survey $survey): RedirectResponse
    {
        if ((int) ($e->errorInfo[1] ?? 0) !== 1451) {
            throw $e;
        }

        return redirect()
            ->route('surveys.questions.index', $survey)
            ->withErrors(['question' => 'Pertanyaan tidak dapat diubah karena sudah memiliki jawaban.']);
    }
}
