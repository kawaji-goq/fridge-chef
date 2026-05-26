<?php

namespace App\Services\Bedrock;

use App\Services\Bedrock\Contracts\BedrockClient;
use App\Services\Bedrock\Data\ProposalCandidateDraft;
use Aws\BedrockRuntime\BedrockRuntimeClient;
use Illuminate\Support\Facades\Log;

class RealBedrockClient implements BedrockClient
{
    private array $usage = ['input_tokens' => 0, 'output_tokens' => 0];

    public function __construct(
        private readonly BedrockRuntimeClient $client,
        private readonly string $modelId,
    ) {}

    public function selectFinalCandidates(array $context, array $candidates): array
    {
        if (empty($candidates)) {
            return [];
        }

        $payload = $this->buildPayload($context, $candidates);

        $response = $this->client->invokeModel([
            'modelId' => $this->modelId,
            'contentType' => 'application/json',
            'accept' => 'application/json',
            'body' => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);

        $body = json_decode($response['body']->getContents(), true);

        $this->usage = [
            'input_tokens' => $body['usage']['input_tokens'] ?? 0,
            'output_tokens' => $body['usage']['output_tokens'] ?? 0,
        ];

        $aiText = $body['content'][0]['text'] ?? '';
        $parsed = $this->parseAiResponse($aiText);

        return $this->mergeAiSelectionsWithCandidates($parsed, $candidates);
    }

    public function enhanceInstructions(array $recipe): string
    {
        $ingredientsText = collect($recipe['ingredients'] ?? [])
            ->map(fn ($i) => '- '.$i['name'].' '.$i['quantity'].$i['unit'])
            ->implode("\n");

        $userPayload = "タイトル: {$recipe['title']}\n"
            ."人数: {$recipe['servings_default']} 人前\n"
            ."所要時間目安: {$recipe['total_cook_minutes']} 分\n\n"
            ."材料:\n{$ingredientsText}\n\n"
            ."簡潔な手順（参考）:\n{$recipe['instructions']}";

        $systemPrompt = 'あなたは料理初心者向けのレシピライターです。'
            ."与えられた料理の作り方を、料理が苦手な初心者でも失敗せず作れるように詳しく書き直してください。\n\n"
            ."ガイドライン:\n"
            ."- 番号付き手順（1. / 2. / ...）で 5〜8 ステップ\n"
            ."- 各ステップに目安時間や火加減（強火/中火/弱火）を含める\n"
            ."- 切り方は「一口大」ではなく「2cm 角程度」のように具体的に\n"
            ."- 焦げや生焼けなど失敗しがちな箇所には注意点を 1 行で添える\n"
            ."- 完成の見極め方を最後のステップに書く\n"
            ."- 全体で 250〜450 文字、平易な日本語で\n\n"
            .'手順本文のみ出力してください（前置きや「以下の通り：」などは不要）。';

        try {
            $response = $this->client->invokeModel([
                'modelId' => $this->modelId,
                'contentType' => 'application/json',
                'accept' => 'application/json',
                'body' => json_encode([
                    'anthropic_version' => 'bedrock-2023-05-31',
                    'max_tokens' => 600,
                    'system' => $systemPrompt,
                    'messages' => [['role' => 'user', 'content' => $userPayload]],
                ], JSON_UNESCAPED_UNICODE),
            ]);

            $body = json_decode($response['body']->getContents(), true);
            $this->usage = [
                'input_tokens' => $body['usage']['input_tokens'] ?? 0,
                'output_tokens' => $body['usage']['output_tokens'] ?? 0,
            ];

            return trim($body['content'][0]['text'] ?? '');
        } catch (\Throwable $e) {
            Log::warning('Bedrock enhanceInstructions failed', ['error' => $e->getMessage()]);

            return '';
        }
    }

    public function parseIngredients(string $raw): string
    {
        if (trim($raw) === '') {
            return '';
        }

        $systemPrompt = '日本のレシピの材料リストを正規化してください。'
            ."\n\n"
            ."ルール:\n"
            ."- 各材料を 1 行で記述\n"
            ."- 形式: 食材名 数量 単位（半角スペース区切り）\n"
            ."- 装飾記号（★●☆◯◎▲▼※■□◆◇）は除去\n"
            ."- 括弧内の補足（(中)・(薄切り) 等）は除去\n"
            ."- 単位を統一: 重量=g/kg、容量=ml/l、個数=個/パック/袋、計量=大さじ/小さじ/カップ/合\n"
            ."- \"cc\" → \"ml\"、\"本\" → \"個\"（食材により）、\"枚\" → \"個\"\n"
            ."- 分数は小数に: \"1/2\" → \"0.5\"\n"
            ."- 数量不明（少々/適量/お好みで）の行は省略\n"
            ."- 食材名は標準的な表記（豚バラ肉、玉ねぎ、にんじん等）\n\n"
            .'正規化結果のみを出力（前置きや説明、Markdown装飾は不要）。';

        try {
            $response = $this->client->invokeModel([
                'modelId' => $this->modelId,
                'contentType' => 'application/json',
                'accept' => 'application/json',
                'body' => json_encode([
                    'anthropic_version' => 'bedrock-2023-05-31',
                    'max_tokens' => 800,
                    'system' => $systemPrompt,
                    'messages' => [['role' => 'user', 'content' => $raw]],
                ], JSON_UNESCAPED_UNICODE),
            ]);

            $body = json_decode($response['body']->getContents(), true);
            $this->usage = [
                'input_tokens' => $body['usage']['input_tokens'] ?? 0,
                'output_tokens' => $body['usage']['output_tokens'] ?? 0,
            ];

            return trim($body['content'][0]['text'] ?? '');
        } catch (\Throwable $e) {
            Log::warning('Bedrock parseIngredients failed', ['error' => $e->getMessage()]);

            return '';
        }
    }

    public function driver(): string
    {
        return 'real';
    }

    public function lastUsage(): array
    {
        return $this->usage;
    }

    private function buildPayload(array $context, array $candidates): array
    {
        $systemPrompt = '日本の家庭料理のアシスタントとして、ユーザーの冷蔵庫の在庫と好みから、'
            ."今日の献立 5 つを提示してください。\n"
            ."必ず JSON 形式で {\"selections\":[{\"rank\":1,\"candidate_index\":N,\"reason\":\"...\"},...]} を返してください。\n"
            ."候補は提供される candidates の index (0-based) から 5 つ選び、それぞれ 30〜80 文字の自然な日本語の reason を付けてください。\n"
            .'他のテキストは含めないでください。';

        $userPayload = [
            'context' => $context,
            'candidates' => array_map(function (ProposalCandidateDraft $c, int $i) {
                return [
                    'index' => $i,
                    'title' => $c->recipeSnapshot['title'] ?? '(unknown)',
                    'score' => $c->score,
                    'missing_ingredients' => $c->missingIngredients,
                ];
            }, $candidates, array_keys($candidates)),
        ];

        return [
            'anthropic_version' => 'bedrock-2023-05-31',
            'max_tokens' => 800,
            'system' => $systemPrompt,
            'messages' => [[
                'role' => 'user',
                'content' => json_encode($userPayload, JSON_UNESCAPED_UNICODE),
            ]],
        ];
    }

    private function parseAiResponse(string $text): array
    {
        // JSON 部分を抽出（説明文が前後に混ざる場合に備える）
        if (preg_match('/\{.*\}/s', $text, $m)) {
            $text = $m[0];
        }
        $decoded = json_decode($text, true);
        if (! is_array($decoded) || ! isset($decoded['selections'])) {
            Log::warning('Bedrock response unparseable', ['text' => $text]);

            return [];
        }

        return $decoded['selections'];
    }

    /**
     * @param  array  $selections  AI からの選択結果（candidate_index と reason のリスト）
     * @param  ProposalCandidateDraft[]  $candidates
     * @return ProposalCandidateDraft[]
     */
    private function mergeAiSelectionsWithCandidates(array $selections, array $candidates): array
    {
        $result = [];
        foreach ($selections as $sel) {
            $idx = $sel['candidate_index'] ?? null;
            if ($idx === null || ! isset($candidates[$idx])) {
                continue;
            }
            $result[] = $candidates[$idx]->withReason((string) ($sel['reason'] ?? ''));
            if (count($result) >= 5) {
                break;
            }
        }
        // AI が失敗した場合のフォールバック（上位 5 件をそのまま）
        if (empty($result)) {
            return array_slice($candidates, 0, 5);
        }

        return $result;
    }
}
