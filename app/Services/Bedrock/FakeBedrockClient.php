<?php

namespace App\Services\Bedrock;

use App\Services\Bedrock\Contracts\BedrockClient;
use App\Services\Bedrock\Data\ProposalCandidateDraft;

/**
 * 開発時用の Fake 実装。Bedrock を呼ばずに、上位 3 件を取り、テンプレ理由を付ける。
 */
class FakeBedrockClient implements BedrockClient
{
    private array $usage = ['input_tokens' => 0, 'output_tokens' => 0];

    public function selectFinalCandidates(array $context, array $candidates): array
    {
        $top = array_slice($candidates, 0, 5);

        return array_map(function (ProposalCandidateDraft $c, int $rank) use ($context) {
            $reason = $this->buildReason($c, $rank, $context);

            return $c->withReason($reason);
        }, $top, array_keys($top));
    }

    public function enhanceInstructions(array $recipe): string
    {
        return "[Fake] 初心者向け詳細手順 のモック\n\n".($recipe['instructions'] ?? '');
    }

    public function parseIngredients(string $raw): string
    {
        // Fake は簡易整形のみ（装飾記号除去・カッコ除去・cc→ml）
        $lines = preg_split('/\r?\n/', $raw) ?: [];
        $out = [];
        foreach ($lines as $line) {
            $line = preg_replace('/^[★●☆◯◎▲▼※・■□◆◇▪▫\s]+/u', '', $line) ?? $line;
            $line = preg_replace('/[（(].*?[)）]/u', ' ', $line) ?? $line;
            $line = preg_replace('/(\d+\s*)cc\b/i', '$1ml', $line) ?? $line;
            $line = trim(preg_replace('/\s+/u', ' ', $line) ?? $line);
            if ($line !== '') {
                $out[] = $line;
            }
        }

        return implode("\n", $out);
    }

    public function driver(): string
    {
        return 'fake';
    }

    public function lastUsage(): array
    {
        return $this->usage;
    }

    private function buildReason(ProposalCandidateDraft $c, int $rank, array $context): string
    {
        $reasons = [
            'お手元の在庫だけでサッと作れる定番メニューです。',
            '冷蔵庫の食材をバランス良く使い切れます。',
            '短い調理時間で家族みんな満足できる一品です。',
            '在庫を活かせるシンプルなメニューです。',
            '気軽に作れて満足度の高い一品です。',
        ];
        $base = $reasons[$rank % count($reasons)];

        if (! empty($c->missingIngredients)) {
            $missingNames = array_slice(array_map(fn ($m) => $m['name'] ?? 'なにか', $c->missingIngredients), 0, 2);
            $base .= ' （あと '.implode('・', $missingNames).' があるとさらに本格的に）';
        }

        return '[Fake] '.$base;
    }
}
