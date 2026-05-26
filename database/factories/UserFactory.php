<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'account_id' => null,
            'locale' => 'ja-JP',
            'region' => 'JP',
            'last_active_at' => now(),
        ];
    }
}
