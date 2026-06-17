<?php

namespace Tests\Unit;

use App\Models\TaskVersion;
use App\Services\Tasks\TaskGrader;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class TaskGraderTest extends TestCase
{
    use RefreshDatabase;

    public function test_numeric_exact_answer_is_correct(): void
    {
        $this->seed(DatabaseSeeder::class);
        $version = TaskVersion::query()->firstOrFail();

        $result = app(TaskGrader::class)->grade($version, ['value' => 4]);

        $this->assertSame('correct', $result['result']);
        $this->assertSame('numeric_correct', $result['feedback_key']);
    }

    public function test_numeric_wrong_answer_is_incorrect(): void
    {
        $this->seed(DatabaseSeeder::class);
        $version = TaskVersion::query()->firstOrFail();

        $result = app(TaskGrader::class)->grade($version, ['value' => 3]);

        $this->assertSame('incorrect', $result['result']);
        $this->assertSame('numeric_incorrect_review', $result['feedback_key']);
    }

    public function test_numeric_answer_respects_tolerance(): void
    {
        $this->seed(DatabaseSeeder::class);
        $version = TaskVersion::query()->firstOrFail();
        $version->forceFill([
            'answer_schema' => ['correct_value' => 4, 'tolerance' => 0.1],
        ])->save();

        $result = app(TaskGrader::class)->grade($version->refresh(), ['value' => 4.05]);

        $this->assertSame('correct', $result['result']);
    }

    public function test_feedback_text_comes_from_task_version_explanation(): void
    {
        $this->seed(DatabaseSeeder::class);
        $version = TaskVersion::query()->firstOrFail();
        $version->forceFill([
            'answer_schema' => ['correct_value' => 9, 'tolerance' => 0],
            'explanation' => 'Addiere zuerst 4 und teile dann durch 3.',
        ])->save();

        $correct = app(TaskGrader::class)->grade($version->refresh(), ['value' => 9]);
        $incorrect = app(TaskGrader::class)->grade($version->refresh(), ['value' => 8]);

        $this->assertStringContainsString('Addiere zuerst 4 und teile dann durch 3.', $correct['feedback_text']);
        $this->assertStringContainsString('Addiere zuerst 4 und teile dann durch 3.', $incorrect['feedback_text']);
        $this->assertStringNotContainsString('Ziehe zuerst 3 ab', $correct['feedback_text']);
        $this->assertStringNotContainsString('Ziehe zuerst 3 ab', $incorrect['feedback_text']);
    }

    public function test_numeric_grader_rejects_missing_numeric_value(): void
    {
        $this->seed(DatabaseSeeder::class);
        $version = TaskVersion::query()->firstOrFail();

        $this->expectException(InvalidArgumentException::class);

        app(TaskGrader::class)->grade($version, ['text' => '4']);
    }
}
