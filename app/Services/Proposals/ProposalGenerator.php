<?php

namespace App\Services\Proposals;

use App\Models\Adoption;
use App\Models\InventoryItem;
use App\Models\Proposal;
use App\Models\ProposalCandidate;
use App\Models\Recipe;
use App\Models\User;
use App\Services\Bedrock\Contracts\BedrockClient;
use App\Services\Bedrock\Data\ProposalCandidateDraft;
use Illuminate\Support\Facades\DB;

class ProposalGenerator
{
    private const CANDIDATE_POOL_SIZE = 15;
    private const FINAL_CANDIDATE_COUNT = 5;
    private const HISTORY_DAYS = 7;

    public function __construct(
        private readonly BedrockClient $bedrock,
    ) {}

    /**
     * @param  string[]  $mustUseIngredientIds  ユーザーが「絶対使いたい」と指定した ingredient_id
     */
    public function generate(User $user, array $mustUseIngredientIds = []): Proposal
    {
        $context = $this->buildContext($user);
        $context['must_use_ingredient_ids'] = $mustUseIngredientIds;

        [$candidates, $fellBack] = $this->buildCandidatePool($user, $context);
        $context['must_use_fell_back'] = $fellBack;

        $final = $this->bedrock->selectFinalCandidates($context, $candidates);

        return $this->persist($user, $context, $final);
    }

    private function buildContext(User $user): array
    {
        $profile = $user->profile;
        $inventory = InventoryItem::with(['ingredient.localizations', 'unit'])
            ->where('user_id', $user->id)
            ->get()
            ->map(fn ($i) => [
                'ingredient_id' => $i->ingredient_id,
                'ingredient_name' => $i->ingredient->displayName(),
                'base_quantity' => (float) $i->base_quantity,
                'storage_location' => $i->storage_location,
                'expires_at' => $i->expires_at?->toDateString(),
            ])->all();

        $allergenIds = $user->allergies()->pluck('allergen_id')->all();
        $dislikedIngredientIds = $user->dislikedIngredients()->pluck('ingredient_id')->all();
        $preferenceTags = $user->preferenceTags->mapWithKeys(fn ($t) => [$t->tag => (int) $t->weight])->all();

        $recentRecipeIds = Adoption::where('user_id', $user->id)
            ->where('adopted_at', '>=', now()->subDays(self::HISTORY_DAYS))
            ->pluck('recipe_id')
            ->all();

        return [
            'household_adults' => $profile?->household_adults ?? 1,
            'household_children' => $profile?->household_children ?? 0,
            'inventory' => $inventory,
            'allergen_ids' => $allergenIds,
            'disliked_ingredient_ids' => $dislikedIngredientIds,
            'preference_tags' => $preferenceTags,
            'recent_recipe_ids' => $recentRecipeIds,
        ];
    }

    /**
     * 候補プール構築：ハード制約フィルタ + スコアリング + 上位 N 件
     *
     * @return array{0: ProposalCandidateDraft[], 1: bool}  [候補配列, must_use フォールバック発生フラグ]
     */
    private function buildCandidatePool(User $user, array $context): array
    {
        $inventoryIngredientIds = collect($context['inventory'])->pluck('ingredient_id')->all();

        $recipes = Recipe::with(['ingredients.ingredient.allergens', 'ingredients.ingredient.localizations', 'tags'])
            ->get()
            // ハードフィルタ：アレルゲンを含むレシピを除外
            ->reject(function ($recipe) use ($context) {
                foreach ($recipe->ingredients as $ri) {
                    $ingAllergenIds = $ri->ingredient->allergens->pluck('id')->all();
                    if (array_intersect($ingAllergenIds, $context['allergen_ids'])) {
                        return true;
                    }
                }

                return false;
            })
            // ハードフィルタ：嫌い食材を含むレシピを除外
            ->reject(function ($recipe) use ($context) {
                $ingIds = $recipe->ingredients->pluck('ingredient_id')->all();

                return array_intersect($ingIds, $context['disliked_ingredient_ids']) !== [];
            });

        $mustUseIds = $context['must_use_ingredient_ids'] ?? [];
        $fellBack = false;
        if (! empty($mustUseIds)) {
            $filtered = $recipes->filter(function ($recipe) use ($mustUseIds) {
                $ingIds = $recipe->ingredients->pluck('ingredient_id')->all();

                return ! empty(array_intersect($ingIds, $mustUseIds));
            });
            if ($filtered->isNotEmpty()) {
                $recipes = $filtered;
            } else {
                $fellBack = true; // 該当レシピ無し → フィルタ無効化（ソフトフォールバック）
            }
        }

        $scored = $recipes->map(function ($recipe) use ($context, $inventoryIngredientIds) {
            return $this->scoreRecipe($recipe, $context, $inventoryIngredientIds);
        })->sortByDesc('score')->take(self::CANDIDATE_POOL_SIZE);

        $candidates = $scored->map(function ($s) {
            return new ProposalCandidateDraft(
                recipeId: $s['recipe']->id,
                recipeSnapshot: [
                    'title' => $s['recipe']->title,
                    'used_from_inventory' => $s['used_from_inventory'],
                ],
                score: $s['score'],
                missingIngredients: $s['missing'],
            );
        })->values()->all();

        return [$candidates, $fellBack];
    }

