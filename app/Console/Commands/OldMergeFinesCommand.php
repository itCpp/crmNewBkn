<?php

namespace App\Console\Commands;

use App\Models\CrmMka\Fine as CrmMkaFine;
use App\Models\Fine;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class OldMergeFinesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'old:fines';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Перенос штрафов из старой ЦРМ';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->question("   Перенос штрафов   ");

        $added = 0;
        $deleted = 0;

        CrmMkaFine::all()
            ->each(function ($row) use (&$added, &$deleted) {

                if ($row->comment) {
                    $row->comment = Str::replace(["<b>", "</b>"], "", $row->comment);
                }

                $fine = new Fine;

                $fine->user_pin = $row->pin;
                $fine->from_pin = (int) $row->pin_add ?: null;
                $fine->fine = $row->fine;
                $fine->comment = $row->comment;
                $fine->request_id = $row->id_request;
                $fine->is_autofine = (bool) $row->auto_fine;
                $fine->fine_date = $row->fine_date;
                $fine->created_at = $row->created_at;
                $fine->updated_at = $row->updated_at;
                $fine->deleted_at = $row->deleted_at;

                $fine->save();

                if ($fine->deleted_at) {
                    $deleted++;
                } else {
                    $added++;

                    $notification = "Вам назначен штраф в размере {$row->fine} руб";

                    if ($fine->comment) {
                        $notification .= ", по причине: " . $fine->comment;
                    }

                    Notification::create([
                        'user' => $fine->user_pin,
                        'notif_type' => "fine",
                        'notification' => $notification,
                        'data' => $fine->toArray(),
                        'created_at' => $fine->created_at,
                        'readed_at' => $fine->updated_at,
                        'user_by_id' => $this->getUserId($fine->from_pin),
                    ]);
                }
            });

        $count = $added + $deleted;

        $this->line("Перенесено штрафов: <options=bold>{$count}</>, из них");
        $this->line("Действующих штрафов: <fg=green;options=bold>{$added}</>");
        $this->line("Удаленных штрафов: <fg=red;options=bold>{$deleted}</> (для полноты картины)");

        return 0;
    }

    /**
     * Поиск сотрудника
     * 
     * @param  null|int $pin
     * @return null|int
     */
    public function getUserId($pin)
    {
        if (!empty($this->users[$pin]))
            return $this->users[$pin];

        if (!$pin)
            return $this->users[$pin] = null;

        $user = User::wherePin($pin)->first();

        return $this->users[$pin] = $user->id ?? null;
    }
}
