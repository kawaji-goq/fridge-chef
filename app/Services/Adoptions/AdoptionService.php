<?php

namespace App\Services\Adoptions;

use App\Models\Adoption;
use App\Models\AdoptionInventoryUse;
use App\Models\Ingredient;
use App\Models\IngredientUnitConversion;
use App\Models\InventoryItem;
use App\Models\ProposalCandidate;
use App\Models\Recipe;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdoptionService
{
    /**
     * 候補を採用し、在庫から必要量を減算する。
     *
     * @return array{adoption: Adoption, consumed: array<int,array<string,mixed>>, shortages: array<int,array<string,mixed>>}
     */
    public function adopt(User $user, ProposalCandidate $candidate, ?float $servings = null): array
    {
        $recipe = $candidate->recipe;
        if (! $recipe) {
            throw new \InvalidArgumentException('AI 生成レシピの採用は未対応です（Phase2）。既存レシピのみ採用可能。');
        }

        $servings = $servings ?: (float) $recipe->servings_default;
        $scale = $servings / max((float) $recipe->servings_default, 1.0);

        return DB::transaction(function () use ($user, $candidate, $recipe, $servings, $scale) {
            $adoption = Adoption::create([
                'user_id' => $user->id,
                'proposal_id' => $candidate->proposal_id,
                'recipe_id' => $recipe->id,
                'adopted_at' => now(),
                'servings' => $servings,
            ]);

            $consumed = [];
            $shortages = [];
            $unitLabels = Unit::pluck('label_ja', 'id');

            foreach ($recipe->ingredients as $ri) {
                $requiredInRecipeUnit = (float) $ri->quantity * $scale;
                $requiredBase = $this->convertToBase($ri->ingredient, $ri->unit_id, $requiredInRecipeUnit);

                $result = $this->consumeFromInventory($user, $ri->ingredient, $ri->unit_id, $requiredBase, $adoption);

                $baseUnitLabel = $unitLabels[$ri->ingredient->base_unit_id] ?? '';

                if ($result['consumed_base'] > 0) {
                    $consumed[] = [
                        'ingredient_name' => $ri->ingredient->displayName(),
                        'used_base_quantity' => $result['consumed_base'],
                        'base_unit_label' => $baseUnitLabel,
                    ];
                }

                $shortage = $requiredBase - $result['consumed_base'];
                if ($shortage > 0.0001 && ! $ri->is_optional) {
                    $shortages[] = [
                        'ingredient_name' => $ri->ingredient->displayName(),
                        'shortage_base' => $shortage,
                        'base_unit_label' => $baseUnitLabel,
                    ];
                }
            }

            return ['adoption' => $adoption, 'consumed' => $consumed, 'shortages' => $shortages];
        });
    }

    /**
     * 単位を base_unit に変換。
     */
    private function convertToBase(Ingredient $ingredient, int $fromUnitId, float $qty): float
    {
        if ($fromUnitId === $ingredient->base_unit_id) {
            return $qty;
        }

        $conv = IngredientUnitConversion::where('ingredient_id', $ingredient->id)
            ->where('unit_id', $fromUnitId)
            ->first();

        return $conv ? $qty * (float) $conv->factor_to_base : $qty;
    }

    /**
     * 在庫から FIFO で消費し、AdoptionInventoryUse を記録。
     *
     * @return array{consumed_base: float}
     */
    private function consumeFromInventory(User $user, Ingredient $ingredient, int $recipeUnitId, float $requiredBase, Adoption $adoption): array
    {
        $items = InventoryItem::where('user_id', $user->id)
            ->where('ingredient_id', $ingredient->id)
            ->orderByRaw('expires_at IS NULL, expires_at ASC') // 期限近いものを先に
            ->orderBy('created_at')
            ->get();

        $remaining = $requiredBase;
        $totalConsumed = 0.0;

        foreach ($items as $item) {
            if ($remaining <= 0.0001) {
                break;
            }
            $available = (float) $item->base_quantity;
            $consume = min($available, $remaining);

            AdoptionInventoryUse::create([
                'adoption_id' => $adoption->id,
                'inventory_item_id' => $item->id,
                'used_quantity' => $consume, // 表示用にはひとまず base 値そのまま
                'used_unit_id' => $ingredient->base_unit_id,
                'used_base_quantity' => $consume,
            ]);

            $item->base_quantity = $available - $consume;
            $item->quantity = $this->recalcDisplayQuantity($item);

            if ($item->base_quantity <= 0.0001) {
                $item->delete();
            } else {
                $item->save();
            }

            $remaining -= $consume;
            $totalConsumed += $consume;
        }

        return ['consumed_base' => $totalConsumed];
    }

    /**
     * base_quantity の変動に合わせて表示用 quantity を再計算。
     */
    private function recalcDisplayQuantity(InventoryItem $item): float
    {
        if ($item->unit_id === $item->ingredient->base_unit_id) {
            return (float) $item->base_quantity;
        }
        $conv = IngredientUnitConversion::where('ingredient_id', $item->ingredient_id)
            ->where('unit_id', $item->unit_id)
            ->first();

        return $conv && (float) $conv->factor_to_base > 0
            ? (float) $item->base_quantity / (float) $conv->factor_to_base
            : (float) $item->base_quantity;
    }
}
