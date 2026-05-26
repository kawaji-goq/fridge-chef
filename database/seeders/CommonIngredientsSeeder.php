<?php

namespace Database\Seeders;

use App\Models\Allergen;
use App\Models\Ingredient;
use App\Models\IngredientAlias;
use App\Models\IngredientLocalization;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CommonIngredientsSeeder extends Seeder
{
    /**
     * よく使う日本の家庭料理食材を投入。
     * 各項目: [slug, category, base_unit_code, display_name, aliases[], allergen_codes[]]
     */
    public function run(): void
    {
        $units = Unit::pluck('id', 'code');
        $allergens = Allergen::pluck('id', 'code');

        foreach ($this->data() as $row) {
            DB::transaction(function () use ($row, $units, $allergens) {
                /** @var Ingredient $ingredient */
                $ingredient = Ingredient::firstOrCreate(
                    ['slug' => $row['slug']],
                    [
                        'category' => $row['category'],
                        'base_unit_id' => $units[$row['base_unit_code']],
                    ]
                );

                IngredientLocalization::updateOrCreate(
                    ['ingredient_id' => $ingredient->id, 'locale' => 'ja-JP'],
                    ['display_name' => $row['display_name']]
                );

                foreach ($row['aliases'] ?? [] as $alias) {
                    IngredientAlias::firstOrCreate([
                        'ingredient_id' => $ingredient->id,
                        'locale' => 'ja-JP',
                        'alias' => $alias,
                    ]);
                }

                foreach ($row['allergen_codes'] ?? [] as $code) {
                    if (! isset($allergens[$code])) {
                        continue;
                    }
                    DB::table('ingredient_allergens')->updateOrInsert(
                        ['ingredient_id' => $ingredient->id, 'allergen_id' => $allergens[$code]],
                        ['created_at' => now(), 'updated_at' => now()]
                    );
                }
            });
        }
    }

    private function data(): array
    {
        return [
            // === Protein ===
            ['slug' => 'egg', 'category' => 'protein', 'base_unit_code' => 'piece',
                'display_name' => '卵', 'aliases' => ['たまご', '玉子', '鶏卵'], 'allergen_codes' => ['egg']],
            ['slug' => 'chicken-breast', 'category' => 'protein', 'base_unit_code' => 'g',
                'display_name' => '鶏むね肉', 'aliases' => ['とりむね', '鶏胸肉']],
            ['slug' => 'chicken-thigh', 'category' => 'protein', 'base_unit_code' => 'g',
                'display_name' => '鶏もも肉', 'aliases' => ['とりもも', '鶏腿肉']],
            ['slug' => 'pork-belly', 'category' => 'protein', 'base_unit_code' => 'g',
                'display_name' => '豚バラ肉', 'aliases' => ['豚バラ', 'ぶたばら']],
            ['slug' => 'pork-koma', 'category' => 'protein', 'base_unit_code' => 'g',
                'display_name' => '豚こま切れ', 'aliases' => ['豚こま', 'ぶたこま']],
            ['slug' => 'beef-thinly-sliced', 'category' => 'protein', 'base_unit_code' => 'g',
                'display_name' => '牛薄切り肉', 'aliases' => ['牛肉', 'ぎゅうにく']],
            ['slug' => 'ground-meat', 'category' => 'protein', 'base_unit_code' => 'g',
                'display_name' => 'ひき肉', 'aliases' => ['挽肉', 'ミンチ']],
            ['slug' => 'bacon', 'category' => 'protein', 'base_unit_code' => 'g',
                'display_name' => 'ベーコン', 'aliases' => []],
            ['slug' => 'sausage', 'category' => 'protein', 'base_unit_code' => 'piece',
                'display_name' => 'ウインナー', 'aliases' => ['ソーセージ', 'ウィンナー']],
            ['slug' => 'salmon', 'category' => 'protein', 'base_unit_code' => 'g',
                'display_name' => '鮭', 'aliases' => ['さけ', 'サーモン']],
            ['slug' => 'mackerel', 'category' => 'protein', 'base_unit_code' => 'g',
                'display_name' => 'さば', 'aliases' => ['鯖']],
            ['slug' => 'canned-tuna', 'category' => 'protein', 'base_unit_code' => 'piece',
                'display_name' => 'ツナ缶', 'aliases' => ['シーチキン', 'ツナ']],
            ['slug' => 'tofu', 'category' => 'protein', 'base_unit_code' => 'g',
                'display_name' => '豆腐', 'aliases' => ['とうふ']],
            ['slug' => 'natto', 'category' => 'protein', 'base_unit_code' => 'pack',
                'display_name' => '納豆', 'aliases' => ['なっとう']],
            ['slug' => 'aburaage', 'category' => 'protein', 'base_unit_code' => 'piece',
                'display_name' => '油揚げ', 'aliases' => ['あぶらあげ']],

            // === Dairy ===
            ['slug' => 'milk', 'category' => 'dairy', 'base_unit_code' => 'ml',
                'display_name' => '牛乳', 'aliases' => ['ぎゅうにゅう', 'ミルク'], 'allergen_codes' => ['milk']],
            ['slug' => 'butter', 'category' => 'dairy', 'base_unit_code' => 'g',
                'display_name' => 'バター', 'aliases' => [], 'allergen_codes' => ['milk']],
            ['slug' => 'cheese', 'category' => 'dairy', 'base_unit_code' => 'g',
                'display_name' => 'チーズ', 'aliases' => ['ピザ用チーズ', 'とろけるチーズ'], 'allergen_codes' => ['milk']],
            ['slug' => 'yogurt', 'category' => 'dairy', 'base_unit_code' => 'g',
                'display_name' => 'ヨーグルト', 'aliases' => [], 'allergen_codes' => ['milk']],

            // === Vegetables ===
            ['slug' => 'onion', 'category' => 'vegetable', 'base_unit_code' => 'piece',
                'display_name' => '玉ねぎ', 'aliases' => ['たまねぎ', '玉葱']],
            ['slug' => 'carrot', 'category' => 'vegetable', 'base_unit_code' => 'piece',
                'display_name' => 'にんじん', 'aliases' => ['人参', 'にんじん']],
            ['slug' => 'potato', 'category' => 'vegetable', 'base_unit_code' => 'piece',
                'display_name' => 'じゃがいも', 'aliases' => ['ジャガイモ', '馬鈴薯', 'ポテト']],
            ['slug' => 'cabbage', 'category' => 'vegetable', 'base_unit_code' => 'piece',
                'display_name' => 'キャベツ', 'aliases' => ['きゃべつ']],
            ['slug' => 'chinese-cabbage', 'category' => 'vegetable', 'base_unit_code' => 'piece',
                'display_name' => '白菜', 'aliases' => ['はくさい']],
            ['slug' => 'lettuce', 'category' => 'vegetable', 'base_unit_code' => 'piece',
                'display_name' => 'レタス', 'aliases' => []],
            ['slug' => 'cucumber', 'category' => 'vegetable', 'base_unit_code' => 'piece',
                'display_name' => 'きゅうり', 'aliases' => ['胡瓜', 'キュウリ']],
            ['slug' => 'tomato', 'category' => 'vegetable', 'base_unit_code' => 'piece',
                'display_name' => 'トマト', 'aliases' => ['とまと']],
            ['slug' => 'bell-pepper', 'category' => 'vegetable', 'base_unit_code' => 'piece',
                'display_name' => 'ピーマン', 'aliases' => []],
            ['slug' => 'eggplant', 'category' => 'vegetable', 'base_unit_code' => 'piece',
                'display_name' => 'なす', 'aliases' => ['茄子', 'ナス']],
            ['slug' => 'daikon', 'category' => 'vegetable', 'base_unit_code' => 'piece',
                'display_name' => '大根', 'aliases' => ['だいこん']],
            ['slug' => 'broccoli', 'category' => 'vegetable', 'base_unit_code' => 'piece',
                'display_name' => 'ブロッコリー', 'aliases' => []],
            ['slug' => 'spinach', 'category' => 'vegetable', 'base_unit_code' => 'bag',
                'display_name' => 'ほうれん草', 'aliases' => ['ほうれんそう']],
            ['slug' => 'komatsuna', 'category' => 'vegetable', 'base_unit_code' => 'bag',
                'display_name' => '小松菜', 'aliases' => ['こまつな']],
            ['slug' => 'long-onion', 'category' => 'vegetable', 'base_unit_code' => 'piece',
                'display_name' => '長ねぎ', 'aliases' => ['ねぎ', '長葱', 'ながねぎ']],
            ['slug' => 'bean-sprouts', 'category' => 'vegetable', 'base_unit_code' => 'bag',
                'display_name' => 'もやし', 'aliases' => []],
            ['slug' => 'shimeji', 'category' => 'vegetable', 'base_unit_code' => 'pack',
                'display_name' => 'しめじ', 'aliases' => []],
            ['slug' => 'enoki', 'category' => 'vegetable', 'base_unit_code' => 'pack',
                'display_name' => 'えのき', 'aliases' => ['エノキ']],
            ['slug' => 'shiitake', 'category' => 'vegetable', 'base_unit_code' => 'pack',
                'display_name' => 'しいたけ', 'aliases' => ['椎茸']],
            ['slug' => 'garlic', 'category' => 'vegetable', 'base_unit_code' => 'piece',
                'display_name' => 'にんにく', 'aliases' => ['ニンニク']],
            ['slug' => 'ginger', 'category' => 'vegetable', 'base_unit_code' => 'piece',
                'display_name' => '生姜', 'aliases' => ['しょうが']],

            // === Pantry / Grain ===
            ['slug' => 'rice', 'category' => 'grain', 'base_unit_code' => 'kg',
                'display_name' => '米', 'aliases' => ['お米', 'こめ']],
            ['slug' => 'bread', 'category' => 'grain', 'base_unit_code' => 'piece',
                'display_name' => '食パン', 'aliases' => ['パン'], 'allergen_codes' => ['wheat']],
            ['slug' => 'pasta', 'category' => 'grain', 'base_unit_code' => 'g',
                'display_name' => 'パスタ', 'aliases' => ['スパゲッティ'], 'allergen_codes' => ['wheat']],
            ['slug' => 'udon', 'category' => 'grain', 'base_unit_code' => 'pack',
                'display_name' => 'うどん', 'aliases' => [], 'allergen_codes' => ['wheat']],
            ['slug' => 'flour', 'category' => 'grain', 'base_unit_code' => 'g',
                'display_name' => '小麦粉', 'aliases' => ['薄力粉'], 'allergen_codes' => ['wheat']],

            // === Seasoning ===
            ['slug' => 'soy-sauce', 'category' => 'seasoning', 'base_unit_code' => 'ml',
                'display_name' => '醤油', 'aliases' => ['しょうゆ'], 'allergen_codes' => ['wheat']],
            ['slug' => 'miso', 'category' => 'seasoning', 'base_unit_code' => 'g',
                'display_name' => '味噌', 'aliases' => ['みそ']],
            ['slug' => 'salt', 'category' => 'seasoning', 'base_unit_code' => 'g',
                'display_name' => '塩', 'aliases' => ['しお']],
            ['slug' => 'sugar', 'category' => 'seasoning', 'base_unit_code' => 'g',
                'display_name' => '砂糖', 'aliases' => ['さとう']],
            ['slug' => 'vinegar', 'category' => 'seasoning', 'base_unit_code' => 'ml',
                'display_name' => '酢', 'aliases' => ['お酢', 's']],
            ['slug' => 'mirin', 'category' => 'seasoning', 'base_unit_code' => 'ml',
                'display_name' => 'みりん', 'aliases' => ['味醂']],
            ['slug' => 'cooking-sake', 'category' => 'seasoning', 'base_unit_code' => 'ml',
                'display_name' => '料理酒', 'aliases' => ['酒']],
            ['slug' => 'oil', 'category' => 'seasoning', 'base_unit_code' => 'ml',
                'display_name' => 'サラダ油', 'aliases' => ['油', 'あぶら']],
            ['slug' => 'sesame-oil', 'category' => 'seasoning', 'base_unit_code' => 'ml',
                'display_name' => 'ごま油', 'aliases' => ['ゴマ油']],
            ['slug' => 'mayonnaise', 'category' => 'seasoning', 'base_unit_code' => 'g',
                'display_name' => 'マヨネーズ', 'aliases' => ['マヨ'], 'allergen_codes' => ['egg']],
            ['slug' => 'ketchup', 'category' => 'seasoning', 'base_unit_code' => 'g',
                'display_name' => 'ケチャップ', 'aliases' => []],
        ];
    }
}
