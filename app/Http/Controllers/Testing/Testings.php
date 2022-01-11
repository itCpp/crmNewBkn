<?php

namespace App\Http\Controllers\Testing;

use App\Models\TestingProcess;
use App\Models\TestingQuestion;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class Testings extends Controller
{
    /**
     * Количество вопросов в тестировании
     * 
     * @var int
     */
    const QUESTIONS_COUNT = 20;

    /**
     * Начало тестирования
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        return response()->json();
    }

    /**
     * Создание теста для сотрудника с json ответом
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        $response = $this->createTesting($request);

        return response()->json($response);
    }

    /**
     * Создание теста для сотрудника
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function createTesting(Request $request)
    {
        foreach ($this->generateNumbers() as $offset) {
            $questions[] = TestingQuestion::select('id')->offset($offset)->limit(1)->first()->id;
        }

        return TestingProcess::create([
            'uuid' => Str::orderedUuid(),
            'pin' => $request->pin,
            'questions_id' => $questions ?? [],
        ]);
    }

    /**
     * Формирование порядковых номеров для вопросов
     * 
     * @return array
     */
    public function generateNumbers()
    {
        $questions = TestingQuestion::count();

        $limit = $questions > self::QUESTIONS_COUNT ? self::QUESTIONS_COUNT : $questions;
        $numbers = [];

        while (count($numbers) < $limit) {

            $number = rand(0, $questions - 1);

            if (!in_array($number, $numbers))
                $numbers[] = $number;
        }

        return $numbers;
    }
}
