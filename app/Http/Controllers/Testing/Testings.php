<?php

namespace App\Http\Controllers\Testing;

use App\Exceptions\ExceptionsJsonResponse;
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
     * @throws ExceptionsJsonResponse
     */
    public function __construct(Request $request)
    {
        if ($request->uuid and !$this->process = TestingProcess::where('uuid', $request->uuid)->first())
            throw new ExceptionsJsonResponse("Тестирование не найдено");

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
            'process' => $this->processToArray(),
            'question' => $question,
        ]);
    }

    /**
     * Преобразование данных процесса для вывода на страницу
     * 
     * @return array
     */
    public function processToArray()
    {
        return array_merge(
            ['name' => $this->name ?? null],
            $this->process->toArray(),
        );
    }

    /**
     * Поиск имени сотрудника
     * 
     * @return string|null
     */
    public function getUserName()
    {
        if ($this->process->pin)
            $this->name = Users::findUserPin($this->process->pin)->name_full ?? null;
        else if ($this->process->pin_old)
            $this->name = Users::findUserOldPin($this->process->pin_old)->fullName ?? null;

        return $this->name ?? null;
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
            'process' => $this->processToArray(),
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
            'process' => $this->processToArray(),
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
        $request = new Request(
            query: $request->all(),
            server: [
                'HTTP_USER_AGENT' => $request->userAgent(),
                'REMOTE_ADDR' => $request->header('x-forwarded-for') ?: $request->ip(),
            ]
        );

        $request->general = $request->general ? (int) $request->general : 0;
        $request->thematic = $request->thematic ? (int) $request->thematic : 0;

        $this->process = $this->createTesting($request);

        $this->getUserName();

        return response()->json($this->processToArray());
    }

    /**
     * Создание теста для сотрудника
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     * 
     * @throws \App\Exceptions\ExceptionsJsonResponse
     */
    public function createTesting(Request $request)
    {
        if ($request->thematic and !$request->themes)
            throw new ExceptionsJsonResponse("Необходимо выбрать тему дополнительных вопросов, либо изменить их количетсво на 0");

        if (!$request->general and !$request->thematic)
            $request->general = self::QUESTIONS_COUNT;

        $themes = [null];

        if (is_array($request->themes))
            $themes = [null, $request->themes];

        foreach ($themes as $theme) {
            foreach ($this->generateNumbers($request, $theme) as $offset) {
                $questions[] = TestingQuestion::select('id')->offset($offset)->limit(1)->first()->id;
            }
        }

        $test = new TestingProcess;
        $test->uuid = Str::orderedUuid();
        $test->pin = $request->pin;
        $test->pin_old = $request->pin_old;
        $test->questions_id = $questions ?? [];

        $test->save();

        return $test;
    }

    /**
     * Формирование порядковых номеров для вопросов
     * 
     * @param \Illuminate\Http\Request $request
     * @param null|string $theme
     * @return array
     * 
     * @throws \App\Exceptions\ExceptionsJsonResponse
     */
    public function generateNumbers(Request $request, $theme)
    {
        if (($theme !== null and !$request->thematic) or ($theme === null and !$request->general))
            return [];

        $questions = TestingQuestion::when(is_array($theme), function ($query) use ($theme) {
            $query->whereIn('theme', $theme);
        })
            ->when($theme === null, function ($query) {
                $query->where('theme', null);
            })
            ->count();

        $count = $theme === null ? $request->general : $request->thematic;

        $limit = $questions > $count ? $count : $questions;
        $numbers = [];

        while (count($numbers) < $limit) {

            $number = rand(0, $questions - 1);

            if (!in_array($number, $numbers))
                $numbers[] = $number;
        }

        return $numbers;
    }
}