    private function scoreRecipe(Recipe $recipe, array $context, array $inventoryIngredientIds): array
    {
        $required = $recipe->ingredients;
        $totalRequired = max($required->count(), 1);
        $matchedCount = 0;
        $missing = [];
        $usedFromInventory = [];

        foreach ($required as $ri) {
            if (in_array($ri->ingredient_id, $inventoryIngredientIds, true)) {
                $matchedCount++;
                $usedFromInventory[] = [
                    'ingredient_id' => $ri->ingredient_id,
                    'name' => $ri->ingredient->displayName(),
                ];
            } elseif (! $ri->is_optional) {
                $missing[] = [
                    'ingredient_id' => $ri->ingredient_id,
                    'name' => $ri->ingredient->displayName(),
                    'quantity' => (float) $ri->quantity,
                    'unit_id' => $ri->unit_id,
                ];
            }
        }

        $matchRate = $matchedCount / $totalRequired;
        $score = $matchRate * 100;

        // 在庫消費度ボーナス：在庫アイテムの何割を使うか（最大 +30）
        $inventoryCount = max(count($inventoryIngredientIds), 1);
        $utilization = $matchedCount / $inventoryCount;
        $score += $utilization * 30;

        // 「絶対使いたい」食材を含むほど大きく加点
        $mustUseIds = $context['must_use_ingredient_ids'] ?? [];
        $recipeIngredientIds = $recipe->ingredients->pluck('ingredient_id')->all();
        $mustUseHits = count(array_intersect($recipeIngredientIds, $mustUseIds));
        $score += $mustUseHits * 25;

        // 履歴と被るレシピは減点
        if (in_array($recipe->id, $context['recent_recipe_ids'], true)) {
            $score -= 30;
        }

        // 好みタグで加点
        $tagWeights = $context['preference_tags'];
        foreach ($recipe->tags as $tag) {
            $score += ($tagWeights[$tag->tag] ?? 0) * 5;
        }

        return [
            'recipe' => $recipe,
            'score' => $score,
            'missing' => $missing,
            'used_from_inventory' => $usedFromInventory,
        ];
    }

    /**
     * 提案を DB に保存。
     *
     * @param  ProposalCandidateDraft[]  $candidates
     */
    private function persist(User $user, array $context, array $candidates): Proposal
    {
        return DB::transaction(function () use ($user, $context, $candidates) {
            $proposal = Proposal::create([
                'user_id' => $user->id,
                'requested_at' => now(),
                'context_snapshot' => [
                    'inventory_count' => count($context['inventory']),
                    'allergen_ids' => $context['allergen_ids'],
                    'recent_recipe_ids' => $context['recent_recipe_ids'],
                    'must_use_ingredient_ids' => $context['must_use_ingredient_ids'] ?? [],
                    'must_use_fell_back' => $context['must_use_fell_back'] ?? false,
                ],
                'model_meta' => [
                    'driver' => $this->bedrock->driver(),
                    'usage' => $this->bedrock->lastUsage(),
                ],
            ]);

            foreach ($candidates as $i => $c) {
                ProposalCandidate::create([
                    'proposal_id' => $proposal->id,
                    'recipe_id' => $c->recipeId,
                    'recipe_snapshot' => $c->recipeSnapshot,
                    'rank' => $i + 1,
                    'score' => $c->score,
                    'reason_text' => $c->reason,
                    'missing_ingredients' => $c->missingIngredients,
                ]);
            }

            return $proposal->fresh();
        });
    }
}
