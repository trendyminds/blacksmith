<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sandbox>
 */
class SandboxFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'app_name' => str(fake()->word)->lower(),
            'pr_number' => fake()->randomNumber(3),
            'php_version' => fake()->randomElement(['php74', 'php82', 'php83']),
            'doc_root' => fake()->randomElement(['/web', '/public', '/src/public']),
            'repo' => str(fake()->word.'/'.fake()->word)->lower(),
            'branch' => str(fake()->word)->lower(),
            'aliases' => [],
            'ssl' => fake()->boolean,
        ];
    }
}
