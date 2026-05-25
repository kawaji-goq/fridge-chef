<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UnitsSeeder::class,
            AllergensSeeder::class,
            NutrientsSeeder::class,
            CommonIngredientsSeeder::class,
            IngredientNutrientValuesSeeder::class,
            IngredientUnitConversionsSeeder::class,
            StaplerecipesSeeder::class,
        ]);
    }
}
