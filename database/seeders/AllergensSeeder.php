<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AllergensSeeder extends Seeder
{
    public function run(): void
    {
        $allergens = [
            ['code' => 'egg',       'label_ja' => '卵',       'label_en' => 'Egg'],
            ['code' => 'milk',      'label_ja' => '乳',       'label_en' => 'Milk'],
            ['code' => 'wheat',     'label_ja' => '小麦',     'label_en' => 'Wheat'],
            ['code' => 'buckwheat', 'label_ja' => 'そば',     'label_en' => 'Buckwheat'],
            ['code' => 'peanut',    'label_ja' => '落花生',   'label_en' => 'Peanut'],
            ['code' => 'shrimp',    'label_ja' => 'えび',     'label_en' => 'Shrimp'],
            ['code' => 'crab',      'label_ja' => 'かに',     'label_en' => 'Crab'],
            ['code' => 'walnut',    'label_ja' => 'くるみ',   'label_en' => 'Walnut'],
        ];

        $now = now();
        DB::table('allergens')->upsert(
            collect($allergens)->map(fn ($a) => [...$a, 'created_at' => $now, 'updated_at' => $now])->all(),
            ['code'],
            ['label_ja', 'label_en', 'updated_at']
        );
    }
}
