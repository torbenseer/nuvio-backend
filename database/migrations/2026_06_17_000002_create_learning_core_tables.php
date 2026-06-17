<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('learning_nodes', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('type')->default('skill');
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('learning_node_subject', function (Blueprint $table): void {
            $table->foreignId('learning_node_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->primary(['learning_node_id', 'subject_id']);
        });

        Schema::create('learning_paths', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('type')->default('subject_path');
            $table->integer('estimated_minutes')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('learning_path_nodes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('learning_path_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learning_node_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->timestamps();

            $table->unique(['learning_path_id', 'learning_node_id']);
            $table->unique(['learning_path_id', 'position']);
        });

        Schema::create('tasks', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('type')->default('numeric');
            $table->unsignedInteger('difficulty')->default(1);
            $table->unsignedInteger('estimated_minutes')->default(5);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('learning_node_task', function (Blueprint $table): void {
            $table->foreignId('learning_node_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(true);
            $table->primary(['learning_node_id', 'task_id']);
        });

        Schema::create('task_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->text('prompt');
            $table->json('input_schema');
            $table->json('answer_schema');
            $table->text('explanation');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['task_id', 'version']);
        });

        Schema::create('enrollments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learning_path_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('active');
            $table->timestamp('started_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'learning_path_id']);
        });

        Schema::create('reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learning_node_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('scheduled');
            $table->timestamp('due_at')->nullable();
            $table->unsignedInteger('interval_days')->nullable();
            $table->unsignedInteger('lapses')->default(0);
            $table->timestamp('last_attempted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'due_at']);
        });

        Schema::create('task_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_version_id')->constrained()->cascadeOnDelete();
            $table->foreignId('review_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('started');
            $table->string('result')->nullable();
            $table->json('answer')->nullable();
            $table->string('feedback_key')->nullable();
            $table->text('feedback_text')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('mastery_states', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learning_node_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('unknown');
            $table->timestamp('last_practiced_at')->nullable();
            $table->timestamp('retained_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'learning_node_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mastery_states');
        Schema::dropIfExists('task_attempts');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('task_versions');
        Schema::dropIfExists('learning_node_task');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('learning_path_nodes');
        Schema::dropIfExists('learning_paths');
        Schema::dropIfExists('learning_node_subject');
        Schema::dropIfExists('learning_nodes');
        Schema::dropIfExists('subjects');
    }
};
