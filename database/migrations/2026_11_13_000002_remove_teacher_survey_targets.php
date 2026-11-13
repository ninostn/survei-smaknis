<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('surveys')->update(['teacher_target_mode' => 'none']);
        DB::table('survey_target_teachers')->delete();
    }

    public function down(): void
    {
        // Teacher targets are intentionally not restored.
    }
};
