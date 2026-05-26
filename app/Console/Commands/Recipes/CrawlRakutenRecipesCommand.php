<?php

namespace App\Console\Commands\Recipes;

use App\Models\Recipe;
use App\Services\Rakuten\RakutenRecipeClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('recipes:crawl-rakuten {--limit=0 : 巡回するカテゴリ数上限（0 = 全件）}')]
#[Description('楽天レシピ API からカテゴリ別ランキングを取得してレシピ DB に蓄積')]
class CrawlRakutenRecipesCommand extends Command
{
    public function handle(): int
    {
        $appId = env('RAKUTEN_APP_ID');
        $accessKey = env('RAKUTEN_ACCESS_KEY');
        if (! $appId || ! $accessKey) {
            $this->error('RAKUTEN_APP_ID と RAKUTEN_ACCESS_KEY が .env に設定されていません。docs/rakuten-setup.md を参照。');

            return self::FAILURE;
        }

        $client = new RakutenRecipeClient($appId, $accessKey, (string) config('app.url'));

        $this->info('カテゴリ一覧を取得中…');
        $categories = $client->categoryList('large');
        if (empty($categories)) {
            $this->error('カテゴリ一覧の取得に失敗しました。');

            return self::FAILURE;
        }

        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $categories = array_slice($categories, 0, $limit);
        }

        $this->info('カテゴリ '.count($categories).' 件を巡回します…');
        $bar = $this->output->createProgressBar(count($categories));
        $bar->start();

        $upserted = 0;
        $errors = 0;

        foreach ($categories as $cat) {
            $categoryId = (string) ($cat['categoryId'] ?? '');
            if ($categoryId === '') {
                $bar->advance();

                continue;
            }

            // 規約: 1 秒以上の間隔
            usleep(1100 * 1000);

            $ranking = $client->categoryRanking($categoryId);
            if (empty($ranking)) {
                $errors++;
                $bar->advance();

                continue;
            }

            foreach ($ranking as $r) {
                if ($this->saveRecipe($r)) {
                    $upserted++;
                }
            }

            $bar->advance();
        }
        $bar->finish();
        $this->newLine();

        $this->info("完了: {$upserted} 件 upsert / {$errors} カテゴリで取得失敗");

        return self::SUCCESS;
    }

    private function saveRecipe(array $r): bool
    {
        $recipeId = $r['recipeId'] ?? null;
        if (! $recipeId) {
            return false;
        }

        // 調理時間（"30分以内" → 30 等）
        $cookMinutes = $this->parseCookMinutes($r['recipeIndication'] ?? '');

        Recipe::updateOrCreate(
            ['source_type' => 'rakuten', 'external_id' => (string) $recipeId],
            [
                'title' => $r['recipeTitle'] ?? '(無題)',
                'locale' => 'ja-JP',
                'servings_default' => 2,
                'total_cook_minutes' => $cookMinutes,
                'instructions' => $r['recipeDescription'] ?? '',
                'materials_text' => $r['recipeMaterial'] ?? [],
                'image_url' => $r['foodImageUrl'] ?? ($r['mediumImageUrl'] ?? null),
                'attribution_url' => $r['recipeUrl'] ?? null,
                'attribution_label' => '楽天レシピ',
            ]
        );

        return true;
    }

    private function parseCookMinutes(string $indication): ?int
    {
        // "30分以内" "1時間以上" "5分以内" 等
        if (preg_match('/(\d+)\s*分/', $indication, $m)) {
            return (int) $m[1];
        }
        if (preg_match('/(\d+)\s*時間/', $indication, $m)) {
            return (int) $m[1] * 60;
        }

        return null;
    }
}
