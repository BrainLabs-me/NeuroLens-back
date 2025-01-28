<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\EegSignal;
use App\Models\User;

class EegSignalFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = EegSignal::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'signal' => $this->faker->numberBetween(-100000, 100000),
            'user_id' => User::factory(),
        ];
    }
}
