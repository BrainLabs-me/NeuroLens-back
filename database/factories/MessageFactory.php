<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Message;
use App\Models\User;

class MessageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Message::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'bot_message' => $this->faker->numberBetween(-100000, 100000),
            'message' => $this->faker->numberBetween(-100000, 100000),
            'role' => $this->faker->randomElement(["user bot"]),
        ];
    }
}
