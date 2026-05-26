<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 楽天レシピ API クロール（日次、午前 3 時 JST）
Schedule::command('recipes:crawl-rakuten')->dailyAt('03:00');
