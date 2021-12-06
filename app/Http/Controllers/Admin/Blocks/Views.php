<?php

namespace App\Http\Controllers\Admin\Blocks;

use App\Http\Controllers\Controller;
use App\Models\Company\AllVisit;
use Jenssegers\Agent\Agent;
use Illuminate\Http\Request;

class Views extends Controller
{
    /**
     * Создание экземпляра объекта
     * 
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function __construct(
        protected Request $request
    ) {
        $this->start = $request->start ?: now();

        $this->agent = new Agent();
    }

    /**
     * Вывод данных о просмотрах
     * 
     * @return array
     */
    public function getData()
    {
        $data = AllVisit::where('created_at', '<', $this->start)
            ->orderBy('id', "DESC")
            ->paginate(50);

        foreach ($data as $row) {
            
            $row->link = "http://" . $row->site;

            if ($row->page)
                $row->link .= $row->page;
            
            $this->agent->setUserAgent($row->user_agent);

            $row->robot = $this->agent->isRobot();
            $row->platform = $this->agent->platform();

            $rows[] = $row->toArray();
        }

        return [
            'rows' => $rows ?? [],
            'nextPage' => $data->currentPage() + 1,
            'pages' => $data->lastPage(),
            'date' => ($this->request->page == 1 || !$this->request->page) ? $this->start : null,
        ];
    }

    /**
     * Вывод данных о просмотрах
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    static function get(Request $request)
    {
        return (new static($request))->getData();
    }
}
