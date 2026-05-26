<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\Nutrient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * 既存食材の栄養値を投入。
 * value_per_100_base は base_unit 100 単位あたりの値。
 * - mass (g) ベース: 100g あたり
 * - volume (ml) ベース: 100ml あたり（液体は密度 ≒ 1 と近似）
 * - count (piece) ベース: 100 個あたり（1 個重量を掛けたもの）
 *
 * 出典: 日本食品標準成分表（八訂）増補 2023 を参考にした近似値。
 * MVP のため厳密性より作りやすさ優先、後で正確版で上書き予定。
 */
class IngredientNutrientValuesSeeder extends Seeder
{
    public function run(): void
    {
        $nutrientIds = Nutrient::pluck('id', 'code');
        $ingredientIds = Ingredient::pluck('id', 'slug');

        foreach ($this->data() as $row) {
            if (! isset($ingredientIds[$row['slug']])) {
                continue;
            }
            $ingredientId = $ingredientIds[$row['slug']];

            foreach (['energy_kcal', 'protein_g', 'fat_g', 'carb_g', 'sodium_mg'] as $code) {
                if (! isset($row[$code])) {
                    continue;
                }
                DB::table('ingredient_nutrient_values')->updateOrInsert(
                    [
                        'ingredient_id' => $ingredientId,
                        'nutrient_id' => $nutrientIds[$code],
                        'source' => 'mext_8th_approx_2026',
                    ],
                    [
                        'value_per_100_base' => $row[$code],
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    }

    /**
     * 食材別 栄養値（per 100 base_unit）
     */
    private function data(): array
    {
        return [
            // ===== Protein =====
            // 卵 base=piece, 1個~60g → ×60
            ['slug' => 'egg', 'energy_kcal' => 8520, 'protein_g' => 738, 'fat_g' => 612, 'carb_g' => 18, 'sodium_mg' => 8400],
            // 鶏むね肉 base=g
            ['slug' => 'chicken-breast', 'energy_kcal' => 105, 'protein_g' => 23.3, 'fat_g' => 1.5, 'carb_g' => 0, 'sodium_mg' => 60],
            ['slug' => 'chicken-thigh', 'energy_kcal' => 190, 'protein_g' => 17.3, 'fat_g' => 14, 'carb_g' => 0, 'sodium_mg' => 60],
            ['slug' => 'pork-belly', 'energy_kcal' => 386, 'protein_g' => 14.4, 'fat_g' => 35.4, 'carb_g' => 0.1, 'sodium_mg' => 50],
            ['slug' => 'pork-koma', 'energy_kcal' => 253, 'protein_g' => 19.3, 'fat_g' => 19.2, 'carb_g' => 0.2, 'sodium_mg' => 50],
            ['slug' => 'beef-thinly-sliced', 'energy_kcal' => 259, 'protein_g' => 17.4, 'fat_g' => 20.6, 'carb_g' => 0.3, 'sodium_mg' => 50],
            ['slug' => 'ground-meat', 'energy_kcal' => 245, 'protein_g' => 17.5, 'fat_g' => 19.2, 'carb_g' => 0.3, 'sodium_mg' => 60],
            ['slug' => 'bacon', 'energy_kcal' => 405, 'protein_g' => 12.9, 'fat_g' => 39.1, 'carb_g' => 0.3, 'sodium_mg' => 800],
            // ウインナー base=piece, 1本~20g → ×20
            ['slug' => 'sausage', 'energy_kcal' => 6400, 'protein_g' => 264, 'fat_g' => 564, 'carb_g' => 60, 'sodium_mg' => 14800],
            ['slug' => 'salmon', 'energy_kcal' => 138, 'protein_g' => 22.3, 'fat_g' => 4.5, 'carb_g' => 0.1, 'sodium_mg' => 60],
            ['slug' => 'mackerel', 'energy_kcal' => 247, 'protein_g' => 20.6, 'fat_g' => 16.8, 'carb_g' => 0.3, 'sodium_mg' => 80],
            // ツナ缶 base=piece, 1缶~70g → ×70
            ['slug' => 'canned-tuna', 'energy_kcal' => 19600, 'protein_g' => 1190, 'fat_g' => 1610, 'carb_g' => 0, 'sodium_mg' => 21000],
            ['slug' => 'tofu', 'energy_kcal' => 56, 'protein_g' => 4.9, 'fat_g' => 3, 'carb_g' => 2, 'sodium_mg' => 10],
            // 納豆 base=pack, 1パック~45g → ×45
            ['slug' => 'natto', 'energy_kcal' => 9000, 'protein_g' => 743, 'fat_g' => 450, 'carb_g' => 540, 'sodium_mg' => 90],
            // 油揚げ base=piece, 1枚~30g → ×30
            ['slug' => 'aburaage', 'energy_kcal' => 11580, 'protein_g' => 690, 'fat_g' => 990, 'carb_g' => 12, 'sodium_mg' => 300],

            // ===== Dairy =====
            // 牛乳 base=ml
            ['slug' => 'milk', 'energy_kcal' => 67, 'protein_g' => 3.3, 'fat_g' => 3.8, 'carb_g' => 4.8, 'sodium_mg' => 41],
            ['slug' => 'butter', 'energy_kcal' => 745, 'protein_g' => 0.6, 'fat_g' => 81, 'carb_g' => 0.2, 'sodium_mg' => 750],
            ['slug' => 'cheese', 'energy_kcal' => 339, 'protein_g' => 22.7, 'fat_g' => 26, 'carb_g' => 1.3, 'sodium_mg' => 1100],
            ['slug' => 'yogurt', 'energy_kcal' => 62, 'protein_g' => 3.6, 'fat_g' => 3, 'carb_g' => 4.9, 'sodium_mg' => 48],

            // ===== Vegetables (count-based; 100 base = 100 piece または 100 bag/pack) =====
            // 玉ねぎ ~200g/個 → 37 × 200 = 7400
            ['slug' => 'onion', 'energy_kcal' => 7400, 'protein_g' => 200, 'fat_g' => 20, 'carb_g' => 1760, 'sodium_mg' => 400],
            // にんじん ~150g/個 → 39 × 150 = 5850
            ['slug' => 'carrot', 'energy_kcal' => 5850, 'protein_g' => 90, 'fat_g' => 30, 'carb_g' => 1395, 'sodium_mg' => 3750],
            // じゃがいも ~150g/個 → 77 × 150 = 11550
            ['slug' => 'potato', 'energy_kcal' => 11550, 'protein_g' => 240, 'fat_g' => 15, 'carb_g' => 2550, 'sodium_mg' => 150],
            // キャベツ ~1kg/玉 → 23 × 1000 = 23000
            ['slug' => 'cabbage', 'energy_kcal' => 23000, 'protein_g' => 1300, 'fat_g' => 200, 'carb_g' => 5300, 'sodium_mg' => 5000],
            ['slug' => 'chinese-cabbage', 'energy_kcal' => 14000, 'protein_g' => 800, 'fat_g' => 100, 'carb_g' => 3200, 'sodium_mg' => 6000],
            ['slug' => 'lettuce', 'energy_kcal' => 4800, 'protein_g' => 240, 'fat_g' => 40, 'carb_g' => 1120, 'sodium_mg' => 800],
            // きゅうり ~100g/本 → 14 × 100 = 1400
            ['slug' => 'cucumber', 'energy_kcal' => 1400, 'protein_g' => 100, 'fat_g' => 10, 'carb_g' => 300, 'sodium_mg' => 100],
            // トマト ~150g/個 → 19 × 150 = 2850
            ['slug' => 'tomato', 'energy_kcal' => 2850, 'protein_g' => 105, 'fat_g' => 15, 'carb_g' => 705, 'sodium_mg' => 450],
            // ピーマン ~30g/個 → 22 × 30 = 660
            ['slug' => 'bell-pepper', 'energy_kcal' => 660, 'protein_g' => 27, 'fat_g' => 6, 'carb_g' => 153, 'sodium_mg' => 30],
            // なす ~80g/個 → 22 × 80 = 1760
            ['slug' => 'eggplant', 'energy_kcal' => 1760, 'protein_g' => 88, 'fat_g' => 8, 'carb_g' => 416, 'sodium_mg' => 0],
            // 大根 ~1kg/本 → 18 × 1000 = 18000
            ['slug' => 'daikon', 'energy_kcal' => 18000, 'protein_g' => 500, 'fat_g' => 100, 'carb_g' => 4100, 'sodium_mg' => 1700],
            ['slug' => 'broccoli', 'energy_kcal' => 9900, 'protein_g' => 1290, 'fat_g' => 180, 'carb_g' => 1980, 'sodium_mg' => 600],
            // ほうれん草 ~200g/袋 → 20 × 200 = 4000
            ['slug' => 'spinach', 'energy_kcal' => 4000, 'protein_g' => 440, 'fat_g' => 80, 'carb_g' => 620, 'sodium_mg' => 3200],
            ['slug' => 'komatsuna', 'energy_kcal' => 2800, 'protein_g' => 300, 'fat_g' => 40, 'carb_g' => 480, 'sodium_mg' => 3000],
            // 長ねぎ ~150g/本 → 35 × 150 = 5250
            ['slug' => 'long-onion', 'energy_kcal' => 5250, 'protein_g' => 210, 'fat_g' => 15, 'carb_g' => 1245, 'sodium_mg' => 0],
            // もやし ~200g/袋
            ['slug' => 'bean-sprouts', 'energy_kcal' => 2800, 'protein_g' => 360, 'fat_g' => 20, 'carb_g' => 520, 'sodium_mg' => 600],
            // しめじ ~100g/パック
            ['slug' => 'shimeji', 'energy_kcal' => 1800, 'protein_g' => 270, 'fat_g' => 50, 'carb_g' => 480, 'sodium_mg' => 200],
            ['slug' => 'enoki', 'energy_kcal' => 2200, 'protein_g' => 270, 'fat_g' => 20, 'carb_g' => 750, 'sodium_mg' => 200],
            ['slug' => 'shiitake', 'energy_kcal' => 1900, 'protein_g' => 300, 'fat_g' => 30, 'carb_g' => 700, 'sodium_mg' => 100],
            // にんにく ~5g/かけ → 134 × 5 = 670
            ['slug' => 'garlic', 'energy_kcal' => 670, 'protein_g' => 31.5, 'fat_g' => 4.5, 'carb_g' => 137, 'sodium_mg' => 25],
            // 生姜 ~30g/かけ → 30 × 30 = 900
            ['slug' => 'ginger', 'energy_kcal' => 900, 'protein_g' => 27, 'fat_g' => 9, 'carb_g' => 195, 'sodium_mg' => 18],

            // ===== Grain =====
            // 米 base=kg → per 100 kg = per 100000g → 358 × 1000 = 358000
            ['slug' => 'rice', 'energy_kcal' => 358000, 'protein_g' => 6100, 'fat_g' => 900, 'carb_g' => 77600, 'sodium_mg' => 1000],
            // 食パン base=piece, 1枚~60g → 264 × 60 = 15840
            ['slug' => 'bread', 'energy_kcal' => 15840, 'protein_g' => 540, 'fat_g' => 240, 'carb_g' => 2790, 'sodium_mg' => 30000],
            // パスタ base=g
            ['slug' => 'pasta', 'energy_kcal' => 347, 'protein_g' => 12.9, 'fat_g' => 1.8, 'carb_g' => 73.1, 'sodium_mg' => 1],
            // うどん base=pack, 1玉~200g → 105 × 200 = 21000
            ['slug' => 'udon', 'energy_kcal' => 21000, 'protein_g' => 520, 'fat_g' => 80, 'carb_g' => 4380, 'sodium_mg' => 12000],
            ['slug' => 'flour', 'energy_kcal' => 367, 'protein_g' => 8.3, 'fat_g' => 1.5, 'carb_g' => 75.8, 'sodium_mg' => 0],

            // ===== Seasoning =====
            ['slug' => 'soy-sauce', 'energy_kcal' => 71, 'protein_g' => 7.7, 'fat_g' => 0, 'carb_g' => 7.9, 'sodium_mg' => 5700],
            ['slug' => 'miso', 'energy_kcal' => 192, 'protein_g' => 12.5, 'fat_g' => 6, 'carb_g' => 21.9, 'sodium_mg' => 4900],
            ['slug' => 'salt', 'energy_kcal' => 0, 'protein_g' => 0, 'fat_g' => 0, 'carb_g' => 0, 'sodium_mg' => 39000],
            ['slug' => 'sugar', 'energy_kcal' => 384, 'protein_g' => 0, 'fat_g' => 0, 'carb_g' => 99.2, 'sodium_mg' => 0],
            ['slug' => 'vinegar', 'energy_kcal' => 25, 'protein_g' => 0.1, 'fat_g' => 0, 'carb_g' => 2.4, 'sodium_mg' => 6],
            ['slug' => 'mirin', 'energy_kcal' => 241, 'protein_g' => 0.3, 'fat_g' => 0, 'carb_g' => 43.2, 'sodium_mg' => 3],
            ['slug' => 'cooking-sake', 'energy_kcal' => 89, 'protein_g' => 0.2, 'fat_g' => 0, 'carb_g' => 4.5, 'sodium_mg' => 2],
            ['slug' => 'oil', 'energy_kcal' => 921, 'protein_g' => 0, 'fat_g' => 100, 'carb_g' => 0, 'sodium_mg' => 0],
            ['slug' => 'sesame-oil', 'energy_kcal' => 921, 'protein_g' => 0, 'fat_g' => 100, 'carb_g' => 0, 'sodium_mg' => 0],
            ['slug' => 'mayonnaise', 'energy_kcal' => 700, 'protein_g' => 1.5, 'fat_g' => 75, 'carb_g' => 3.6, 'sodium_mg' => 730],
            ['slug' => 'ketchup', 'energy_kcal' => 121, 'protein_g' => 1.6, 'fat_g' => 0.2, 'carb_g' => 27.6, 'sodium_mg' => 1200],
        ];
    }
}
