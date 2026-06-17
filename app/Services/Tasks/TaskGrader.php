<?php

namespace App\Services\Tasks;

use App\Models\TaskVersion;
use InvalidArgumentException;

class TaskGrader
{
    /**
     * @param  array<string, mixed>  $answer
     * @return array{result: string, feedback_key: string, feedback_text: string}
     */
    public function grade(TaskVersion $taskVersion, array $answer): array
    {
        $schema = $taskVersion->answer_schema;

        if (! array_key_exists('value', $answer) || ! is_numeric($answer['value'])) {
            throw new InvalidArgumentException('Numeric tasks require a numeric answer value.');
        }

        $correctValue = (float) $schema['correct_value'];
        $tolerance = (float) ($schema['tolerance'] ?? 0);
        $submittedValue = (float) $answer['value'];
        $isCorrect = abs($submittedValue - $correctValue) <= $tolerance;

        return [
            'result' => $isCorrect ? 'correct' : 'incorrect',
            'feedback_key' => $isCorrect ? 'numeric_correct' : 'numeric_incorrect_review',
            'feedback_text' => $this->feedbackText($taskVersion, $isCorrect),
        ];
    }

    private function feedbackText(TaskVersion $taskVersion, bool $isCorrect): string
    {
        $explanation = trim($taskVersion->explanation);

        if ($isCorrect) {
            return $explanation === ''
                ? 'Richtig. Der Schritt sitzt für jetzt.'
                : "Richtig. {$explanation}";
        }

        return $explanation === ''
            ? 'Noch nicht ganz. Das ist gutes Review-Material. Nuvio bringt das später wieder.'
            : "Noch nicht ganz. {$explanation} Das ist gutes Review-Material. Nuvio bringt das später wieder.";
    }
}
