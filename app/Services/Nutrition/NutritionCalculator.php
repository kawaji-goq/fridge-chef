<?php

namespace App\Services\Nutrition;

use App\Models\IngredientNutrientValue;
use App\Models\IngredientUnitConversion;
use App\Models\Nutrient;
use App\Models\Recipe;
use App\Models\RecipeNutrientValue;
use Illuminate\Support\Facades\DB;

class NutritionCalculator
{
    /**
     * レシピの 1 人前あたりの栄養値を計算して保存。
     *
     * 式: per_serving[N] = Σ (ri.quantity × factor_to_base[ri] × value_per_100_base[ing][N] / 100)
     *                       ÷ recipe.servings_default
     */
    public function calculateAndStore(Recipe $recipe): array
    {
        $recipe->loadMissing(['ingredients.ingredient', 'ingredients.unit']);

        $nutrientIds = Nutrient::pluck('id', 'code')->all();
        $totals = []; // nutrient_id => float

        foreach ($recipe->ingredients as $ri) {
            $baseQty = $this->convertToBase($ri->ingredient, $ri->unit_id, (float) $ri->quantity);

            $values = IngredientNutrientValue::where('ingredient_id', $ri->ingredient_id)
                ->where('source', 'mext_8th_approx_2026')
                ->get();

            foreach ($values as $val) {
                $contribution = $baseQty * (float) $val->value_per_100_base / 100;
                $totals[$val->nutrient_id] = ($totals[$val->nutrient_id] ?? 0) + $contribution;
            }
        }

        $servings = max((int) $recipe->servings_default, 1);
        $perServing = array_map(fn ($v) => $v / $servings, $totals);

        DB::transaction(function () use ($recipe, $perServing) {
            RecipeNutrientValue::where('recipe_id', $recipe->id)->delete();
            foreach ($perServing as $nutrientId => $value) {
                RecipeNutrientValue::create([
                    'recipe_id' => $recipe->id,
                    'nutrient_id' => $nutrientId,
                    'value_per_serving' => $value,
                    'calculated_at' => now(),
                ]);
            }
        });

        return $perServing;
    }

    public function calculateAll(): int
    {
        $count = 0;
        Recipe::with(['ingredients.ingredient', 'ingredients.unit'])->chunk(50, function ($recipes) use (&$count) {
            foreach ($recipes as $recipe) {
                $this->calculateAndStore($recipe);
                $count++;
            }
        });

        return $count;
    }

    private function convertToBase($ingredient, int $fromUnitId, float $qty): float
    {
        if ($fromUnitId === $ingredient->base_unit_id) {
            return $qty;
        }
        $conv = IngredientUnitConversion::where('ingredient_id', $ingredient->id)
            ->where('unit_id', $fromUnitId)
            ->first();

        return $conv ? $qty * (float) $conv->factor_to_base : $qty;
    }
}
