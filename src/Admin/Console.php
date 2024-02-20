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

use Alight\App;
use Alight\Utility;
use Exception;
use ErrorException;
use Symfony\Component\Cache\Exception\InvalidArgumentException;
use InvalidArgumentException as GlobalInvalidArgumentException;
use PDOException;

class Console
{
    public static array $config = [];
    private static int $index = 0;

    public const
        CHART_AREA = 'Area',
        CHART_BAR = 'Bar',
        CHART_BIDIRECTIONAL_BAR = 'BidirectionalBar',
        CHART_BOX = 'Box',
        CHART_BULLET = 'Bullet',
        CHART_CHORD = 'Chord',
        CHART_CIRCLE_PACKING = 'CirclePacking',
        CHART_COLUMN = 'Column',
        CHART_DUAL_AXES = 'DualAxes',
        CHART_FACET = 'Facet',
        CHART_FUNNEL = 'Funnel',
        CHART_GAUGE = 'Gauge',
        CHART_HEATMAP = 'Heatmap',
        CHART_HISTOGRAM = 'Histogram',
        CHART_LINE = 'Line',
        CHART_LIQUID = 'Liquid',
        CHART_MIX = 'Mix',
        CHART_PIE = 'Pie',
        CHART_PROGRESS = 'Progress',
        CHART_RADAR = 'Radar',
        CHART_RADIAL_BAR = 'RadialBar',
        CHART_RING_PROGRESS = 'RingProgress',
        CHART_ROSE = 'Rose',
        CHART_SANKEY = 'Sankey',
        CHART_SCATTER = 'Scatter',
        CHART_STOCK = 'Stock',
        CHART_SUNBURST = 'Sunburst',
        CHART_TINY_AREA = 'TinyArea',
        CHART_TINY_COLUMN = 'TinyColumn',
        CHART_TINY_LINE = 'TinyLine',
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
        self::$config[self::$index] = [
            'component' => $component,
            'grid' => ['span' => 24],
        ];

        return new ConsoleChart(self::$index);
    }

    /**
     * Get full console configuration based on user role
     * 
     * @param int $userId 
     * @return array 
     * @throws Exception 
     * @throws ErrorException 
     * @throws InvalidArgumentException 
     * @throws InvalidArgumentException 
     * @throws GlobalInvalidArgumentException 
     * @throws PDOException 
     */
    public static function build(int $userId): array
    {
        $userInfo = Model::getUserInfo($userId);

        preg_match('/^(\d{5,11})@qq\.com$/', $userInfo['email'], $match);
        if (isset($match[1])) {
            $avatar = 'https://q.qlogo.cn/g?b=qq&nk=' . $match[1] . '&s=100';
        } else {
            $avatarDomain = Config::get('cravatar') ? 'cravatar.cn' : 'www.libravatar.org';
            $avatar = 'https://' . $avatarDomain . '/avatar/' . ($userInfo['email'] ? md5(strtolower(trim($userInfo['email']))) : '') . '?s=100&d=mp';
        }

        $roleEnum = Utility::arrayFilter(Model::getRoleList(), ['id' => $userInfo['role_id']]);
        $roleName = $roleEnum ? reset($roleEnum)['name'] : '';


        $consoleFile = App::root(Config::get('console'));
        if ($consoleFile && file_exists($consoleFile)) {
            require $consoleFile;
        }

        $chart = self::$config;
        if ($chart) {
            foreach ($chart as $k => $v) {
                if (!$v || (isset($v['role']) && $v['role'] && !in_array($userInfo['role_id'], $v['role']))) {
                    unset($chart[$k]);
                }
            }
        }

        return [
            'user' => [
                'id' => $userId,
                'avatar' => $avatar,
                'account' => $userInfo['account'],
                'name' => $userInfo['name'],
                'role' => $roleName,
            ],
            'chart' => $chart
        ];
    }
}
