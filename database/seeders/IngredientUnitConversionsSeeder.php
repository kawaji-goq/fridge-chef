<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * 食材ごとの「別単位 → base_unit」変換係数。
 * 例：米の base_unit=kg、1合=150g なので factor_to_base=0.15
 */
class IngredientUnitConversionsSeeder extends Seeder
{
    public function run(): void
    {
        $units = Unit::pluck('id', 'code');
        $ingredients = Ingredient::pluck('id', 'slug');

        $rows = [
            // [ingredient_slug, unit_code, factor_to_base]
            ['rice', 'go', 0.15],   // 米: 1合 = 0.15 kg
            ['rice', 'g', 0.001],   // 米: 1g = 0.001 kg
        ];

        foreach ($rows as [$slug, $code, $factor]) {
            if (! isset($ingredients[$slug], $units[$code])) {
                continue;
            }
            DB::table('ingredient_unit_conversions')->updateOrInsert(
                [
                    'ingredient_id' => $ingredients[$slug],
                    'unit_id' => $units[$code],
                ],
                [
                    'factor_to_base' => $factor,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
