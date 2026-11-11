<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesSurveyDraft;
use App\Http\Requests\PublishSurveyRequest;
use App\Http\Requests\SurveyRequest;
use App\Models\Survey;
use App\Models\SurveyCategory;
use App\Models\User;
use App\Support\SurveyTargetOptions;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SurveyController extends Controller
{
    use AuthorizesSurveyDraft;

    public function index(Request $request): View
    {
        $this->ensureTeacherProfile($request);

        $user = $request->user();

        $search = $request->query('search');
        $search = is_string($search) ? trim($search) : '';

        $status = $request->query('status');
        $status = is_string($status) && in_array($status, ['draft', 'published', 'closed'], true)
            ? $status
            : null;

        $categoryId = filter_var($request->query('category_id'), FILTER_VALIDATE_INT);
        $categoryId = $categoryId === false || $categoryId <= 0 ? null : $categoryId;

        $surveys = Survey::query()
            ->with(['creator', 'category'])
            ->withCount('responses')
            ->when($user->role === 'teacher', fn ($q) => $q->where('creator_id', $user->id))
            ->when($search !== '', fn ($q) => $q->where('title', 'like', '%'.$search.'%'))
            ->when($status !== null, fn ($q) => $q->where('status', $status))
            ->when($categoryId !== null, fn ($q) => $q->where('category_id', $categoryId))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('surveys.index', [
            'surveys' => $surveys,
            'search' => $search,
            'status' => $status,
            'categoryId' => $categoryId,
            'categories' => SurveyCategory::orderBy('name')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->ensureTeacherProfile($request);

        return view('surveys.create', $this->formOptions($request->user()));
    }

    public function edit(Request $request, Survey $survey): View
    {
        $this->ensureCanManageDraft($request, $survey);

        $survey->load(['targetClasses', 'targetTeachers']);

        return view('surveys.edit', $this->formOptions($request->user()) + [
            'survey' => $survey,
        ]);
    }

    public function store(SurveyRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated): void {
            $survey = Survey::create([
                'creator_id' => $request->user()->id,
                'category_id' => $validated['category_id'],
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'student_target_mode' => $validated['student_target_mode'],
                'teacher_target_mode' => $validated['teacher_target_mode'],
                'status' => 'draft',
                'starts_at' => null,
                'ends_at' => null,
                'is_result_published' => false,
            ]);

            $this->syncTargets($survey, $validated);
        });

        return redirect()
            ->route('surveys.index')
            ->with('status', 'Survei berhasil dibuat sebagai draft.');
    }

    public function update(SurveyRequest $request, Survey $survey): RedirectResponse
    {
        $this->ensureCanManageDraft($request, $survey);

        $validated = $request->validated();

        DB::transaction(function () use ($request, $survey, $validated): void {
            $locked = Survey::whereKey($survey->id)->lockForUpdate()->firstOrFail();

            $this->ensureCanManageDraft($request, $locked);

            $locked->update([
                'category_id' => $validated['category_id'],
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'student_target_mode' => $validated['student_target_mode'],
                'teacher_target_mode' => $validated['teacher_target_mode'],
            ]);

            $this->syncTargets($locked, $validated);
        });

        return redirect()
            ->route('surveys.index')
            ->with('status', 'Survei berhasil diperbarui.');
    }

    public function destroy(Request $request, Survey $survey): RedirectResponse
    {
        $this->ensureCanManageDraft($request, $survey);

        try {
            DB::transaction(function () use ($request, $survey): void {
                $locked = Survey::whereKey($survey->id)->lockForUpdate()->firstOrFail();

                $this->ensureCanManageDraft($request, $locked);

                $surveyId = $locked->id;

                $responseIds = DB::table('responses')
                    ->select('id')
                    ->where('survey_id', $surveyId);

                $questionIds = DB::table('questions')
                    ->select('id')
                    ->where('survey_id', $surveyId);

                $optionIds = DB::table('question_options')
                    ->select('id')
                    ->whereIn('question_id', $questionIds);

                $answerIds = DB::table('answers')
                    ->select('id')
                    ->whereIn('response_id', $responseIds)
                    ->orWhereIn('question_id', $questionIds);

                DB::table('answer_options')
                    ->whereIn('answer_id', $answerIds)
                    ->orWhereIn('question_id', $questionIds)
                    ->orWhereIn('option_id', $optionIds)
                    ->delete();

                DB::table('answers')
                    ->whereIn('response_id', $responseIds)
                    ->orWhereIn('question_id', $questionIds)
                    ->delete();

                DB::table('responses')->where('survey_id', $surveyId)->delete();
                DB::table('questions')->where('survey_id', $surveyId)->delete();

                $locked->delete();
            });
        } catch (QueryException $e) {
            if (($e->errorInfo[1] ?? null) !== 1451) {
                throw $e;
            }

            return redirect()
                ->route('surveys.index')
                ->withErrors(['destroy' => 'Survei tidak dapat dihapus karena masih memiliki data jawaban.']);
        }

        return redirect()
            ->route('surveys.index')
            ->with('status', 'Survei berhasil dihapus.');
    }

    public function publishForm(Request $request, Survey $survey): View
    {
        $this->ensureCanManageDraft($request, $survey);

        return view('surveys.publish', [
            'survey' => $survey,
            'issues' => $this->publishIssues($survey),
        ]);
    }

    public function publish(PublishSurveyRequest $request, Survey $survey): RedirectResponse
    {
        $this->ensureCanManageDraft($request, $survey);

        $validated = $request->validated();
        $issues = [];

        DB::transaction(function () use ($request, $survey, $validated, &$issues): void {
            $locked = Survey::whereKey($survey->id)->lockForUpdate()->firstOrFail();

            $this->ensureCanManageDraft($request, $locked);

            $issues = $this->publishIssues($locked);

            if ($issues !== []) {
                return;
            }

            $locked->update([
                'status' => 'published',
                'starts_at' => $validated['starts_at'],
                'ends_at' => $validated['ends_at'],
                'is_result_published' => false,
            ]);
        });

        if ($issues !== []) {
            return redirect()
                ->route('surveys.publish.create', $survey)
                ->withErrors($issues)
                ->withInput();
        }

        return redirect()
            ->route('surveys.index')
            ->with('status', 'Survei berhasil dipublikasikan.');
    }

    public function close(Request $request, Survey $survey): RedirectResponse
    {
        $this->ensureTeacherProfile($request);

        DB::transaction(function () use ($request, $survey): void {
            $locked = Survey::whereKey($survey->id)->lockForUpdate()->firstOrFail();

            $this->ensureTeacherProfile($request);

            abort_if($locked->status !== 'published', 403);

            $this->ensureOwnsSurvey($request, $locked);

            $locked->update(['status' => 'closed']);
        });

        return redirect()
            ->route('surveys.index')
            ->with('status', 'Survei berhasil ditutup.');
    }

    /**
     * Periksa konsistensi pertanyaan, opsi, dan target responden dari data aktual.
     *
     * @return array<int, string>
     */
    private function publishIssues(Survey $survey): array
    {
        $survey->loadMissing(['creator.teacher', 'questions.options', 'targetClasses', 'targetTeachers.user']);

        $issues = [];

        if ($survey->questions->isEmpty()) {
            $issues[] = 'Survei minimal memiliki satu pertanyaan sebelum dipublikasikan.';
        }

        foreach ($survey->questions as $question) {
            if (! in_array($question->type, ['single_choice', 'multiple_choice'], true)) {
                continue;
            }

            $options = $question->options;
            $emptyOptions = $options->filter(
                fn ($option) => ! is_string($option->option_text) || trim($option->option_text) === '',
            );

            if ($options->count() < 2 || $emptyOptions->isNotEmpty()) {
                $issues[] = 'Pertanyaan pilihan "'.$question->question_text.'" harus memiliki minimal dua opsi jawaban yang tidak kosong.';
            }
        }

        if ($survey->student_target_mode === 'none' && $survey->teacher_target_mode === 'none') {
            $issues[] = 'Tentukan minimal satu target responden, siswa atau guru.';
        }

        $creator = $survey->creator;

        if ($survey->student_target_mode === 'all' && $creator?->role === 'teacher') {
            $issues[] = 'Guru tidak dapat menargetkan seluruh siswa.';
        }

        if ($survey->student_target_mode === 'classes') {
            if ($survey->targetClasses->isEmpty()) {
                $issues[] = 'Target siswa mode kelas belum memiliki kelas yang dipilih.';
            }

            $inactiveClasses = $survey->targetClasses->filter(fn ($class) => ! $class->is_active);

            if ($inactiveClasses->isNotEmpty()) {
                $issues[] = 'Kelas target tidak aktif: '.$inactiveClasses->pluck('name')->implode(', ').'.';
            }

            if ($creator?->role === 'teacher') {
                $teacher = $creator->teacher;

                if ($teacher === null) {
                    $issues[] = 'Akun guru pembuat survei tidak memiliki profil guru.';
                } else {
                    $taughtClassIds = $teacher->teachingAssignments()->pluck('class_id')->all();
                    $notTaught = $survey->targetClasses->reject(
                        fn ($class) => in_array($class->id, $taughtClassIds, true),
                    );

                    if ($notTaught->isNotEmpty()) {
                        $issues[] = 'Anda tidak lagi mengajar kelas target: '.$notTaught->pluck('name')->implode(', ').'.';
                    }
                }
            }
        }

        if ($survey->teacher_target_mode === 'selected') {
            if ($survey->targetTeachers->isEmpty()) {
                $issues[] = 'Target guru mode pilihan belum memiliki guru yang dipilih.';
            }

            $inactiveTeachers = $survey->targetTeachers->filter(
                fn ($teacher) => $teacher->user === null
                    || $teacher->user->role !== 'teacher'
                    || ! $teacher->user->is_active,
            );

            if ($inactiveTeachers->isNotEmpty()) {
                $issues[] = 'Guru target tidak aktif: '.$inactiveTeachers->pluck('name')->implode(', ').'.';
            }
        }

        return $issues;
    }

    /**
     * Opsi form yang sama untuk halaman buat dan edit.
     *
     * @return array<string, mixed>
     */
    private function formOptions(User $user): array
    {
        return [
            'categories' => SurveyCategory::orderBy('name')->get(),
            'classes' => SurveyTargetOptions::studentClasses($user),
            'teachers' => SurveyTargetOptions::selectableTeachers(),
            'canTargetAllStudents' => SurveyTargetOptions::canTargetAllStudents($user),
        ];
    }

    /**
     * Sinkronkan pivot sesuai mode. Dipanggil di dalam transaction.
     *
     * @param  array<string, mixed>  $validated
     */
    private function syncTargets(Survey $survey, array $validated): void
    {
        $survey->targetClasses()->sync(
            $validated['student_target_mode'] === 'classes'
                ? $validated['target_class_ids']
                : [],
        );

        $survey->targetTeachers()->sync(
            $validated['teacher_target_mode'] === 'selected'
                ? $validated['target_teacher_ids']
                : [],
        );
    }
}
