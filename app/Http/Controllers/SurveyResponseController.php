<?php

namespace App\Http\Controllers;

use App\Exceptions\DuplicateResponseException;
use App\Http\Requests\SubmitResponseRequest;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Response;
use App\Models\Survey;
use App\Models\User;
use App\Support\SurveyEligibility;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

/**
 * Halaman responden: daftar survei, detail, dan penyimpanan jawaban.
 */
class SurveyResponseController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $surveys = SurveyEligibility::availableFor($user)
            ->with(['category', 'creator:id,username'])
            ->orderBy('ends_at')
            ->orderBy('id')
            ->paginate(10);

        $submittedSurveysWithResults = Survey::query()
            ->whereIn('status', ['published', 'closed'])
            ->where('is_result_published', true)
            ->whereHas('responses', fn ($q) => $q->where('user_id', $user->id))
            ->with(['category', 'creator:id,username'])
            ->orderBy('ends_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(10, ['*'], 'submitted_page');

        return view('responses.index', [
            'surveys' => $surveys,
            'submittedSurveysWithResults' => $submittedSurveysWithResults,
        ]);
    }

    public function show(Request $request, Survey $survey): View
    {
        abort_unless(SurveyEligibility::allows($request->user(), $survey), 403);

        $survey->load(['category', 'creator:id,username', 'questions.options']);

        return view('responses.show', [
            'survey' => $survey,
        ]);
    }

    public function store(SubmitResponseRequest $request, Survey $survey): RedirectResponse
    {
        $user = $request->user();
        $answers = $request->validated('answers', []);

        try {
            DB::transaction(function () use ($user, $survey, $answers): void {
                // Kunci baris survei agar submit pada survei yang sama diserialkan.
                $locked = Survey::query()->lockForUpdate()->findOrFail($survey->id);

                // FormRequest sudah memeriksa; ulangi dengan baris terkunci.
                abort_unless(SurveyEligibility::allows($user, $locked), 403);

                $response = $this->createResponse($locked, $user);

                foreach ($locked->questions()->with('options')->get() as $question) {
                    $this->storeAnswer($response, $question, $answers[$question->id] ?? []);
                }
            });
        } catch (DuplicateResponseException) {
            return redirect()->route('responses.index')
                ->with('error', 'Anda sudah pernah mengisi survei ini.');
        }

        return redirect()->route('responses.index')
            ->with('status', 'Jawaban berhasil dikirim.');
    }

    private function createResponse(Survey $survey, User $user): Response
    {
        try {
            return Response::create([
                'survey_id' => $survey->id,
                'user_id' => $user->id,
                'submitted_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            throw new DuplicateResponseException('Respons survei sudah ada.');
        }
    }

    /**
     * @param  array<int|string, mixed>  $answer
     */
    private function storeAnswer(Response $response, Question $question, array $answer): void
    {
        if ($question->type === 'single_choice') {
            $optionId = $answer['option_id'] ?? null;

            if ($optionId === null || $optionId === '') {
                return;
            }

            $this->storeChoiceAnswer($response, $question, [(int) $optionId]);

            return;
        }

        if ($question->type === 'multiple_choice') {
            $optionIds = $answer['option_ids'] ?? null;

            if (! is_array($optionIds) || $optionIds === []) {
                return;
            }

            $this->storeChoiceAnswer($response, $question, array_map('intval', $optionIds));

            return;
        }

        if ($question->type === 'rating') {
            $rating = $answer['rating'] ?? null;

            if ($rating === null || $rating === '') {
                return;
            }

            Answer::create([
                'response_id' => $response->id,
                'question_id' => $question->id,
                'rating_value' => (int) $rating,
            ]);

            return;
        }

        $text = $answer['text'] ?? null;
        $text = is_string($text) ? trim($text) : null;

        if ($text === null || $text === '') {
            return;
        }

        Answer::create([
            'response_id' => $response->id,
            'question_id' => $question->id,
            'answer_text' => $text,
        ]);
    }

    /**
     * @param  array<int, int>  $optionIds
     */
    private function storeChoiceAnswer(Response $response, Question $question, array $optionIds): void
    {
        $optionIds = array_values(array_unique($optionIds));
        $validIds = $question->options->pluck('id')->all();

        foreach ($optionIds as $optionId) {
            if (! in_array($optionId, $validIds, true)) {
                throw new RuntimeException('Opsi jawaban tidak valid untuk pertanyaan ini.');
            }
        }

        $answer = Answer::create([
            'response_id' => $response->id,
            'question_id' => $question->id,
        ]);

        $answer->selectedOptions()->attach($optionIds, [
            'question_id' => $question->id,
        ]);
    }
}
