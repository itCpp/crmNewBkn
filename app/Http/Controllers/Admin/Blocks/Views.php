<?php

namespace App\Http\Controllers\Admin\Blocks;

use App\Http\Controllers\Admin\Databases;
use App\Http\Controllers\Controller;
use App\Models\Company\AllVisit;
use Exception;
use Jenssegers\Agent\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            ->when((bool) $this->request->ip, function ($query) {
                $query->where('ip', $this->request->ip);
            })
            ->when((bool) $this->request->site, function ($query) {
                $query->where('site', $this->request->site);
            })
            ->orderBy('id', "DESC")
            ->paginate(50);

        foreach ($data as $row) {

            $row->link = "http://" . $row->site;

            if ($row->page)
                $row->link .= $row->page;

            $this->agent->setUserAgent($row->user_agent);

            $row->robot = $this->agent->isRobot();
            $row->platform = $this->agent->platform();
            $row->desktop = $this->agent->isDesktop();
            $row->phone = $this->agent->isPhone();

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
        return (new static($request))->getDataOwnSites();
    }

    /**
     * Статистика по сайтам 
     * 
     * @return array
     */
    public function getDataOwnSites()
    {
        $this->databases = new Databases;
        $sites = $this->databases->sites(true);
        $site = (int) $this->request->site;

        if (!(bool) $this->request->site and count($sites)) {
            $site = $sites[0]['value'];
        }

        $this->connection = $this->databases->setConfigs($site ?? null)[0] ?? null;
        $this->own_ips = $this->envExplode('OUR_IP_ADDRESSES_LIST');

        $this->getCounterData();

        try {
            $data = DB::connection($this->connection)
                ->table('visits')
                ->where('created_at', '<', $this->start)
                ->when((bool) $this->request->allToDay, function ($query) {
                    $query->where('created_at', '>=', now()->startOfDay());
                })
                ->when((bool) $this->request->ip, function ($query) {
                    $query->where('ip', $this->request->ip);
                })
                ->when(!(bool) $this->request->ip, function ($query) {
                    $query->whereNotIn('ip', $this->own_ips);
                })
                ->orderBy('id', 'DESC')
                ->paginate(60);

            foreach ($data as $row) {
                $this->rows[] = $this->serializeRow($row);
            }

            $this->total = $data->total();
            $this->pages = $data->lastPage();
            $this->next = $data->currentPage() + 1;
        } catch (Exception) {
        }

        return [
            'date' => ($this->request->page == 1 || !$this->request->page) ? $this->start : null,
            'sites' => $sites,
            'site' => $site ?? null,
            'rows' => $this->rows ?? [],
            'total' => $this->total ?? 0,
            'pages' => $this->pages ?? 0,
            'nextPage' => $this->next ?? 0,
        ];
    }

    /**
     * Подсчет общего количества строк
     * 
     * @return $this;
     */
    public function getCounterData()
    {
        try {
            $this->total += DB::connection($this->connection)
                ->table('visits')
                ->where('created_at', '<', $this->start)
                ->when((bool) $this->request->allToDay, function ($query) {
                    $query->where('created_at', '>=', now()->startOfDay());
                })
                ->when((bool) $this->request->ip, function ($query) {
                    $query->where('ip', $this->request->ip);
                })
                ->when(!((bool) $this->request->ip and (bool) ($this->own_ips ?? false)), function ($query) {
                    $query->whereNotIn('ip', $this->own_ips);
                })
                ->count();
        } catch (Exception) {
        }

        return $this;
    }

    /**
     * @param object $row
     * @return array
     */
    public function serializeRow($row)
    {
        $row->link = "";

        $data = json_decode($row->request_data, true);
        $row->request_data = $data ?: $row->request_data;

        if ($data['headers']['Host'] ?? null)
            $site = $data['headers']['Host'];
        else if ($data['headers']['host'] ?? null)
            $site = $data['headers']['host'];

        if ($site ?? null) {
            $row->site = idn_to_utf8($site);
            $row->link = "http://" . $row->site;
        }

        if ($row->page)
            $row->link .= $row->page;

        $this->agent->setUserAgent($row->user_agent);

        $row->robot = $this->agent->isRobot();
        $row->platform = $this->agent->platform();
        $row->desktop = $this->agent->isDesktop();
        $row->phone = $this->agent->isPhone();

        $row->sort = strtotime($row->created_at);

        return $row;
    }
}
