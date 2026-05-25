<?php

namespace App\Console\Commands\Recipes;

use App\Services\Nutrition\NutritionCalculator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('recipes:calculate-nutrition')]
#[Description('全レシピの栄養値（per_serving）を再計算')]
class CalculateNutritionCommand extends Command
{
    public function handle(NutritionCalculator $calculator): int
    {
        $this->info('栄養値を計算中…');
        $count = $calculator->calculateAll();
        $this->info("完了：{$count} 件のレシピを更新しました。");

        return self::SUCCESS;
    }
}
