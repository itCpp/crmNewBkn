<?php

namespace App\Console\Commands;

use App\Http\Controllers\Admin\Blocks\OwnStatistics;
use App\Models\BlockHost as ModelsBlockHost;
use App\Models\BlockIp;
use App\Models\Company\BlockHost;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BlockMigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blocks:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Переносит все блокировки ip с одной базы на индивидуальные каждого сайта';

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
        $this->own = new OwnStatistics(new Request());

        BlockHost::lazy()
            ->each(function ($row) {
                $this->setBlock($row);
            });

        return 0;
    }

    /**
     * Обработка блокировки
     * 
     * @param \App\Models\Company\BlockHost $row
     * @return null
     */
    public function setBlock(BlockHost $row)
    {
        if ($row->is_hostname == 1)
            return $this->setBlockHostName($row);

        $row_block = BlockIp::firstOrNew(['ip' => $row->host]);
        $row_block->hostname = gethostbyaddr($row->host);

        if ($row->block == 1)
            $row_block->sites = $this->setSitesBlock($row_block->ip, true);

        $row_block->save();

        return null;
    }

    /**
     * Блокировка имени хоста
     * 
     * @param \App\Models\Company\BlockHost $row
     * @return null
     */
    public function setBlockHostName(BlockHost $row)
    {
        $row_block = ModelsBlockHost::firstOrNew(['host' => $row->host]);

        if ($row_block->block == 1)
            $row_block->sites = $this->setSitesBlock($row_block->host, true, true);

        $row_block->save();

        return null;
    }

    /**
     * Примение блокировки к сайту
     * 
     * @param string $addr
     * @param bool $is_block
     * @param bool $is_hostname
     * @return array
     */
    public function setSitesBlock($addr, $is_block = true, $is_hostname = false)
    {
        $date = date("Y-m-d H:i:s");

        foreach ($this->own->connections() as $connection) {

            try {
                $table = DB::connection($connection)->table('blocks');

                $block = $table->where('host', $addr)
                    ->where('is_hostname', (int) $is_hostname)
                    ->first();

                if (!$block) {
                    $id = $table->insertGetId([
                        'host' => $addr,
                        'is_hostname' => (int) $is_hostname,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);
                } else {
                    $id = $block->id;
                }

                $table->where('id', $id)
                    ->limit(1)
                    ->update([
                        'is_block' => (int) $is_block,
                        'updated_at' => $date,
                    ]);

                $id = config("database.connections.{$connection}.connection_id");

                $sites["id-" . $id] = (bool) $is_block;
            } catch (Exception $e) {
                // ...
            }
        }

        return $sites ?? [];
    }
}
