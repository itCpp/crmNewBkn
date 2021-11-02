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
    public function title($string)
    {
        $length = Str::length(strip_tags($string)) + 12;

        $this->newLine();

        $this->line('<bg=green;options=bold>' . str_repeat(' ', $length) . '</>');
        $this->line('<bg=green;options=bold>      '.$string.'      </>');
        $this->line('<bg=green;options=bold>' . str_repeat(' ', $length) . '</>');

        $this->newLine();
    }
}