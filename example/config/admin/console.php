<?php

declare(strict_types=1);

use Alight\Admin\Console;

Console::chart(Console::CHART_HEATMAP)->config([
    'height' => 200,
    'yField' => 'date',
    'xField' => 'time',
    'shape' => 'circle',
    'colorField' => 'color',
    'sizeField' => 'show',
    'sizeRatio' => 0.5,
    'reflect' => 'y',
    'xAxis' => [
        'position' => 'top',
        'grid' => null,
    ],
    'tooltip' => [
        'title' => 'title',
        'domStyles' => [
            'g2-tooltip-list' => ['display' => 'none']
        ]
    ],
])->api('console')->grid(['xs' => 24, 'sm' => 12, 'md' => 16, 'lg' => 18, 'xxl' => 20]);
