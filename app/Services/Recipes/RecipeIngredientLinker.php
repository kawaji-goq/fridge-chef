<?php

namespace App\Services\Recipes;

use App\Models\Ingredient;
use App\Models\IngredientAlias;
use App\Models\IngredientLocalization;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * パース済み材料リストを Recipe に紐付ける（recipe_ingredients を生成）。
 * - 食材マスタに無いものは新規作成（slug 自動）
 * - 単位が判定できないものはスキップ（recipe_ingredients に入らないが materials_text には残る）
 */
class RecipeIngredientLinker
{
    /**
     * @param  array  $parsed  MaterialParser::parseMultiline の結果
     * @return array{linked: int, skipped: int, unknown_units: int}
     */
    public function syncToRecipe(Recipe $recipe, array $parsed): array
    {
        $unitsByLabel = Unit::pluck('id', 'label_ja')->all();
        $unitsByCode = Unit::pluck('id', 'code')->all();

        $linked = 0;
        $skipped = 0;
        $unknownUnits = 0;

        DB::transaction(function () use ($recipe, $parsed, $unitsByLabel, $unitsByCode, &$linked, &$skipped, &$unknownUnits) {
            RecipeIngredient::where('recipe_id', $recipe->id)->delete();

            foreach ($parsed as $row) {
                $name = $row['name'] ?? '';
                $quantity = $row['quantity'];
                $unitLabel = $row['unit_label'];

                if ($name === '' || $quantity === null) {
                    $skipped++;

                    continue;
                }

                $unitId = $unitsByLabel[$unitLabel] ?? $unitsByCode[$unitLabel] ?? null;
                if (! $unitId) {
                    $unknownUnits++;

                    continue;
                }

                $ingredient = $this->findOrCreateIngredient($name, $unitId);

                // 同じ食材+単位の組合せが既にあったらスキップ
                $exists = RecipeIngredient::where('recipe_id', $recipe->id)
                    ->where('ingredient_id', $ingredient->id)
                    ->where('unit_id', $unitId)
                    ->exists();
                if ($exists) {
                    continue;
                }

                RecipeIngredient::create([
                    'recipe_id' => $recipe->id,
                    'ingredient_id' => $ingredient->id,
                    'quantity' => $quantity,
                    'unit_id' => $unitId,
                    'is_optional' => false,
                    'display_text' => $row['raw'] ?? null,
                ]);

                $linked++;
            }
        });

        return ['linked' => $linked, 'skipped' => $skipped, 'unknown_units' => $unknownUnits];
    }

    private function findOrCreateIngredient(string $name, int $defaultBaseUnitId): Ingredient
    {
        // 完全一致 → display_name
        $loc = IngredientLocalization::where('locale', 'ja-JP')
            ->where('display_name', $name)
            ->first();
        if ($loc) {
            return Ingredient::findOrFail($loc->ingredient_id);
        }

        // alias で完全一致
        $alias = IngredientAlias::where('locale', 'ja-JP')
            ->where('alias', $name)
            ->first();
        if ($alias) {
            return Ingredient::findOrFail($alias->ingredient_id);
        }

        // 部分一致（display_name に含まれる）
        $partial = IngredientLocalization::where('locale', 'ja-JP')
            ->where(function ($q) use ($name) {
                $q->where('display_name', 'like', "%{$name}%")
                  ->orWhere(DB::raw("?"), 'like', DB::raw("CONCAT('%', display_name, '%')"));
            })
            ->setBindings([$name])
            ->first();
        if ($partial) {
            return Ingredient::findOrFail($partial->ingredient_id);
        }

        // 新規作成
        $slug = Str::slug($name) ?: 'ing-'.Str::random(8);
        $i = 1;
        $base = $slug;
        while (Ingredient::where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }
        $ingredient = Ingredient::create([
            'slug' => $slug,
            'category' => 'other',
            'base_unit_id' => $defaultBaseUnitId,
        ]);
        IngredientLocalization::create([
            'ingredient_id' => $ingredient->id,
            'locale' => 'ja-JP',
            'display_name' => $name,
        ]);

        return $ingredient;
    }
}
