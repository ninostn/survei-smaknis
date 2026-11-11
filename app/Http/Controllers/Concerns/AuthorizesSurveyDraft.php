<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Survey;
use Illuminate\Http\Request;

trait AuthorizesSurveyDraft
{
    private function ensureCanManageDraft(Request $request, Survey $survey): void
    {
        $this->ensureTeacherProfile($request);

        abort_if($survey->status !== 'draft', 403);

        $this->ensureOwnsSurvey($request, $survey);
    }

    private function ensureTeacherProfile(Request $request): void
    {
        $user = $request->user();

        abort_if($user->role === 'teacher' && $user->teacher === null, 403);
    }

    private function ensureOwnsSurvey(Request $request, Survey $survey): void
    {
        $user = $request->user();

        abort_if(
            $user->role === 'teacher' && $survey->creator_id !== $user->id,
            403,
        );
    }
}
