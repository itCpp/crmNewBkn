<?php

namespace App\Http\Controllers\Testing;

use Exception;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Users\Users;
use App\Models\TestingProcess;
use App\Models\TestingQuestion;
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
     * Экземпляр моедели процесса тестирования
     * 
     * @var TestingProcess|null
     */
    public $process = null;

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
        if ($request->uuid and !$this->process = TestingProcess::where('uuid', $request->uuid)->first())
            throw new Exception("Тестирование не найдено");

        if ($this->process)
            $this->getUserName();
    }

    /**
     * Загрузка данных тестирования
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        $question = ($this->process->start_at and !$this->process->done_at)
            ? $this->findQuestion()
            : null;

        return response()->json([
            'process' => $this->process,
            'question' => $question,
        ]);
    }

    /**
     * Поиск имени сотрудника
     * 
     * @return string|null
     */
    public function getUserName()
    {
        if ($this->process->pin)
            $this->process->name = Users::findUserPin($this->process->pin)->name_full ?? null;
        else if ($this->process->pin_old)
            $this->process->name = Users::findUserOldPin($this->process->pin_old)->fullName ?? null;

        return $this->process->name ?? null;
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

        $question = $this->findQuestion();

        $this->process->save();

        return response()->json([
            'process' => $this->process,
            'question' => $question,
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

        $id = $process['question'];

        if (empty($process['questions'][$id]))
            $process['questions'][$id] = $this->createQuestion($id);

        $this->process->answer_process = $process;
        $this->process->save();

        return $process['questions'][$id];
    }

    /**
     * Создание вопроса для вывода на страницу
     * 
     * @param int $id
     * @return array
     */
    public function createQuestion($id)
    {
        $question = TestingQuestion::find($id);

        $question->answers = collect($question->answers)->shuffle();
        $question->answers_rights = $this->encrypt(json_encode($question->right_answers));

        return $question->toArray();
    }

    /**
     * Следующий вопрос
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function next(Request $request)
    {
        $process = $this->process->answer_process;

        if (empty($process['questions'][$request->question]))
            return response()->json(['message' => "Вопрос не найден"], 400);

        // Запись ответов на текущий вопрос
        $process['questions'][$request->question]['answers_selected'] = $request->answers;
        $process['questions'][$request->question]['answer_data'] = [
            'time' => now(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        $id = $this->findNextQuestionId($request->question);
        $process['question'] = $id;

        if (!$id)
            $process = $this->serialiseAnswers($process);

        $this->process->answer_process = $process;
        $this->process->done_at = $id ? null : now();

        $question = $id ? $this->findQuestion() : null;

        $this->process->save();

        return response()->json([
            'process' => $this->process,
            'question' => $question ?? null,
        ]);
    }

    /**
     * Поиск идентификатор следующего вопроса
     * 
     * @param int $question_id Идентификатор текущего вопроса
     * @return false|int
     */
    public function findNextQuestionId($question_id)
    {
        $next = false; // Флаг остановки

        foreach ($this->process->questions_id as $id) {

            if ($next) {
                $next = $id;
                break;
            }

            if ($id == $question_id)
                $next = true;
        }

        if (gettype($next) == "integer")
            return $next;

        return false;
    }

    /**
     * Обработка данных при завершении
     * 
     * @return null
     */
    public function serialiseAnswers($process)
    {
        $correct = 0;
        $incorrect = 0;

        foreach ($process['questions'] ?? [] as $key => $question) {

            $answers_rights = $this->decrypt($question['answers_rights']);
            $answers_rights = json_decode($answers_rights, true);

            $diff = array_diff($question['answers_selected'], $answers_rights);

            $process['questions'][$key]['bad'] = count($diff) > 0;
            $process['questions'][$key]['diff'] = $diff;

            if ($process['questions'][$key]['bad']) {
                $incorrect++;
            } else {
                $correct++;
            }
        }

        $process['correct'] = $correct;
        $process['incorrect'] = $incorrect;

        return $process;
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
