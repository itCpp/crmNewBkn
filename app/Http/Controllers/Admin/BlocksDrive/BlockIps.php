<?php

namespace App\Http\Controllers\Admin\BlocksDrive;

use App\Models\BlockIp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BlockIps extends Drive
{
    /**
     * Список заблокированных адресов
     * 
     * @var array
     */
    protected $rows = [];

    /**
     * Список ip адресов
     * 
     * @var array
     */
    protected $ips = [];

    /**
     * Вывод заблокированных Хостов
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        return response()->json([
            'rows' => $this->getBlocks($request),
        ]);
    }

    /**
     * Поиск заблокированных адресов во всех базах сайта
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function getBlocks(Request $request)
    {
        $this->ips = BlockIp::orderBy('id', 'DESC')
            ->limit(40)
            ->get()
            ->map(function ($row) {
                return $row->ip;
            })
            ->toArray();

        foreach ($this->databases as $database) {
            $this->getBlock($database);
        }

        return collect($this->rows)
            ->map(function ($row) {

                if (count($this->connections) == count($row['blocks']))
                    $row['isAllBlock'] = true;

                foreach ($this->databases as $database) {
                    $blocks[] = [
                        'site' => $database['domain'] ?: "Сайт #" . $database['id'],
                        'block' => $row['blocks'][$database['id']] ?? false,
                        'id' => $database['id'],
                    ];
                }

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
            ->whereIn('host', $this->ips)
            ->where('is_hostname', 0)
            ->get()
            ->each(function ($row) use ($database) {
                $this->setRow($row, $database);
            });
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
        if (empty($this->rows[$row->host])) {
            $this->rows[$row->host] = [
                'ip' => $row->host,
                'blocks' => [],
                'isBlock' => false,
                'isAllBlock' => false,
            ];
        }

        if ($row->is_block == 1)
            $this->rows[$row->host]['isBlock'] = true;

        $this->rows[$row->host]['blocks'][$database['id']] = $row->is_block == 1;

        return $this->rows[$row->host];
    }
}
