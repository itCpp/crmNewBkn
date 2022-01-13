<?php

namespace Database\Seeders;

use App\Models\UsersPosition;
use Illuminate\Database\Seeder;

class UsersPositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (UsersPosition::factory()->positions as $row)
            UsersPosition::create(['name' => $row]);
    }
}
