<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\RecipeTag;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StaplerecipesSeeder extends Seeder
{
    public function run(): void
    {
        $ingredientsBySlug = Ingredient::all()->keyBy('slug');
        $unitsByCode = Unit::pluck('id', 'code');

        foreach ($this->recipes() as $row) {
            DB::transaction(function () use ($row, $ingredientsBySlug, $unitsByCode) {
                /** @var Recipe $recipe */
                $recipe = Recipe::firstOrCreate(
                    ['source_type' => 'user_created', 'external_id' => 'seed:'.$row['slug']],
                    [
                        'title' => $row['title'],
                        'locale' => 'ja-JP',
                        'servings_default' => $row['servings'],
                        'total_cook_minutes' => $row['minutes'],
                        'instructions' => $row['instructions'],
                        'attribution_label' => '標準レシピ',
                    ]
                );

                // ingredients
                RecipeIngredient::where('recipe_id', $recipe->id)->delete();
                foreach ($row['ingredients'] as $ing) {
                    $ingredient = $ingredientsBySlug->get($ing['slug']);
                    if (! $ingredient) {
                        continue; // 未登録の食材はスキップ
                    }
                    RecipeIngredient::create([
                        'recipe_id' => $recipe->id,
                        'ingredient_id' => $ingredient->id,
                        'quantity' => $ing['quantity'],
                        'unit_id' => $unitsByCode[$ing['unit']],
                        'is_optional' => $ing['optional'] ?? false,
                    ]);
                }

                // tags
                RecipeTag::where('recipe_id', $recipe->id)->delete();
                foreach ($row['tags'] as $tag) {
                    RecipeTag::create([
                        'recipe_id' => $recipe->id,
                        'tag' => $tag,
                    ]);
                }
            });
        }
    }

    private function recipes(): array
    {
        return [
            // ===== 和食 =====
            [
                'slug' => 'nikujaga', 'title' => '肉じゃが', 'servings' => 2, 'minutes' => 30,
                'tags' => ['washoku', 'main_dish', 'simmered'],
                'instructions' => "1. じゃがいもは一口大、玉ねぎは薄切り、にんじんは乱切り。\n2. 鍋に油を熱し、豚肉を炒める。\n3. 野菜を加えてさっと炒め、水と調味料を加え落とし蓋で15分煮る。",
                'ingredients' => [
                    ['slug' => 'pork-koma', 'quantity' => 200, 'unit' => 'g'],
                    ['slug' => 'potato', 'quantity' => 3, 'unit' => 'piece'],
                    ['slug' => 'onion', 'quantity' => 1, 'unit' => 'piece'],
                    ['slug' => 'carrot', 'quantity' => 1, 'unit' => 'piece'],
                    ['slug' => 'soy-sauce', 'quantity' => 60, 'unit' => 'ml'],
                    ['slug' => 'sugar', 'quantity' => 30, 'unit' => 'g'],
                    ['slug' => 'mirin', 'quantity' => 30, 'unit' => 'ml'],
                    ['slug' => 'cooking-sake', 'quantity' => 30, 'unit' => 'ml'],
                ],
            ],
            [
                'slug' => 'shogayaki', 'title' => '豚の生姜焼き', 'servings' => 2, 'minutes' => 15,
                'tags' => ['washoku', 'main_dish'],
                'instructions' => "1. 豚肉に薄く小麦粉をまぶす。\n2. すりおろし生姜・醤油・みりん・酒を混ぜタレを作る。\n3. フライパンで豚肉を焼き、タレを絡める。",
                'ingredients' => [
                    ['slug' => 'pork-belly', 'quantity' => 250, 'unit' => 'g'],
                    ['slug' => 'ginger', 'quantity' => 1, 'unit' => 'piece'],
                    ['slug' => 'soy-sauce', 'quantity' => 30, 'unit' => 'ml'],
                    ['slug' => 'mirin', 'quantity' => 30, 'unit' => 'ml'],
                    ['slug' => 'cooking-sake', 'quantity' => 15, 'unit' => 'ml'],
                    ['slug' => 'oil', 'quantity' => 15, 'unit' => 'ml'],
                    ['slug' => 'flour', 'quantity' => 10, 'unit' => 'g', 'optional' => true],
                ],
            ],
            [
                'slug' => 'oyakodon', 'title' => '親子丼', 'servings' => 2, 'minutes' => 20,
                'tags' => ['washoku', 'donburi', 'main_dish'],
                'instructions' => "1. 鶏もも肉と玉ねぎを切り、めんつゆで煮る。\n2. 溶き卵を回し入れ半熟で火を止める。\n3. ご飯にのせる。",
                'ingredients' => [
                    ['slug' => 'chicken-thigh', 'quantity' => 200, 'unit' => 'g'],
                    ['slug' => 'onion', 'quantity' => 1, 'unit' => 'piece'],
                    ['slug' => 'egg', 'quantity' => 3, 'unit' => 'piece'],
                    ['slug' => 'soy-sauce', 'quantity' => 45, 'unit' => 'ml'],
                    ['slug' => 'mirin', 'quantity' => 30, 'unit' => 'ml'],
                    ['slug' => 'sugar', 'quantity' => 10, 'unit' => 'g'],
                    ['slug' => 'rice', 'quantity' => 0.4, 'unit' => 'kg'],
                ],
            ],
            [
                'slug' => 'mabodofu', 'title' => '麻婆豆腐', 'servings' => 2, 'minutes' => 20,
                'tags' => ['chuka', 'main_dish'],
                'instructions' => "1. 豆腐は1.5cm角に切る。\n2. にんにく生姜長ねぎを炒め、ひき肉を加える。\n3. 味噌・醤油・砂糖で味付け、豆腐を加えて煮る。\n4. 仕上げにごま油。",
                'ingredients' => [
                    ['slug' => 'ground-meat', 'quantity' => 150, 'unit' => 'g'],
                    ['slug' => 'tofu', 'quantity' => 300, 'unit' => 'g'],
                    ['slug' => 'long-onion', 'quantity' => 1, 'unit' => 'piece'],
                    ['slug' => 'garlic', 'quantity' => 2, 'unit' => 'piece'],
                    ['slug' => 'ginger', 'quantity' => 1, 'unit' => 'piece'],
                    ['slug' => 'miso', 'quantity' => 30, 'unit' => 'g'],
                    ['slug' => 'soy-sauce', 'quantity' => 15, 'unit' => 'ml'],
                    ['slug' => 'sugar', 'quantity' => 5, 'unit' => 'g'],
                    ['slug' => 'sesame-oil', 'quantity' => 10, 'unit' => 'ml'],
                ],
            ],
            [
                'slug' => 'teriyaki-chicken', 'title' => '鶏の照り焼き', 'servings' => 2, 'minutes' => 20,
                'tags' => ['washoku', 'main_dish'],
                'instructions' => "1. 鶏もも肉の皮目を下にしてフライパンで焼く。\n2. 余分な油を拭き、醤油・みりん・砂糖・酒を加えて煮絡める。",
                'ingredients' => [
                    ['slug' => 'chicken-thigh', 'quantity' => 300, 'unit' => 'g'],
                    ['slug' => 'soy-sauce', 'quantity' => 30, 'unit' => 'ml'],
                    ['slug' => 'mirin', 'quantity' => 30, 'unit' => 'ml'],
                    ['slug' => 'sugar', 'quantity' => 10, 'unit' => 'g'],
                    ['slug' => 'cooking-sake', 'quantity' => 15, 'unit' => 'ml'],
                ],
            ],
            [
                'slug' => 'karaage', 'title' => '鶏の唐揚げ', 'servings' => 2, 'minutes' => 25,
                'tags' => ['washoku', 'main_dish', 'fried'],
                'instructions' => "1. 鶏もも肉を一口大に切り、醤油・酒・生姜・にんにくに15分漬ける。\n2. 小麦粉をまぶして170℃の油で揚げる。\n※ 揚げ油は別途用意（吸油は約30ml）",
                'ingredients' => [
                    ['slug' => 'chicken-thigh', 'quantity' => 400, 'unit' => 'g'],
                    ['slug' => 'soy-sauce', 'quantity' => 30, 'unit' => 'ml'],
                    ['slug' => 'cooking-sake', 'quantity' => 15, 'unit' => 'ml'],
                    ['slug' => 'ginger', 'quantity' => 1, 'unit' => 'piece'],
                    ['slug' => 'garlic', 'quantity' => 1, 'unit' => 'piece'],
                    ['slug' => 'flour', 'quantity' => 60, 'unit' => 'g'],
                    ['slug' => 'oil', 'quantity' => 30, 'unit' => 'ml'], // 吸油量のみ
                ],
            ],
            [
                'slug' => 'hamburg', 'title' => 'ハンバーグ', 'servings' => 2, 'minutes' => 30,
                'tags' => ['yoshoku', 'main_dish'],
                'instructions' => "1. 玉ねぎをみじん切りにして炒め冷ます。\n2. ひき肉・卵・牛乳・塩を混ぜ、玉ねぎを加えて成形。\n3. 中火で両面焼く。",
                'ingredients' => [
                    ['slug' => 'ground-meat', 'quantity' => 300, 'unit' => 'g'],
                    ['slug' => 'onion', 'quantity' => 1, 'unit' => 'piece'],
                    ['slug' => 'egg', 'quantity' => 1, 'unit' => 'piece'],
                    ['slug' => 'milk', 'quantity' => 30, 'unit' => 'ml'],
                    ['slug' => 'salt', 'quantity' => 3, 'unit' => 'g'],
                    ['slug' => 'ketchup', 'quantity' => 30, 'unit' => 'g', 'optional' => true],
                ],
            ],
            [
                'slug' => 'chahan', 'title' => 'チャーハン', 'servings' => 2, 'minutes' => 15,
                'tags' => ['chuka', 'rice'],
                'instructions' => "1. 卵を溶き、ご飯にあらかじめ混ぜておく。\n2. フライパンで卵入りご飯を炒める。\n3. ベーコン・長ねぎを加え、塩・醤油で味付け。",
                'ingredients' => [
                    ['slug' => 'rice', 'quantity' => 0.4, 'unit' => 'kg'],
                    ['slug' => 'egg', 'quantity' => 2, 'unit' => 'piece'],
                    ['slug' => 'long-onion', 'quantity' => 1, 'unit' => 'piece'],
                    ['slug' => 'bacon', 'quantity' => 50, 'unit' => 'g'],
                    ['slug' => 'oil', 'quantity' => 15, 'unit' => 'ml'],
                    ['slug' => 'soy-sauce', 'quantity' => 10, 'unit' => 'ml'],
                    ['slug' => 'salt', 'quantity' => 2, 'unit' => 'g'],
                ],
            ],
            [
                'slug' => 'gyudon', 'title' => '牛丼', 'servings' => 2, 'minutes' => 20,
                'tags' => ['washoku', 'donburi'],
                'instructions' => "1. 牛肉と玉ねぎを醤油・砂糖・みりん・酒で煮る。\n2. ご飯にのせる。",
                'ingredients' => [
                    ['slug' => 'beef-thinly-sliced', 'quantity' => 200, 'unit' => 'g'],
                    ['slug' => 'onion', 'quantity' => 1, 'unit' => 'piece'],
                    ['slug' => 'soy-sauce', 'quantity' => 45, 'unit' => 'ml'],
                    ['slug' => 'mirin', 'quantity' => 30, 'unit' => 'ml'],
                    ['slug' => 'sugar', 'quantity' => 15, 'unit' => 'g'],
                    ['slug' => 'cooking-sake', 'quantity' => 30, 'unit' => 'ml'],
                    ['slug' => 'rice', 'quantity' => 0.4, 'unit' => 'kg'],
                ],
            ],
            [
                'slug' => 'tamago-kake-gohan', 'title' => '卵かけご飯', 'servings' => 1, 'minutes' => 3,
                'tags' => ['washoku', 'rice', 'quick'],
                'instructions' => "1. 温かいご飯に卵を割り入れる。\n2. 醤油を回しかけて混ぜる。",
                'ingredients' => [
                    ['slug' => 'rice', 'quantity' => 0.2, 'unit' => 'kg'],
                    ['slug' => 'egg', 'quantity' => 1, 'unit' => 'piece'],
                    ['slug' => 'soy-sauce', 'quantity' => 5, 'unit' => 'ml'],
                ],
            ],
            [
                'slug' => 'miso-soup', 'title' => '味噌汁', 'servings' => 2, 'minutes' => 10,
                'tags' => ['washoku', 'soup'],
                'instructions' => "1. 水を沸かし、豆腐と長ねぎを加える。\n2. 火を弱めて味噌を溶き入れる。",
                'ingredients' => [
                    ['slug' => 'tofu', 'quantity' => 100, 'unit' => 'g'],
                    ['slug' => 'long-onion', 'quantity' => 0.5, 'unit' => 'piece'],
                    ['slug' => 'miso', 'quantity' => 30, 'unit' => 'g'],
                ],
            ],
            [
                'slug' => 'tonjiru', 'title' => '豚汁', 'servings' => 2, 'minutes' => 25,
                'tags' => ['washoku', 'soup'],
                'instructions' => "1. 豚肉と野菜を切り、ごま油で炒める。\n2. 水を加え煮込み、味噌を溶く。\n3. 仕上げに長ねぎ。",
                'ingredients' => [
                    ['slug' => 'pork-koma', 'quantity' => 150, 'unit' => 'g'],
                    ['slug' => 'daikon', 'quantity' => 0.3, 'unit' => 'piece'],
                    ['slug' => 'carrot', 'quantity' => 0.5, 'unit' => 'piece'],
                    ['slug' => 'tofu', 'quantity' => 100, 'unit' => 'g'],
                    ['slug' => 'long-onion', 'quantity' => 0.5, 'unit' => 'piece'],
                    ['slug' => 'miso', 'quantity' => 45, 'unit' => 'g'],
                    ['slug' => 'sesame-oil', 'quantity' => 10, 'unit' => 'ml'],
                ],
            ],

            // ===== 洋食 =====
            [
                'slug' => 'napolitan', 'title' => 'ナポリタン', 'servings' => 2, 'minutes' => 20,
                'tags' => ['yoshoku', 'pasta'],
                'instructions' => "1. パスタを表示時間より少し長めに茹でる。\n2. ウインナーと野菜を炒め、ケチャップで味付け。\n3. パスタを加えて絡める。",
                'ingredients' => [
                    ['slug' => 'pasta', 'quantity' => 200, 'unit' => 'g'],
                    ['slug' => 'sausage', 'quantity' => 4, 'unit' => 'piece'],
                    ['slug' => 'onion', 'quantity' => 1, 'unit' => 'piece'],
                    ['slug' => 'bell-pepper', 'quantity' => 1, 'unit' => 'piece'],
                    ['slug' => 'ketchup', 'quantity' => 80, 'unit' => 'g'],
                    ['slug' => 'oil', 'quantity' => 15, 'unit' => 'ml'],
                ],
            ],
            [
                'slug' => 'peperoncino', 'title' => 'ペペロンチーノ', 'servings' => 2, 'minutes' => 15,
                'tags' => ['italian', 'pasta', 'quick'],
                'instructions' => "1. パスタを茹でる。\n2. オリーブオイルでにんにくをじっくり炒める。\n3. パスタの茹で汁を加えて乳化、パスタを和える。",
                'ingredients' => [
                    ['slug' => 'pasta', 'quantity' => 200, 'unit' => 'g'],
                    ['slug' => 'garlic', 'quantity' => 2, 'unit' => 'piece'],
                    ['slug' => 'oil', 'quantity' => 30, 'unit' => 'ml'],
                    ['slug' => 'salt', 'quantity' => 5, 'unit' => 'g'],
                ],
            ],
            [
                'slug' => 'carbonara', 'title' => 'カルボナーラ', 'servings' => 2, 'minutes' => 20,
                'tags' => ['italian', 'pasta'],
                'instructions' => "1. パスタを茹でる。\n2. ベーコンを炒める。\n3. 卵・チーズ・牛乳を混ぜたソースを火を止めてパスタに絡める。",
                'ingredients' => [
                    ['slug' => 'pasta', 'quantity' => 200, 'unit' => 'g'],
                    ['slug' => 'egg', 'quantity' => 2, 'unit' => 'piece'],
                    ['slug' => 'bacon', 'quantity' => 80, 'unit' => 'g'],
                    ['slug' => 'cheese', 'quantity' => 40, 'unit' => 'g'],
                    ['slug' => 'milk', 'quantity' => 50, 'unit' => 'ml'],
                    ['slug' => 'salt', 'quantity' => 3, 'unit' => 'g'],
                ],
            ],
            [
                'slug' => 'omurice', 'title' => 'オムライス', 'servings' => 2, 'minutes' => 25,
                'tags' => ['yoshoku', 'rice'],
                'instructions' => "1. 鶏肉と玉ねぎを炒め、ご飯とケチャップでチキンライスを作る。\n2. 卵を溶きフライパンで焼き、ご飯にかぶせる。",
                'ingredients' => [
                    ['slug' => 'rice', 'quantity' => 0.4, 'unit' => 'kg'],
                    ['slug' => 'egg', 'quantity' => 4, 'unit' => 'piece'],
                    ['slug' => 'chicken-breast', 'quantity' => 150, 'unit' => 'g'],
                    ['slug' => 'onion', 'quantity' => 1, 'unit' => 'piece'],
                    ['slug' => 'ketchup', 'quantity' => 60, 'unit' => 'g'],
                    ['slug' => 'butter', 'quantity' => 20, 'unit' => 'g'],
                ],
            ],
            [
                'slug' => 'potato-salad', 'title' => 'ポテトサラダ', 'servings' => 3, 'minutes' => 25,
                'tags' => ['yoshoku', 'side_dish', 'cold'],
                'instructions' => "1. じゃがいもを茹でて潰す。\n2. きゅうり・にんじん・玉ねぎを薄切りにして塩もみ。\n3. マヨネーズで和える。",
                'ingredients' => [
                    ['slug' => 'potato', 'quantity' => 3, 'unit' => 'piece'],
                    ['slug' => 'cucumber', 'quantity' => 1, 'unit' => 'piece'],
                    ['slug' => 'carrot', 'quantity' => 0.5, 'unit' => 'piece'],
                    ['slug' => 'onion', 'quantity' => 0.5, 'unit' => 'piece'],
                    ['slug' => 'mayonnaise', 'quantity' => 50, 'unit' => 'g'],
                    ['slug' => 'salt', 'quantity' => 3, 'unit' => 'g'],
                ],
            ],
            [
                'slug' => 'omelette', 'title' => 'チーズオムレツ', 'servings' => 1, 'minutes' => 8,
                'tags' => ['yoshoku', 'quick', 'main_dish'],
                'instructions' => "1. 卵を溶き、牛乳と塩を加える。\n2. バターを熱したフライパンに流し入れ、チーズを入れて巻く。",
                'ingredients' => [
                    ['slug' => 'egg', 'quantity' => 3, 'unit' => 'piece'],
                    ['slug' => 'milk', 'quantity' => 30, 'unit' => 'ml'],
                    ['slug' => 'cheese', 'quantity' => 30, 'unit' => 'g'],
                    ['slug' => 'butter', 'quantity' => 10, 'unit' => 'g'],
                    ['slug' => 'salt', 'quantity' => 1, 'unit' => 'g'],
                ],
            ],

            // ===== 副菜 =====
            [
                'slug' => 'spinach-ohitashi', 'title' => 'ほうれん草のおひたし', 'servings' => 2, 'minutes' => 10,
                'tags' => ['washoku', 'side_dish', 'quick'],
                'instructions' => "1. ほうれん草を茹で、冷水にとる。\n2. 水気を絞り、醤油をかける。",
                'ingredients' => [
                    ['slug' => 'spinach', 'quantity' => 1, 'unit' => 'bag'],
                    ['slug' => 'soy-sauce', 'quantity' => 15, 'unit' => 'ml'],
                ],
            ],
            [
                'slug' => 'komatsuna-ageage', 'title' => '小松菜と油揚げの煮浸し', 'servings' => 2, 'minutes' => 15,
                'tags' => ['washoku', 'side_dish'],
                'instructions' => "1. 小松菜を切り、油揚げを湯通しして短冊切り。\n2. 醤油・みりんで軽く煮る。",
                'ingredients' => [
                    ['slug' => 'komatsuna', 'quantity' => 1, 'unit' => 'bag'],
                    ['slug' => 'aburaage', 'quantity' => 1, 'unit' => 'piece'],
                    ['slug' => 'soy-sauce', 'quantity' => 20, 'unit' => 'ml'],
                    ['slug' => 'mirin', 'quantity' => 15, 'unit' => 'ml'],
                ],
            ],
            [
                'slug' => 'tomato-salad', 'title' => 'トマトサラダ', 'servings' => 2, 'minutes' => 5,
                'tags' => ['yoshoku', 'side_dish', 'cold', 'quick'],
                'instructions' => "1. トマトをくし切り。\n2. オリーブオイルと塩を振る。",
                'ingredients' => [
                    ['slug' => 'tomato', 'quantity' => 2, 'unit' => 'piece'],
                    ['slug' => 'oil', 'quantity' => 15, 'unit' => 'ml'],
                    ['slug' => 'salt', 'quantity' => 2, 'unit' => 'g'],
                ],
            ],
            [
                'slug' => 'tamagoyaki', 'title' => '卵焼き', 'servings' => 2, 'minutes' => 10,
                'tags' => ['washoku', 'side_dish', 'quick'],
                'instructions' => "1. 卵を溶き砂糖・醤油・塩を加える。\n2. 卵焼き器で巻きながら焼く。",
                'ingredients' => [
                    ['slug' => 'egg', 'quantity' => 3, 'unit' => 'piece'],
                    ['slug' => 'sugar', 'quantity' => 10, 'unit' => 'g'],
                    ['slug' => 'soy-sauce', 'quantity' => 5, 'unit' => 'ml'],
                    ['slug' => 'salt', 'quantity' => 1, 'unit' => 'g'],
                    ['slug' => 'oil', 'quantity' => 5, 'unit' => 'ml'],
                ],
            ],

            // ===== 追加メニュー =====
            [
                'slug' => 'niku-yasai-itame', 'title' => '肉野菜炒め', 'servings' => 2, 'minutes' => 15,
                'tags' => ['chuka', 'main_dish', 'quick'],
                'instructions' => "1. 豚こまを炒める。\n2. キャベツ・にんじん・ピーマンを加えて強火で炒める。\n3. 醤油・塩で味付け。",
                'ingredients' => [
                    ['slug' => 'pork-koma', 'quantity' => 200, 'unit' => 'g'],
                    ['slug' => 'cabbage', 'quantity' => 0.2, 'unit' => 'piece'],
                    ['slug' => 'carrot', 'quantity' => 0.5, 'unit' => 'piece'],
                    ['slug' => 'bell-pepper', 'quantity' => 2, 'unit' => 'piece'],
                    ['slug' => 'soy-sauce', 'quantity' => 20, 'unit' => 'ml'],
                    ['slug' => 'salt', 'quantity' => 2, 'unit' => 'g'],
                    ['slug' => 'oil', 'quantity' => 15, 'unit' => 'ml'],
                ],
            ],
            [
                'slug' => 'yakizake', 'title' => '焼き鮭', 'servings' => 2, 'minutes' => 15,
                'tags' => ['washoku', 'main_dish', 'quick'],
                'instructions' => "1. 鮭に塩を振り 10 分置く。\n2. グリルかフライパンで両面焼く。",
                'ingredients' => [
                    ['slug' => 'salmon', 'quantity' => 200, 'unit' => 'g'],
                    ['slug' => 'salt', 'quantity' => 2, 'unit' => 'g'],
                ],
            ],
            [
                'slug' => 'salmon-mukniel', 'title' => '鮭のムニエル', 'servings' => 2, 'minutes' => 15,
                'tags' => ['yoshoku', 'main_dish'],
                'instructions' => "1. 鮭に塩を振り小麦粉をまぶす。\n2. バターを溶かしたフライパンで両面焼く。",
                'ingredients' => [
                    ['slug' => 'salmon', 'quantity' => 200, 'unit' => 'g'],
                    ['slug' => 'salt', 'quantity' => 2, 'unit' => 'g'],
                    ['slug' => 'flour', 'quantity' => 15, 'unit' => 'g'],
                    ['slug' => 'butter', 'quantity' => 15, 'unit' => 'g'],
                ],
            ],
            [
                'slug' => 'soboro-don', 'title' => 'そぼろ丼', 'servings' => 2, 'minutes' => 15,
                'tags' => ['washoku', 'donburi'],
                'instructions' => "1. ひき肉を炒め、醤油・砂糖・酒で味付け。\n2. 別で卵をいり卵にする。\n3. ご飯にのせる。",
                'ingredients' => [
                    ['slug' => 'ground-meat', 'quantity' => 200, 'unit' => 'g'],
                    ['slug' => 'egg', 'quantity' => 2, 'unit' => 'piece'],
                    ['slug' => 'soy-sauce', 'quantity' => 30, 'unit' => 'ml'],
                    ['slug' => 'sugar', 'quantity' => 15, 'unit' => 'g'],
                    ['slug' => 'cooking-sake', 'quantity' => 15, 'unit' => 'ml'],
                    ['slug' => 'rice', 'quantity' => 0.4, 'unit' => 'kg'],
                ],
            ],
            [
                'slug' => 'nasu-nibitashi', 'title' => 'なすの煮浸し', 'servings' => 2, 'minutes' => 20,
                'tags' => ['washoku', 'side_dish', 'simmered'],
                'instructions' => "1. なすを乱切りにして油で焼く。\n2. 醤油・みりん・砂糖・水で煮る。",
                'ingredients' => [
                    ['slug' => 'eggplant', 'quantity' => 3, 'unit' => 'piece'],
                    ['slug' => 'soy-sauce', 'quantity' => 30, 'unit' => 'ml'],
                    ['slug' => 'mirin', 'quantity' => 30, 'unit' => 'ml'],
                    ['slug' => 'sugar', 'quantity' => 5, 'unit' => 'g'],
                    ['slug' => 'oil', 'quantity' => 15, 'unit' => 'ml'],
                ],
            ],
            [
                'slug' => 'hiyayakko', 'title' => '冷奴', 'servings' => 2, 'minutes' => 5,
                'tags' => ['washoku', 'side_dish', 'cold', 'quick'],
                'instructions' => "1. 豆腐を皿に盛る。\n2. 長ねぎと生姜をのせ醤油をかける。",
                'ingredients' => [
                    ['slug' => 'tofu', 'quantity' => 300, 'unit' => 'g'],
                    ['slug' => 'long-onion', 'quantity' => 0.3, 'unit' => 'piece'],
                    ['slug' => 'ginger', 'quantity' => 0.3, 'unit' => 'piece'],
                    ['slug' => 'soy-sauce', 'quantity' => 15, 'unit' => 'ml'],
                ],
            ],
            [
                'slug' => 'kakitama-soup', 'title' => 'かき玉スープ', 'servings' => 2, 'minutes' => 10,
                'tags' => ['chuka', 'soup', 'quick'],
                'instructions' => "1. 水を沸かして醤油・塩で味付け。\n2. 溶き卵を回し入れ、長ねぎを加える。",
                'ingredients' => [
                    ['slug' => 'egg', 'quantity' => 2, 'unit' => 'piece'],
                    ['slug' => 'long-onion', 'quantity' => 0.3, 'unit' => 'piece'],
                    ['slug' => 'soy-sauce', 'quantity' => 15, 'unit' => 'ml'],
                    ['slug' => 'salt', 'quantity' => 2, 'unit' => 'g'],
                    ['slug' => 'sesame-oil', 'quantity' => 5, 'unit' => 'ml'],
                ],
            ],
            [
                'slug' => 'garlic-rice', 'title' => 'ガーリックライス', 'servings' => 2, 'minutes' => 15,
                'tags' => ['yoshoku', 'rice'],
                'instructions' => "1. にんにくをみじん切り、バターで炒める。\n2. ご飯と醤油・塩を加えて炒める。",
                'ingredients' => [
                    ['slug' => 'rice', 'quantity' => 0.4, 'unit' => 'kg'],
                    ['slug' => 'garlic', 'quantity' => 3, 'unit' => 'piece'],
                    ['slug' => 'butter', 'quantity' => 20, 'unit' => 'g'],
                    ['slug' => 'soy-sauce', 'quantity' => 10, 'unit' => 'ml'],
                    ['slug' => 'salt', 'quantity' => 2, 'unit' => 'g'],
                ],
            ],
            [
                'slug' => 'reishabu-salad', 'title' => '冷しゃぶサラダ', 'servings' => 2, 'minutes' => 15,
                'tags' => ['washoku', 'side_dish', 'cold'],
                'instructions' => "1. 豚こまをサッと茹で冷水で締める。\n2. レタス・きゅうり・トマトに乗せポン酢風（醤油＋酢）をかける。",
                'ingredients' => [
                    ['slug' => 'pork-koma', 'quantity' => 200, 'unit' => 'g'],
                    ['slug' => 'lettuce', 'quantity' => 0.3, 'unit' => 'piece'],
                    ['slug' => 'cucumber', 'quantity' => 1, 'unit' => 'piece'],
                    ['slug' => 'tomato', 'quantity' => 1, 'unit' => 'piece'],
                    ['slug' => 'soy-sauce', 'quantity' => 20, 'unit' => 'ml'],
                    ['slug' => 'vinegar', 'quantity' => 15, 'unit' => 'ml'],
                ],
            ],
            [
                'slug' => 'yakitori-style', 'title' => '焼き鳥風', 'servings' => 2, 'minutes' => 20,
                'tags' => ['washoku', 'main_dish'],
                'instructions' => "1. 鶏もも肉と長ねぎを一口大に切る。\n2. フライパンで焼き、醤油・みりん・砂糖・酒のタレを絡める。",
                'ingredients' => [
                    ['slug' => 'chicken-thigh', 'quantity' => 300, 'unit' => 'g'],
                    ['slug' => 'long-onion', 'quantity' => 1, 'unit' => 'piece'],
                    ['slug' => 'soy-sauce', 'quantity' => 30, 'unit' => 'ml'],
                    ['slug' => 'mirin', 'quantity' => 30, 'unit' => 'ml'],
                    ['slug' => 'sugar', 'quantity' => 15, 'unit' => 'g'],
                    ['slug' => 'cooking-sake', 'quantity' => 15, 'unit' => 'ml'],
                ],
            ],

            // ===== パン系 =====
            [
                'slug' => 'tamago-sando', 'title' => '卵サンド', 'servings' => 2, 'minutes' => 10,
                'tags' => ['yoshoku', 'bread', 'cold', 'quick'],
                'instructions' => "1. 卵を茹でて潰す。\n2. マヨネーズと塩で和える。\n3. 食パンに挟む。",
                'ingredients' => [
                    ['slug' => 'bread', 'quantity' => 4, 'unit' => 'piece'],
                    ['slug' => 'egg', 'quantity' => 3, 'unit' => 'piece'],
                    ['slug' => 'mayonnaise', 'quantity' => 30, 'unit' => 'g'],
                    ['slug' => 'salt', 'quantity' => 1, 'unit' => 'g'],
                ],
            ],
            [
                'slug' => 'pizza-toast', 'title' => 'ピザトースト', 'servings' => 2, 'minutes' => 10,
                'tags' => ['yoshoku', 'bread', 'quick'],
                'instructions' => "1. 食パンにケチャップを塗る。\n2. ピーマン・ソーセージ・チーズをのせる。\n3. トースターで焼く。",
                'ingredients' => [
                    ['slug' => 'bread', 'quantity' => 2, 'unit' => 'piece'],
                    ['slug' => 'ketchup', 'quantity' => 30, 'unit' => 'g'],
                    ['slug' => 'cheese', 'quantity' => 60, 'unit' => 'g'],
                    ['slug' => 'bell-pepper', 'quantity' => 1, 'unit' => 'piece'],
                    ['slug' => 'sausage', 'quantity' => 2, 'unit' => 'piece', 'optional' => true],
                ],
            ],
            [
                'slug' => 'french-toast', 'title' => 'フレンチトースト', 'servings' => 2, 'minutes' => 15,
                'tags' => ['yoshoku', 'bread'],
                'instructions' => "1. 卵・牛乳・砂糖を混ぜ、食パンを浸す。\n2. バターを溶かしたフライパンで両面焼く。",
                'ingredients' => [
                    ['slug' => 'bread', 'quantity' => 2, 'unit' => 'piece'],
                    ['slug' => 'egg', 'quantity' => 2, 'unit' => 'piece'],
                    ['slug' => 'milk', 'quantity' => 100, 'unit' => 'ml'],
                    ['slug' => 'sugar', 'quantity' => 20, 'unit' => 'g'],
                    ['slug' => 'butter', 'quantity' => 15, 'unit' => 'g'],
                ],
            ],
            [
                'slug' => 'cheese-toast', 'title' => 'チーズトースト', 'servings' => 1, 'minutes' => 5,
                'tags' => ['yoshoku', 'bread', 'quick'],
                'instructions' => "1. 食パンにバターを塗り、チーズをのせる。\n2. トースターで焼く。",
                'ingredients' => [
                    ['slug' => 'bread', 'quantity' => 1, 'unit' => 'piece'],
                    ['slug' => 'cheese', 'quantity' => 30, 'unit' => 'g'],
                    ['slug' => 'butter', 'quantity' => 5, 'unit' => 'g', 'optional' => true],
                ],
            ],

            // ===== 鍋 =====
            [
                'slug' => 'yose-nabe', 'title' => '寄せ鍋', 'servings' => 3, 'minutes' => 30,
                'tags' => ['washoku', 'nabe', 'warm'],
                'instructions' => "1. 白菜・長ねぎ・しめじを切る。\n2. 鶏肉・豆腐と一緒に煮込み、醤油・みりんで味付け。",
                'ingredients' => [
                    ['slug' => 'chicken-thigh', 'quantity' => 300, 'unit' => 'g'],
                    ['slug' => 'chinese-cabbage', 'quantity' => 0.25, 'unit' => 'piece'],
                    ['slug' => 'long-onion', 'quantity' => 1, 'unit' => 'piece'],
                    ['slug' => 'shimeji', 'quantity' => 1, 'unit' => 'pack'],
                    ['slug' => 'tofu', 'quantity' => 300, 'unit' => 'g'],
                    ['slug' => 'soy-sauce', 'quantity' => 60, 'unit' => 'ml'],
                    ['slug' => 'mirin', 'quantity' => 30, 'unit' => 'ml'],
                    ['slug' => 'cooking-sake', 'quantity' => 30, 'unit' => 'ml'],
                ],
            ],
            [
                'slug' => 'sukiyaki', 'title' => 'すき焼き', 'servings' => 3, 'minutes' => 30,
                'tags' => ['washoku', 'nabe', 'warm'],
                'instructions' => "1. 牛肉を焼き、砂糖と醤油・酒で割り下を作る。\n2. 白菜・長ねぎ・しめじ・豆腐を加えて煮込む。\n3. 溶き卵につけて食べる。",
                'ingredients' => [
                    ['slug' => 'beef-thinly-sliced', 'quantity' => 400, 'unit' => 'g'],
                    ['slug' => 'chinese-cabbage', 'quantity' => 0.25, 'unit' => 'piece'],
                    ['slug' => 'long-onion', 'quantity' => 1, 'unit' => 'piece'],
                    ['slug' => 'shimeji', 'quantity' => 1, 'unit' => 'pack'],
                    ['slug' => 'tofu', 'quantity' => 300, 'unit' => 'g'],
                    ['slug' => 'soy-sauce', 'quantity' => 80, 'unit' => 'ml'],
                    ['slug' => 'sugar', 'quantity' => 40, 'unit' => 'g'],
                    ['slug' => 'cooking-sake', 'quantity' => 60, 'unit' => 'ml'],
                    ['slug' => 'egg', 'quantity' => 3, 'unit' => 'piece'],
                ],
            ],
        ];
    }
}
