<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LearningPathDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'nodes' => $this->pathNodes
                ->filter(fn ($pathNode): bool => (bool) $pathNode->learningNode?->active)
                ->map(fn ($pathNode): array => [
                    'id' => $pathNode->learningNode->id,
                    'title' => $pathNode->learningNode->title,
                    'position' => $pathNode->position,
                ])
                ->values()
                ->all(),
            'intro_explanations' => $this->introExplanations(),
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function introExplanations(): array
    {
        $configured = $this->intro_explanations;

        if (is_array($configured) && $this->hasAllIntroLevels($configured)) {
            return $configured;
        }

        $firstNodeTitle = $this->pathNodes
            ->first(fn ($pathNode): bool => (bool) $pathNode->learningNode?->active)
            ?->learningNode
            ?->title
            ?? 'Dieses Thema';

        return [
            'new' => [
                'title' => "{$firstNodeTitle} kurz greifen.",
                'body' => 'Du bekommst zuerst die Grundidee und startest mit einem kleinen Schritt. Es geht nicht darum, alles sofort zu können.',
                'usefulness' => 'Du erkennst, welche Handlung als Nächstes passt.',
            ],
            'rough' => [
                'title' => "{$firstNodeTitle} wieder sortieren.",
                'body' => 'Du kennst wahrscheinlich Teile davon. Nuvio setzt kurz den Rahmen und zeigt dir, wo du direkt einsteigen kannst.',
                'usefulness' => 'Du findest schneller die Stelle, die gerade Übung braucht.',
            ],
            'confident' => [
                'title' => "{$firstNodeTitle} festigen.",
                'body' => 'Du nutzt den Einstieg als kurze Auffrischung. Danach prüfst du, ob die Grundlagen auch ohne langes Nachdenken sitzen.',
                'usefulness' => 'Du machst Wissen abrufbarer für schwierigere Aufgaben.',
            ],
        ];
    }

    /**
     * @param array<mixed> $configured
     */
    private function hasAllIntroLevels(array $configured): bool
    {
        foreach (['new', 'rough', 'confident'] as $level) {
            if (
                ! is_array($configured[$level] ?? null)
                || ! is_string($configured[$level]['title'] ?? null)
                || ! is_string($configured[$level]['body'] ?? null)
                || ! is_string($configured[$level]['usefulness'] ?? null)
            ) {
                return false;
            }
        }

        return true;
    }
}
