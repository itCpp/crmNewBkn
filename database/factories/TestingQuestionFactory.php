<?php

namespace Database\Factories;

use App\Models\TestingQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

class TestingQuestionFactory extends Factory
{
    use Traits\TestingQuestionsList;

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TestingQuestion::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $rand = rand(0, count($this->questions) - 1);

        return [
            'question' => $this->questions[$rand]['question'],
            'theme' => $this->questions[$rand]['theme'] ?? null,
            'answers' => $this->questions[$rand]['answers'],
            'right_answers' => $this->questions[$rand]['right_answers'],
        ];
    }
}
