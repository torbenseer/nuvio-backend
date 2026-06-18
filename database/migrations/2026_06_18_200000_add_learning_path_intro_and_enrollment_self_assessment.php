<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_paths', function (Blueprint $table): void {
            $table->json('intro_explanations')->nullable()->after('estimated_minutes');
        });

        Schema::table('enrollments', function (Blueprint $table): void {
            $table->string('self_assessment', 16)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table): void {
            $table->dropColumn('self_assessment');
        });

        Schema::table('learning_paths', function (Blueprint $table): void {
            $table->dropColumn('intro_explanations');
        });
    }
};
