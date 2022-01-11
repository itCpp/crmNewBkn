<?php

namespace App\Http\Controllers\Testing;

use Exception;
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
     * Создание экземпляра объекта
     * 
     * @param \Illuminate\Http\Request $request
     * @return void
     * 
     * @throws Exception
     */
    public function __construct(Request $request)
    {
        if (!$this->process = TestingProcess::where('uuid', $request->uuid)->first())
            throw new Exception("Тестирование не найдено");
    }

    /**
     * Загрузка данных тестирования
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        return response()->json([
            'process' => $this->process,
            'question' => $this->process->start_at ? $this->findQuestion() : null,
        ]);
    }

    /**
     * Начало тестирования
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function start(Request $request)
    {
        if ($this->process->done_at)
            return response()->json(['message' => "Этот тест уже завершен"], 400);

        $this->process->start_at = $this->process->start_at ?: now();

        $process = $this->process->answer_process;

        $process['starting'][] = [
            'time' => now(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        $this->process->answer_process = $process;

        $this->process->save();

        return response()->json([
            'process' => $this->process,
            'question' => $this->findQuestion(),
        ]);
    }

    /**
     * Поиск вопроса для вывода
     * 
     * @return array
     */
    public function findQuestion()
    {
        $process = $this->process->answer_process;

        if (empty($process['question']))
            $process['question'] = $this->process->questions_id[0] ?? null;

        $this->process->answer_process = $process;
        $this->process->save();

        return TestingQuestion::find($process['question'])->toArray();
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
