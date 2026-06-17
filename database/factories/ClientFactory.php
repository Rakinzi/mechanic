<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $counter = 0;
        $counter++;

        return [
            'company_name' => null,
            'name' => 'Client '.$counter,
            'email' => 'client'.$counter.'@example.com',
            'phone' => '+1 555 000 0'.$counter,
            'address' => $counter.' Main Street',
            'notes' => null,
        ];
    }
}
