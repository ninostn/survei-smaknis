<?php

namespace App\Support;

use App\Models\Question;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Agregasi hasil survei untuk admin dan creator.
 */
class SurveyResultAggregator
{
    /**
     * @param  array<int, int>  $pages  Map of question position to page number
     * @return array{type: string, total_responses: int, data: mixed}
     */
    public static function aggregateForQuestion(Question $question, array $pages = []): array
    {
        $page = $pages[$question->position] ?? 1;

        return match ($question->type) {
            'single_choice' => self::aggregateChoice($question, false),
            'multiple_choice' => self::aggregateChoice($question, true),
            'rating' => self::aggregateRating($question),
            'short_text', 'long_text' => self::aggregateText($question, $page),
        };
    }

    /**
     * @return array{type: string, total_responses: int, is_multiple: bool, options: array<int, array{id: int, text: string, count: int, percentage: float}>}
     */
    private static function aggregateChoice(Question $question, bool $isMultiple): array
    {
        $totalResponses = $question->answers()->count();

        $counts = DB::table('answer_options')
            ->join('answers', 'answer_options.answer_id', '=', 'answers.id')
            ->where('answers.question_id', $question->id)
            ->select('answer_options.option_id', DB::raw('COUNT(*) as count'))
            ->groupBy('answer_options.option_id')
            ->pluck('count', 'option_id');

        $options = $question->options->map(function ($option) use ($counts, $totalResponses) {
            $count = $counts[$option->id] ?? 0;
            $percentage = $totalResponses > 0 ? ($count / $totalResponses) * 100 : 0;

            return [
                'id' => $option->id,
                'text' => $option->option_text,
                'count' => $count,
                'percentage' => round($percentage, 1),
            ];
        })->all();

        return [
            'type' => $isMultiple ? 'multiple_choice' : 'single_choice',
            'total_responses' => $totalResponses,
            'is_multiple' => $isMultiple,
            'options' => $options,
        ];
    }

    /**
     * @return array{type: string, total_responses: int, average: float, distribution: array<int, int>}
     */
    private static function aggregateRating(Question $question): array
    {
        $ratings = $question->answers()
            ->whereNotNull('rating_value')
            ->pluck('rating_value');

        $totalResponses = $ratings->count();
        $average = $totalResponses > 0 ? $ratings->avg() : 0;

        $distribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $distribution[$i] = $ratings->filter(fn ($r) => $r === $i)->count();
        }

        return [
            'type' => 'rating',
            'total_responses' => $totalResponses,
            'average' => round($average, 2),
            'distribution' => $distribution,
        ];
    }

    /**
     * @return array{type: string, total_responses: int, answers: LengthAwarePaginator}
     */
    private static function aggregateText(Question $question, int $page): array
    {
        $totalResponses = $question->answers()
            ->whereNotNull('answer_text')
            ->where('answer_text', '!=', '')
            ->count();

        $pageName = 'q'.$question->position.'_page';

        $answers = $question->answers()
            ->whereNotNull('answer_text')
            ->where('answer_text', '!=', '')
            ->select('answer_text')
            ->orderBy('id')
            ->paginate(10, ['*'], $pageName, $page);

        return [
            'type' => 'text',
            'total_responses' => $totalResponses,
            'answers' => $answers,
        ];
    }
}
