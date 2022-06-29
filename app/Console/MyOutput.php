<?php

namespace App\Console;

use Illuminate\Support\Str;

trait MyOutput
{
    /**
     * Write a string in an title box.
     *
     * @param  string  $string
     * @return void
     */
    public function title($string, $color = "green")
    {
        $length = Str::length(strip_tags($string)) + 12;

        $this->newLine();

        $this->line('<fg=' . $color . ';options=bold>' . str_repeat('*', $length) . '</>');
        $this->line('<fg=' . $color . ';options=bold>*     '.$string.'     *</>');
        $this->line('<fg=' . $color . ';options=bold>' . str_repeat('*', $length) . '</>');

        $this->newLine();
    }
}