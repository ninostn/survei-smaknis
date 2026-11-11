<?php

namespace Tests\Feature;

use App\Models\Survey;
use App\Models\SurveyCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SurveyCategoryManagementTest extends TestCase
{
    use DatabaseTransactions;

    private const PASSWORD = 'Password-Awal-123';

    private function admin(): User
    {
        return User::create([
            'username' => 'admin_'.uniqid(),
            'password' => self::PASSWORD,
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    private function teacher(): User
    {
        return User::create([
            'username' => 'guru_'.uniqid(),
            'password' => self::PASSWORD,
            'role' => 'teacher',
            'is_active' => true,
        ]);
    }

    private function student(): User
    {
        return User::create([
            'username' => 'siswa_'.uniqid(),
            'password' => self::PASSWORD,
            'role' => 'student',
            'is_active' => true,
        ]);
    }

    private function makeCategory(?string $name = null, ?string $description = null): SurveyCategory
    {
        return SurveyCategory::create([
            'name' => $name ?? 'Kategori Uji '.uniqid(),
            'description' => $description,
        ]);
    }

    private function makeSurvey(SurveyCategory $category, User $creator): Survey
    {
        return Survey::create([
            'creator_id' => $creator->id,
            'category_id' => $category->id,
            'title' => 'Survei Uji '.uniqid(),
        ]);
    }

    public function test_admin_can_view_category_list(): void
    {
        $category = $this->makeCategory('Kategori Tampil '.uniqid());

        $this->actingAs($this->admin())
            ->get('/admin/survey-categories')
            ->assertOk()
            ->assertViewIs('admin.survey-categories.index')
            ->assertSee('Kategori Survei')
            ->assertSee($category->name);
    }

    public function test_admin_can_create_category(): void
    {
        $name = 'Kategori Baru '.uniqid();

        $this->actingAs($this->admin())
            ->post('/admin/survey-categories', [
                'name' => $name,
                'description' => 'Deskripsi kategori baru.',
            ])
            ->assertRedirect('/admin/survey-categories')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('survey_categories', [
            'name' => $name,
            'description' => 'Deskripsi kategori baru.',
        ]);
    }

    public function test_category_name_spaces_are_normalized(): void
    {
        $suffix = uniqid();

        $this->actingAs($this->admin())
            ->post('/admin/survey-categories', [
                'name' => '  Kategori   Spasi  '.$suffix.'  ',
                'description' => '  Deskripsi   dengan   spasi  ',
            ])
            ->assertRedirect('/admin/survey-categories');

        $this->assertDatabaseHas('survey_categories', [
            'name' => 'Kategori Spasi '.$suffix,
            'description' => 'Deskripsi dengan spasi',
        ]);
    }

    public function test_admin_can_edit_category(): void
    {
        $category = $this->makeCategory();
        $name = 'Kategori Edit '.uniqid();

        $this->actingAs($this->admin())
            ->put('/admin/survey-categories/'.$category->id, [
                'name' => $name,
                'description' => 'Deskripsi hasil edit.',
            ])
            ->assertRedirect('/admin/survey-categories')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('survey_categories', [
            'id' => $category->id,
            'name' => $name,
            'description' => 'Deskripsi hasil edit.',
        ]);
    }

    public function test_empty_category_name_is_rejected(): void
    {
        $before = SurveyCategory::count();

        $this->actingAs($this->admin())
            ->post('/admin/survey-categories', [
                'name' => '   ',
                'description' => 'Deskripsi.',
            ])
            ->assertSessionHasErrors('name');

        $this->assertSame($before, SurveyCategory::count());
    }

    public function test_duplicate_category_name_is_rejected_after_space_normalization(): void
    {
        $existing = $this->makeCategory('Kategori Duplikat '.uniqid());
        $before = SurveyCategory::count();

        $this->actingAs($this->admin())
            ->post('/admin/survey-categories', [
                'name' => '  '.str_replace(' ', '   ', $existing->name).'  ',
                'description' => 'Nama sama.',
            ])
            ->assertSessionHasErrors('name');

        $this->assertSame($before, SurveyCategory::count());
        $this->assertSame(1, SurveyCategory::where('name', $existing->name)->count());
    }

    public function test_category_can_keep_its_own_name_on_edit(): void
    {
        $category = $this->makeCategory('Kategori Tetap '.uniqid());

        $this->actingAs($this->admin())
            ->put('/admin/survey-categories/'.$category->id, [
                'name' => '  '.$category->name.'  ',
                'description' => 'Deskripsi diperbarui.',
            ])
            ->assertRedirect('/admin/survey-categories')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('survey_categories', [
            'id' => $category->id,
            'name' => $category->name,
            'description' => 'Deskripsi diperbarui.',
        ]);
    }

    public function test_category_description_is_optional(): void
    {
        $name = 'Kategori Tanpa Deskripsi '.uniqid();

        $this->actingAs($this->admin())
            ->post('/admin/survey-categories', [
                'name' => $name,
            ])
            ->assertRedirect('/admin/survey-categories')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('survey_categories', [
            'name' => $name,
            'description' => null,
        ]);
    }

    public function test_category_description_cannot_exceed_limit(): void
    {
        $before = SurveyCategory::count();

        $this->actingAs($this->admin())
            ->post('/admin/survey-categories', [
                'name' => 'Kategori Deskripsi Panjang '.uniqid(),
                'description' => str_repeat('a', 1001),
            ])
            ->assertSessionHasErrors('description');

        $this->assertSame($before, SurveyCategory::count());
    }

    public function test_admin_can_delete_unused_category(): void
    {
        $category = $this->makeCategory();

        $this->actingAs($this->admin())
            ->delete('/admin/survey-categories/'.$category->id)
            ->assertRedirect('/admin/survey-categories')
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('survey_categories', [
            'id' => $category->id,
        ]);
    }

    public function test_category_used_by_survey_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $category = $this->makeCategory();
        $survey = $this->makeSurvey($category, $admin);

        $this->actingAs($admin)
            ->delete('/admin/survey-categories/'.$category->id)
            ->assertRedirect('/admin/survey-categories')
            ->assertSessionHasErrors('category');

        $this->assertDatabaseHas('survey_categories', ['id' => $category->id]);
        $this->assertDatabaseHas('surveys', ['id' => $survey->id]);
    }

    public function test_teacher_cannot_access_any_category_route(): void
    {
        $teacher = $this->teacher();
        $category = $this->makeCategory();
        $before = SurveyCategory::count();

        $this->actingAs($teacher)->get('/admin/survey-categories')->assertForbidden();
        $this->actingAs($teacher)->get('/admin/survey-categories/create')->assertForbidden();
        $this->actingAs($teacher)->post('/admin/survey-categories', [
            'name' => 'Kategori Guru '.uniqid(),
        ])->assertForbidden();
        $this->actingAs($teacher)->get('/admin/survey-categories/'.$category->id.'/edit')->assertForbidden();
        $this->actingAs($teacher)->put('/admin/survey-categories/'.$category->id, [
            'name' => 'Kategori Guru Edit',
        ])->assertForbidden();
        $this->actingAs($teacher)->delete('/admin/survey-categories/'.$category->id)->assertForbidden();

        $this->assertSame($before, SurveyCategory::count());
        $this->assertDatabaseHas('survey_categories', ['id' => $category->id]);
    }

    public function test_student_cannot_access_any_category_route(): void
    {
        $student = $this->student();
        $category = $this->makeCategory();
        $before = SurveyCategory::count();

        $this->actingAs($student)->get('/admin/survey-categories')->assertForbidden();
        $this->actingAs($student)->get('/admin/survey-categories/create')->assertForbidden();
        $this->actingAs($student)->post('/admin/survey-categories', [
            'name' => 'Kategori Siswa '.uniqid(),
        ])->assertForbidden();
        $this->actingAs($student)->get('/admin/survey-categories/'.$category->id.'/edit')->assertForbidden();
        $this->actingAs($student)->put('/admin/survey-categories/'.$category->id, [
            'name' => 'Kategori Siswa Edit',
        ])->assertForbidden();
        $this->actingAs($student)->delete('/admin/survey-categories/'.$category->id)->assertForbidden();

        $this->assertSame($before, SurveyCategory::count());
        $this->assertDatabaseHas('survey_categories', ['id' => $category->id]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/survey-categories')->assertRedirect('/login');
    }

    public function test_category_name_must_be_a_string_on_create(): void
    {
        $before = SurveyCategory::count();

        $this->actingAs($this->admin())
            ->post('/admin/survey-categories', [
                'name' => ['Kategori Array'],
                'description' => 'Deskripsi.',
            ])
            ->assertSessionHasErrors('name');

        $this->assertSame($before, SurveyCategory::count());
    }

    public function test_extra_request_fields_are_ignored_on_create(): void
    {
        $name = 'Kategori Aman '.uniqid();

        $this->actingAs($this->admin())
            ->post('/admin/survey-categories', [
                'id' => 999999,
                'name' => $name,
                'description' => 'Deskripsi aman.',
                'surveys_count' => 100,
                'created_at' => '2000-01-01 00:00:00',
            ])
            ->assertRedirect('/admin/survey-categories');

        $category = SurveyCategory::where('name', $name)->firstOrFail();

        $this->assertNotSame(999999, $category->id);
        $this->assertNotSame('2000-01-01 00:00:00', $category->created_at->format('Y-m-d H:i:s'));
        $this->assertDatabaseHas('survey_categories', [
            'id' => $category->id,
            'name' => $name,
            'description' => 'Deskripsi aman.',
        ]);
    }

    public function test_category_search_filters_by_name(): void
    {
        $matching = $this->makeCategory('Pencarian Khusus '.uniqid());
        $other = $this->makeCategory('Kategori Lain '.uniqid());

        $this->actingAs($this->admin())
            ->get('/admin/survey-categories?search='.urlencode('Pencarian Khusus'))
            ->assertOk()
            ->assertSee($matching->name)
            ->assertDontSee($other->name);
    }
}
