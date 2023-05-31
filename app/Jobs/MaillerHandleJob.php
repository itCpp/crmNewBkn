<?php

namespace App\Jobs;

use App\Http\Controllers\Controller;
use App\Models\Mailler;
use App\Models\MaillerLog;
use App\Models\RequestsClient;
use App\Models\RequestsRow;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MaillerHandleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var \App\Models\MaillerLog
     */
    protected $maillerLog;

    /**
     * Результат работы
     * 
     * @var array
     */
    protected $response = [];

    /**
     * Переменные для подмены в тексте
     * 
     * @var array
     */
    protected $variables = [];

    /**
     * Create a new job instance.
     *
     * @param  \App\Models\Mailler  $mailler
     * @param  \App\Models\RequestsRow  $requestsRow
     * @param  bool  $isDirty
     * @param  array  $original
     * @param  array  $changes
     * @return void
     */
    public function __construct(
        protected Mailler $mailler,
        protected RequestsRow $requestsRow,
        protected bool $isDirty,
        protected array $original,
        protected array $changes,
    ) {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->maillerLog = MaillerLog::create([
            'mailler_id' => $this->mailler->id,
            'request_data' => [
                'request' => $this->requestsRow->toArray(),
                'mailler' => $this->mailler->toArray(),
            ],
            'start_at' => now(),
        ]);

        if (!$this->isSend()) {

            $this->response['is_send'] = false;
            $this->response['message'] = "Сообщение не отправлено по условиям проверки";

            $this->maillerLog->update([
                'response_data' => $this->response,
            ]);

            return;
        }

        /** Обход клиентов и отправка заявки по каждому номеру телефона */
        collect($this->requestsRow->clients ?? null)
            ->each(function ($client) {
                $this->sendMail($client);
            });

        $is_failed = collect($this->response['is_sent'] ?? [])
            ->filter(fn ($item) => $item == true)
            ->isEmpty();

        $this->maillerLog->update([
            'response_data' => $this->response,
            'sent_at' => !$is_failed ? now() : null,
            'is_send' => !$is_failed,
            'failed_at' => $is_failed ? now() : null,
            'is_failed' => $is_failed,
        ]);
    }

    /**
     * Отправка заявки на почту по клиенту
     * 
     * @param  \App\Models\RequestsClient  $client
     * @return void
     */
    public function sendMail(RequestsClient $client)
    {
        $mail = new \PHPMailer\PHPMailer\PHPMailer();

        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host = env('PHP_MAIL_HOST', "");
        $mail->Port = (int) env('PHP_MAIL_PORT', 465);
        $mail->Username = env('PHP_MAIL_USER', "");
        $mail->Password = env('PHP_MAIL_PASSWORD', "");
        $mail->SMTPAuth = true;
        $mail->setFrom(
            env('PHP_MAIL_USER', ""),
            $this->mailler->config['from_name'] ?? "crm24ka"
        );
        $mail->addAddress($this->mailler->destination);

        $phone = Controller::checkPhone(decrypt($client->phone, false));

        $variables = $this->variables([
            'phone' => Controller::checkPhone($phone, 2),
            'phone7x' => $phone,
        ]);

        $mail->Subject = empty($this->mailler->config['subject'])
            ? $this->changeVariablesInText($this->mailler->config['subject'] ?? "", $variables)
            : $phone;

        $message = $this->changeVariablesInText($this->mailler->config['message'] ?? "", $variables);

        $mail->msgHTML($message);
        $this->response['is_sent'][$client->id] = $mail->send();
    }

    /**
     * Определяет необходимость отправки
     * 
     * @return bool
     */
    protected function isSend()
    {
        return ($this->response['check']['change_pin'] = $this->checkToChangePin())
            || ($this->response['check']['change_status'] = $this->checkToChangeStatus());
    }

    /**
     * Проверка отправки по смене PIN'a оператора
     * 
     * @return bool
     */
    protected function checkToChangePin()
    {
        if (empty($this->mailler->config['change_pin'])) {
            return false;
        }

        return $this->isDirty
            && !empty($this->changes['pin'])
            && in_array(
                $this->changes['pin'] ?? null,
                $this->mailler->config['pins'] ?? [],
            );
    }

    /**
     * Проверка необходимости отправки по смене статуса
     * 
     * @return bool
     */
    protected function checkToChangeStatus()
    {
        if (empty($this->mailler->config['change_status']))
            return false;

        $status_from = $this->changes['status_id'] ?? null;
        $status_to = $this->changes['status_id'] ?? null;

        if (
            !empty($this->mailler->config['status_from'])
            && ($this->mailler->config['status_from'] ?? null) != $status_from
        ) {
            return false;
        }

        if (
            !empty($this->mailler->config['status_to'])
            && ($this->mailler->config['status_to'] ?? null) != $status_to
        ) {
            return false;
        }

        return $status_from != $status_to;
    }

    /**
     * Подготовка переменных
     * 
     * @param  array  $append
     * @return array
     */
    public function variables($append = [])
    {
        return array_merge([
            'comment' => $this->requestsRow->comment,
            'comment_first' => $this->requestsRow->comment_first,
            'pin' => $this->requestsRow->pin,
            'client_name' => $this->requestsRow->client_name,
            'theme' => $this->requestsRow->theme,
            'region' => $this->requestsRow->region,
            'event_at' => $this->requestsRow->event_at instanceof Carbon
                ? $this->requestsRow->event_at->format("d.m.Y H:i")
                : $this->requestsRow->event_at,
        ], $append);
    }

    /**
     * Заменяет переменные в тексте
     * `${variable}
     * 
     * @param  string  $text
     * @param  array  $data
     * @return string
     */
    public static function changeVariablesInText($text, $data, $phone_modify = false)
    {
        preg_match_all('/\${(.*?)\}/', $text, $matches);

        foreach ($matches[1] as $match) {

            $value = $data[$match] ?? "<{$match}>";

            if ($phone_modify and $match == "phone")
                $value = Controller::phoneModify($value) ?: $value;

            $text = str_replace('${' . $match . '}', $value, $text);
        }

        return $text;
    }
}
