<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Factories\Sequence;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $factory = Permission::factory();

        $state = collect($factory->permissions)
            ->map(function ($row) {
                return [
                    'permission' => $row[0],
                    'comment' => $row[1]
                ];
            })
            ->toArray();

        $factory->count(count($state))->state(new Sequence(...$state))->create();
    }
}
