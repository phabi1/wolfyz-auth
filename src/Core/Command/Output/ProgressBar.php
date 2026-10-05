<?php

namespace App\Core\Command\Output;

class ProgressBar
{
    private $total;
    private $current = 0;

    private $barLength = 50;

    public function __construct(int $total, ?int $barLength = 50)
    {
        $this->total = $total;
        if ($barLength !== null) {
            $this->barLength = $barLength;
        }
    }

    public function advance(int $step = 1): void
    {
        $this->current += $step;
        $this->display();
    }

    private function display(): void
    {
        $percentage = ($this->current / $this->total) * 100;
        $filledLength = (int)($this->barLength * $this->current / $this->total);
        $bar = str_repeat('=', $filledLength) . str_repeat(' ', $this->barLength - $filledLength);
        fwrite(STDOUT, "\r[$bar] $percentage%");
        if ($this->current >= $this->total) {
            fwrite(STDOUT, PHP_EOL);
        }
    }
}