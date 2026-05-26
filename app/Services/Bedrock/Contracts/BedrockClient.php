<?php

namespace App\Services\Bedrock\Contracts;

use App\Services\Bedrock\Data\ProposalCandidateDraft;

interface BedrockClient
{
    /**
     * システム側でスコアリング済みの候補プールから、最終 3 件を選び理由を生成する。
     *
     * @param  array  $context  ユーザー文脈（人数・好み・履歴など）
     * @param  ProposalCandidateDraft[]  $candidates  スコアリング済み候補（上位 N 件）
     * @return ProposalCandidateDraft[]  最終 3 件（reason 付き）
     */
    public function selectFinalCandidates(array $context, array $candidates): array;

    /**
     * 簡潔な手順を初心者向けに詳しく書き直す。
     *
     * @param  array  $recipe  ['title', 'servings_default', 'total_cook_minutes', 'ingredients', 'instructions']
     * @return string  詳しい手順。失敗時は空文字
     */
    public function enhanceInstructions(array $recipe): string;

    /**
     * クックパッド等のフリーフォーマット材料テキストを、ルールパーサーが処理しやすい
     * 正規化された材料リスト（"食材名 数量 単位" を 1 行ずつ）に整形する。
     * 例: "★鶏もも肉(皮なし)  1枚" → "鶏もも肉 1 枚"
     * 失敗時は空文字を返す。
     */
    public function parseIngredients(string $raw): string;

    /**
     * 実装の種別（fake/real）。デバッグ用。
     */
    public function driver(): string;

    /**
     * 直近のトークン使用量。デバッグ用。
     */
    public function lastUsage(): array;
}
