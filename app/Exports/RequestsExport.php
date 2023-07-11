<?php

namespace App\Exports;

use App\Console\Commands\RequestsExport as CommandsRequestsExport;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class RequestsExport implements FromView, WithColumnWidths
{
    /**
     * Данные по заявкам
     * 
     * @var \Illuminate\Database\Eloquent\Collection
     */
    public $data;

    /**
     * Инициализация объекта
     * 
     * @param  string  $start
     * @param  string  $stop
     * @param  string|null  $city
     * @param  string|null  $theme
     * @return void
     */
    public function __construct($start, $stop, $city = null, $theme = null)
    {
        $this->data = CommandsRequestsExport::exportData($start, $stop, $city, $theme);
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function view(): View
    {
        return view('requests.export-table', [
            'header' => collect(collect($this->data)->first())->keys()->toArray(),
            'items' => $this->data,
        ]);
    }

    /**
     * Ширина колонок
     * 
     * @return array
     */
    public function columnWidths(): array
    {
        return [
            'B' => 15,
            'C' => 32,
            'D' => 15,
            'E' => 15,
            'G' => 35,
            'H' => 12,
            'I' => 28,
            'J' => 28,
        ];
    }
}
