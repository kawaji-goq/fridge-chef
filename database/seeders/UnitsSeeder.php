<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitsSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['code' => 'g',     'label_ja' => 'グラム',     'kind' => 'mass'],
            ['code' => 'kg',    'label_ja' => 'キログラム', 'kind' => 'mass'],
            ['code' => 'ml',    'label_ja' => 'ミリリットル', 'kind' => 'volume'],
            ['code' => 'l',     'label_ja' => 'リットル',   'kind' => 'volume'],
            ['code' => 'piece', 'label_ja' => '個',         'kind' => 'count'],
            ['code' => 'pack',  'label_ja' => 'パック',     'kind' => 'count'],
            ['code' => 'bag',   'label_ja' => '袋',         'kind' => 'count'],
            ['code' => 'tbsp',  'label_ja' => '大さじ',     'kind' => 'volume'],
            ['code' => 'tsp',   'label_ja' => '小さじ',     'kind' => 'volume'],
            ['code' => 'cup',   'label_ja' => 'カップ',     'kind' => 'volume'],
            ['code' => 'go',    'label_ja' => '合',         'kind' => 'volume'],
        ];

        $now = now();
        DB::table('units')->upsert(
            collect($units)->map(fn ($u) => [...$u, 'created_at' => $now, 'updated_at' => $now])->all(),
            ['code'],
            ['label_ja', 'kind', 'updated_at']
        );
    }
}
