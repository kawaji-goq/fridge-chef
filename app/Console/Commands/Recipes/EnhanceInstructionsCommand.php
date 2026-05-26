<?php

namespace App\Console\Commands\Recipes;

use App\Models\Recipe;
use App\Services\Bedrock\Contracts\BedrockClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('recipes:enhance-instructions {--force : 既に詳細手順がある場合も再生成}')]
#[Description('Bedrock を使って全レシピの初心者向け詳細手順を生成')]
class EnhanceInstructionsCommand extends Command
{
    public function handle(BedrockClient $bedrock): int
    {
        $this->info('Driver: '.$bedrock->driver());

        $force = (bool) $this->option('force');
        $query = Recipe::with(['ingredients.ingredient.localizations', 'ingredients.unit']);
        if (! $force) {
            $query->whereNull('instructions_beginner');
        }
        $recipes = $query->get();

        if ($recipes->isEmpty()) {
            $this->info('対象レシピがありません（--force で全件再生成）。');

            return self::SUCCESS;
        }

        $this->info("対象 {$recipes->count()} 件 — 生成開始");
        $totalIn = 0;
        $totalOut = 0;

        $bar = $this->output->createProgressBar($recipes->count());
        $bar->start();

        foreach ($recipes as $recipe) {
            $payload = [
                'title' => $recipe->title,
                'servings_default' => $recipe->servings_default,
                'total_cook_minutes' => $recipe->total_cook_minutes,
                'instructions' => $recipe->instructions,
                'ingredients' => $recipe->ingredients->map(fn ($ri) => [
                    'name' => $ri->ingredient->displayName(),
                    'quantity' => rtrim(rtrim((string) $ri->quantity, '0'), '.'),
                    'unit' => $ri->unit->label_ja,
                ])->all(),
            ];

            $text = $bedrock->enhanceInstructions($payload);
            if ($text !== '') {
                $recipe->update(['instructions_beginner' => $text]);
            }
            $usage = $bedrock->lastUsage();
            $totalIn += $usage['input_tokens'] ?? 0;
            $totalOut += $usage['output_tokens'] ?? 0;

            $bar->advance();
        }
        $bar->finish();
        $this->newLine();

        $cost = ($totalIn * 0.8 + $totalOut * 4) / 1000000 * 1.1;
        $this->info(sprintf(
            '完了: in=%d out=%d tokens / 概算 $%.4f (約 %.2f 円)',
            $totalIn, $totalOut, $cost, $cost * 150
        ));

        return self::SUCCESS;
    }
}
