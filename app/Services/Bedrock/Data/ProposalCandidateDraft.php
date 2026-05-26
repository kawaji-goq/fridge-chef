<?php

namespace App\Services\Bedrock\Data;

/**
 * AI に渡す/AI から返ってくる候補レシピの中間表現。
 * 既存 DB のレシピを指す場合は recipeId、AI 生成の場合は recipeSnapshot を持つ。
 */
final class ProposalCandidateDraft
{
    public function __construct(
        public readonly ?string $recipeId,
        public readonly ?array $recipeSnapshot,
        public readonly float $score,
        public readonly array $missingIngredients,
        public readonly string $reason = '',
    ) {}

    public function withReason(string $reason): self
    {
        return new self($this->recipeId, $this->recipeSnapshot, $this->score, $this->missingIngredients, $reason);
    }

    public function toArray(): array
    {
        return [
            'recipe_id' => $this->recipeId,
            'recipe_snapshot' => $this->recipeSnapshot,
            'score' => $this->score,
            'missing_ingredients' => $this->missingIngredients,
            'reason' => $this->reason,
        ];
    }
}
