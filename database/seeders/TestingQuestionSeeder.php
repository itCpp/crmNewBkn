<?php

namespace Database\Seeders;

use App\Models\TestingQuestion;
use Illuminate\Database\Seeder;
// use Illuminate\Database\Eloquent\Factories\Sequence;

class TestingQuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // $factory = TestingQuestion::factory();
        // $state = $factory->questions;
        // $count = count($state);

        // $factory->count($count)->state(new Sequence(...$state))->create();

        foreach (TestingQuestion::factory()->questions as $row)
            TestingQuestion::create($row);
    }
}
