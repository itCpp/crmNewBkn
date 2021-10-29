<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;

class RoleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Role::class;

    /**
     * Стандартные данные
     * 
     * @var array
     */
    protected $roles = [
        [
            'role' => "developer",
            'name' => "Разработчик",
            'comment' => "Имеет доступ абсолютно ко всему функционалу сайта. Имея эту роль, нижние настройки не имеют никакого значения",
        ],
        [
            'role' => "callCenterManager",
            'name' => "Руководитель",
            'comment' => "Руководитель колл-центра",
        ],
        [
            'role' => "admin",
            'name' => "Администратор",
            'comment' => "Распределяет заявки между операторами своего сектора",
        ],
        [
            'role' => "caller",
            'name' => "Оператор",
            'comment' => "Оператор колл-центра",
        ],
        [
            'role' => "secretar",
            'name' => "Секретарь",
            'comment' => "Распределяет заявки между секторами и коллцентрами",
        ],
    ];

    /**
     * Формирование стандартных данных
     * 
     * @return array
     */
    public function makeDefault()
    {
        $count = count($this->roles);
        $state = new Sequence(...$this->roles);

        return $this->count($count)->state($state)->make();
    }

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'role' => "developer",
            'name' => "Разработчик",
            'lvl' => 0,
            'comment' => null,
            'deleted_at' => null,
        ];
    }
}
