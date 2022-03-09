<?php

namespace App\Http\Controllers\Admin\BlocksDrive;

use App\Models\BlockHost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BlockHosts extends Drive
{
    /**
     * Список заблокированных адресов
     * 
     * @var array
     */
    protected $rows = [];

    /**
     * Вывод заблокированных IP
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        return response()->json(array_merge([
            'rows' => $this->getBlocks($request),
        ], $this->paginate ?? []));
    }

    /**
     * Вывод списка заблокированных хостов
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function getBlocks(Request $request)
    {
        $paginate = BlockHost::orderBy('id', 'DESC')
            ->when((bool) $request->search, function ($query) use ($request) {
                $query->where('host', 'LIKE', "%{$request->search}%");
            })
            ->paginate(40);

        $this->paginate = [
            'total' => $paginate->total(),
            'page' => $paginate->currentPage(),
            'pages' => $paginate->lastPage(),
        ];

        $this->hosts = $paginate->map(function ($row) {
            $this->pushHostRow($row->host);
            return $row->host;
        })->toArray();

        foreach ($this->databases as $database) {
            $this->getBlock($database);
        }

        return collect($this->rows)
            ->map(function ($row) {

                $blocked = 0;

                foreach ($this->databases as $database) {

                    $is_block = $row['blocks'][$database['id']] ?? false;

                    if ($is_block)
                        $blocked++;

                    $blocks[] = [
                        'site' => $database['domain'] ?: "Сайт #" . $database['id'],
                        'block' => $is_block,
                        'id' => $database['id'],
                    ];
                }

                if (count($this->connections) == $blocked)
                    $row['blocks_all'] = true;

                $row['blocks'] = $blocks ?? [];

                return $row;
            })
            ->values()
            ->all();
    }

    /**
     * Поиск заблокированных адресов в базе сайта
     * 
     * @param string $database
     */
    public function getBlock($database)
    {
        DB::connection($database['connection'] ?? false)
            ->table('blocks')
            ->whereIn('host', $this->hosts ?? [])
            ->where('is_hostname', 1)
            ->get()
            ->each(function ($row) use ($database) {
                $this->setRow($row, $database);
            });
    }

    /**
     * Создание строки c IP сдресом
     * 
     * @param string $host
     * @return array
     */
    public function pushHostRow($host)
    {
        $key = md5($host);

        if (empty($this->rows[$key])) {
            $this->rows[$key] = [
                'host' => $host,
                'blocks' => [],
                'is_blocked' => false,
                'blocks_all' => false,
            ];
        }

        return $this->rows[$key];
    }

    /**
     * Примение строки с адрсеом
     * 
     * @param object $row
     * @param array $database
     * @return array
     */
    public function setRow($row, $database)
    {
        $this->pushHostRow($row->host);

        $host = md5($row->host);

        if ($row->is_block == 1)
            $this->rows[$host]['is_blocked'] = true;

        $this->rows[$host]['blocks'][$database['id']] = $row->is_block == 1;

        return $this->rows[$host];
    }
}
