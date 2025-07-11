<?php

namespace Database\Factories;

use App\Models\Equipment;
use App\Models\EquipmentCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class EquipmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Equipment::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->words(3, true),
            'model_number' => $this->faker->bothify('??-####'),
            'serial_number' => $this->faker->bothify('SN-########'),
            'equipment_category_id' => EquipmentCategory::factory(),
            'purchase_date' => $this->faker->dateTimeBetween('-3 years', '-1 month'),
            'purchase_cost' => $this->faker->randomFloat(2, 1000, 50000),
            'manufacturer' => $this->faker->company(),
            'supplier' => $this->faker->company(),
            'warranty_expiry' => $this->faker->dateTimeBetween('+1 month', '+2 years'),
            'status' => $this->faker->randomElement(['available', 'in-use', 'maintenance', 'retired']),
            'specifications' => $this->faker->paragraphs(2, true),
            'notes' => $this->faker->optional(0.7)->sentence(),
        ];
    }

    /**
     * Indicate that the equipment is available.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function available()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'available',
            ];
        });
    }

    /**
     * Indicate that the equipment is in use.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function inUse()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'in-use',
            ];
        });
    }

    /**
     * Indicate that the equipment is in maintenance.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function inMaintenance()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'maintenance',
            ];
        });
    }
}