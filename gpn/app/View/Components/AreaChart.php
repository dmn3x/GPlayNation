<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AreaChart extends Component
{
    public string $line;

    public string $area;

    public float $max;

    /** @param array<int, array{label: string, value: int|float}> $points */
    public function __construct(public array $points, public int $height = 180)
    {
        $w = 720;
        $h = $this->height;
        $this->max = max(1, (float) max(array_map(fn ($p) => $p['value'], $this->points) ?: [0]));
        $n = max(1, count($this->points) - 1);
        $coords = [];
        foreach (array_values($this->points) as $i => $p) {
            $coords[] = [round($i * $w / $n, 1), round($h - 8 - ($p['value'] / $this->max) * ($h - 24), 1)];
        }
        $this->line = implode(' ', array_map(fn ($c) => "$c[0],$c[1]", $coords));
        $this->area = "0,$h ".$this->line." $w,$h";
    }

    public function render(): View|Closure
    {
        return view('components.area-chart');
    }
}
