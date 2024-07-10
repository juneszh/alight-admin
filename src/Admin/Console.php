<?php

declare(strict_types=1);

/*
 * This file is part of the Alight package.
 *
 * (c) June So <june@alight.cc>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alight\Admin;

class Console
{
    public static array $config = [];
    private static int $index = 0;

    public const
        CHART_AREA = 'Area',
        CHART_BAR = 'Bar',
        CHART_BASE = 'Base',
        CHART_BIDIRECTIONAL_BAR = 'BidirectionalBar',
        CHART_BOX = 'Box',
        CHART_BULLET = 'Bullet',
        CHART_CIRCLE_PACKING = 'CirclePacking',
        CHART_COLUMN = 'Column',
        CHART_DUAL_AXES = 'DualAxes',
        CHART_FUNNEL = 'Funnel',
        CHART_GAUGE = 'Gauge',
        CHART_HEATMAP = 'Heatmap',
        CHART_HISTOGRAM = 'Histogram',
        CHART_LINE = 'Line',
        CHART_LIQUID = 'Liquid',
        CHART_MIX = 'Mix',
        CHART_PIE = 'Pie',
        CHART_RADAR = 'Radar',
        CHART_RADIAL_BAR = 'RadialBar',
        CHART_ROSE = 'Rose',
        CHART_SANKEY = 'Sankey',
        CHART_SCATTER = 'Scatter',
        CHART_STOCK = 'Stock',
        CHART_SUNBURST = 'Sunburst',
        CHART_TINY_AREA = 'TinyArea',
        CHART_TINY_COLUMN = 'TinyColumn',
        CHART_TINY_LINE = 'TinyLine',
        CHART_TINY_PROGRESS = 'TinyProgress',
        CHART_TINY_RING = 'TinyRing',
        CHART_TREEMAP = 'Treemap',
        CHART_VENN = 'Venn',
        CHART_VIOLIN = 'Violin',
        CHART_WATERFALL = 'Waterfall',
        CHART_WORD_CLOUD = 'WordCloud';

    /**
     * Create a chart
     *
     * @param string $component Console::CHART_*
     * @return ConsoleChart
     * 
     * @see https://charts.ant.design/en/docs/api
     */
    public static function chart(string $component): ConsoleChart
    {
        ++self::$index;
        
        return new ConsoleChart(self::$index, $component);
    }
}
