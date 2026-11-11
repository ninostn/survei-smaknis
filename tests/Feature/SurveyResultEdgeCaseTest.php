<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Response;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Survey;
use App\Models\SurveyCategory;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Edge cases untuk hasil survei.
 */
class SurveyResultEdgeCaseTest extends TestCase
{
    use DatabaseTransactions;

    private const PASSWORD = 'Password-Awal-123';

    private Carbon $base;

    private ?SurveyCategory $category = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->base = Carbon::parse('2026-11-10 10:00:00');
        Carbon::setTestNow($this->base);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function admin(): User
    {
        return User::create([
            'username' => 'admin_'.uniqid(),
            'password' => self::PASSWORD,
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    private function makeClass(): SchoolClass
    {
        return SchoolClass::create([
            'name' => 'Kelas '.uniqid(),
            'academic_year' => '2026/2027',
            'is_active' => true,
        ]);
    }

    private function studentUser(): User
    {
        $user = User::create([
            'username' => 'siswa_'.uniqid(),
            'password' => self::PASSWORD,
            'role' => 'student',
            'is_active' => true,
        ]);

        Student::create([
            'user_id' => $user->id,
            'nis' => 'NIS'.uniqid(),
            'name' => 'Siswa '.uniqid(),
            'class_id' => $this->makeClass()->id,
        ]);

        return $user;
    }

    private function teacherUser(): User
    {
        $user = User::create([
            'username' => 'guru_'.uniqid(),
            'password' => self::PASSWORD,
            'role' => 'teacher',
            'is_active' => true,
        ]);

        Teacher::create([
            'user_id' => $user->id,
            'name' => 'Guru '.uniqid(),
        ]);

        return $user;
    }

    private function makeCategory(): SurveyCategory
    {
        return $this->category ??= SurveyCategory::create([
            'name' => 'Kategori '.uniqid(),
        ]);
    }

    private function makeSurvey(array $overrides = []): Survey
    {
        $creator = $overrides['creator_id'] ?? $this->admin()->id;
        unset($overrides['creator_id']);

        return Survey::create(array_merge([
            'creator_id' => $creator,
            'category_id' => $this->makeCategory()->id,
            'title' => 'Survei '.uniqid(),
            'student_target_mode' => 'all',
            'teacher_target_mode' => 'none',
            'status' => 'published',
            'starts_at' => $this->base->copy()->subHour(),
            'ends_at' => $this->base->copy()->addHour(),
            'is_result_published' => false,
        ], $overrides));
    }

    public function test_optional_single_choice_with_partial_responses(): void
    {
        $admin = $this->admin();
        $survey = $this->makeSurvey(['creator_id' => $admin->id]);

        $question = Question::create([
            'survey_id' => $survey->id,
            'question_text' => 'Pilihan opsional',
            'type' => 'single_choice',
            'is_required' => false,
            'position' => 1,
        ]);

        $optionA = QuestionOption::create([
            'question_id' => $question->id,
            'option_text' => 'Opsi A',
            'position' => 1,
        ]);

        $optionB = QuestionOption::create([
            'question_id' => $question->id,
            'option_text' => 'Opsi B',
            'position' => 2,
        ]);

        for ($i = 0; $i < 20; $i++) {
            $student = $this->studentUser();
            $response = Response::create([
                'survey_id' => $survey->id,
                'user_id' => $student->id,
                'submitted_at' => now(),
            ]);

            if ($i < 10) {
                $answer = Answer::create([
                    'response_id' => $response->id,
                    'question_id' => $question->id,
                ]);

                $option = $i < 5 ? $optionA : $optionB;
                $answer->selectedOptions()->attach($option->id, ['question_id' => $question->id]);
            }
        }

        $html = $this->actingAs($admin)
            ->get(route('surveys.results.show', $survey))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/10\s+jawaban/', $html);
        $this->assertStringContainsString('Opsi A', $html);
        $this->assertStringContainsString('Opsi B', $html);
        $this->assertStringContainsString('5 (50', $html);
        $this->assertStringContainsString('(Opsional)', $html);
    }

    public function test_optional_multiple_choice_with_partial_responses(): void
    {
        $admin = $this->admin();
        $survey = $this->makeSurvey(['creator_id' => $admin->id]);

        $question = Question::create([
            'survey_id' => $survey->id,
            'question_text' => 'Pilihan ganda opsional',
            'type' => 'multiple_choice',
            'is_required' => false,
            'position' => 1,
        ]);

        $optionA = QuestionOption::create([
            'question_id' => $question->id,
            'option_text' => 'A',
            'position' => 1,
        ]);

        $optionB = QuestionOption::create([
            'question_id' => $question->id,
            'option_text' => 'B',
            'position' => 2,
        ]);

        for ($i = 0; $i < 15; $i++) {
            $student = $this->studentUser();
            $response = Response::create([
                'survey_id' => $survey->id,
                'user_id' => $student->id,
                'submitted_at' => now(),
            ]);

            if ($i < 10) {
                $answer = Answer::create([
                    'response_id' => $response->id,
                    'question_id' => $question->id,
                ]);

                if ($i < 6) {
                    $answer->selectedOptions()->attach($optionA->id, ['question_id' => $question->id]);
                }

                if ($i >= 3 && $i < 10) {
                    $answer->selectedOptions()->attach($optionB->id, ['question_id' => $question->id]);
                }
            }
        }

        $html = $this->actingAs($admin)
            ->get(route('surveys.results.show', $survey))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/10\s+responden menjawab/', $html);
        $this->assertStringContainsString('(Opsional)', $html);
    }

    public function test_pagination_per_question_independent(): void
    {
        $admin = $this->admin();
        $survey = $this->makeSurvey(['creator_id' => $admin->id]);

        $q1 = Question::create([
            'survey_id' => $survey->id,
            'question_text' => 'Pertanyaan 1',
            'type' => 'short_text',
            'is_required' => false,
            'position' => 1,
        ]);

        $q2 = Question::create([
            'survey_id' => $survey->id,
            'question_text' => 'Pertanyaan 2',
            'type' => 'long_text',
            'is_required' => false,
            'position' => 2,
        ]);

        for ($i = 0; $i < 15; $i++) {
            $student = $this->studentUser();
            $response = Response::create([
                'survey_id' => $survey->id,
                'user_id' => $student->id,
                'submitted_at' => now(),
            ]);

            Answer::create([
                'response_id' => $response->id,
                'question_id' => $q1->id,
                'answer_text' => 'Q1 Jawaban '.$i,
            ]);

            Answer::create([
                'response_id' => $response->id,
                'question_id' => $q2->id,
                'answer_text' => 'Q2 Jawaban '.$i,
            ]);
        }

        $html = $this->actingAs($admin)
            ->get(route('surveys.results.show', $survey).'?q1_page=2&q2_page=1')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Q1 Jawaban 10', $html);
        $this->assertStringNotContainsString('Q1 Jawaban 0', $html);
        $this->assertStringContainsString('Q2 Jawaban 0', $html);
        $this->assertStringNotContainsString('Q2 Jawaban 10', $html);
        $this->assertStringContainsString('q1_page=', $html);
        $this->assertStringContainsString('q2_page=', $html);
    }

    public function test_respondent_view_respects_threshold_per_question(): void
    {
        $survey = $this->makeSurvey(['is_result_published' => true]);

        $textQ = Question::create([
            'survey_id' => $survey->id,
            'question_text' => 'Teks',
            'type' => 'short_text',
            'is_required' => false,
            'position' => 1,
        ]);

        $ratingQ = Question::create([
            'survey_id' => $survey->id,
            'question_text' => 'Rating',
            'type' => 'rating',
            'is_required' => true,
            'position' => 2,
        ]);

        $students = [];
        for ($i = 0; $i < 5; $i++) {
            $student = $this->studentUser();
            $students[] = $student;
            $response = Response::create([
                'survey_id' => $survey->id,
                'user_id' => $student->id,
                'submitted_at' => now(),
            ]);

            Answer::create([
                'response_id' => $response->id,
                'question_id' => $ratingQ->id,
                'rating_value' => 3,
            ]);

            if ($i < 2) {
                Answer::create([
                    'response_id' => $response->id,
                    'question_id' => $textQ->id,
                    'answer_text' => 'Jawaban teks '.$i,
                ]);
            }
        }

        $html = $this->actingAs($students[0])
            ->get(route('responses.results', $survey))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('dari 5', $html);
        $this->assertStringContainsString('Jawaban teks', $html);
    }

    public function test_optional_label_appears_correctly(): void
    {
        $admin = $this->admin();
        $survey = $this->makeSurvey(['creator_id' => $admin->id]);

        Question::create([
            'survey_id' => $survey->id,
            'question_text' => 'Wajib',
            'type' => 'short_text',
            'is_required' => true,
            'position' => 1,
        ]);

        $optionalQ = Question::create([
            'survey_id' => $survey->id,
            'question_text' => 'Tidak wajib',
            'type' => 'short_text',
            'is_required' => false,
            'position' => 2,
        ]);

        $student = $this->studentUser();
        $response = Response::create([
            'survey_id' => $survey->id,
            'user_id' => $student->id,
            'submitted_at' => now(),
        ]);

        Answer::create([
            'response_id' => $response->id,
            'question_id' => $optionalQ->id,
            'answer_text' => 'Jawaban opsional',
        ]);

        $html = $this->actingAs($admin)
            ->get(route('surveys.results.show', $survey))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Wajib', $html);
        $this->assertStringContainsString('Tidak wajib', $html);
        $this->assertStringContainsString('(Opsional)', $html);
        $this->assertSame(1, substr_count($html, '(Opsional)'));
    }
}
