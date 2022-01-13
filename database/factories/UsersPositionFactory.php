<?php

namespace Database\Factories;

use App\Models\UsersPosition;
use Illuminate\Database\Eloquent\Factories\Factory;

class UsersPositionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UsersPosition::class;

    /**
     * Первоначальные данные
     * 
     * @var array
     */
    public $positions = [
        'Руководитель колл-центра',
        'Руководитель сектора',
        'Оператор колл-центра',
        'Тренер',
        'Секретарь',
    ];

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $rand = rand(0, count($this->positions) - 1);

        return [
            'name' => $this->positions[$rand],
            'description' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
