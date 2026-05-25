<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NutrientsSeeder extends Seeder
{
    public function run(): void
    {
        $nutrients = [
            ['code' => 'energy_kcal', 'label_ja' => 'エネルギー', 'unit' => 'kcal'],
            ['code' => 'protein_g',   'label_ja' => 'たんぱく質', 'unit' => 'g'],
            ['code' => 'fat_g',       'label_ja' => '脂質',       'unit' => 'g'],
            ['code' => 'carb_g',      'label_ja' => '炭水化物',   'unit' => 'g'],
            ['code' => 'sodium_mg',   'label_ja' => 'ナトリウム', 'unit' => 'mg'],
        ];

        $now = now();
        DB::table('nutrients')->upsert(
            collect($nutrients)->map(fn ($n) => [...$n, 'created_at' => $now, 'updated_at' => $now])->all(),
            ['code'],
            ['label_ja', 'unit', 'updated_at']
        );
    }
}
