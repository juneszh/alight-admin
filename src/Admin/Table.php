<?php

declare(strict_types=1);

/*
 * This file is part of the Alight package.
 *
 * (c) June So <alight@juneszh.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alight\Admin;

use Alight\Admin;
use Alight\Request;
use Alight\Response;
use ArrayObject;
use Exception;
use ErrorException;
use Symfony\Component\Cache\Exception\InvalidArgumentException;
use InvalidArgumentException as GlobalInvalidArgumentException;
use PDOException;

class Table
{
    public static array $config = [];
    private static int $buttonIndex = 0;
    private static int $statisticIndex = 0;

    /**
     * Create a colum
     * 
     * @param string $key 
     * @return TableColumn 
     */
    public static function column(string $key): TableColumn
    {
        if (!isset(self::$config[__FUNCTION__][$key])) {
            self::$config[__FUNCTION__][$key] = [
                'title' => $key,
                'database' => true,
            ];
        }
        return new TableColumn($key);
    }

    /**
     * Create a button
     * 
     * @param string $form 
     * @return TableButton 
     */
    public static function button(string $form): TableButton
    {
        ++self::$buttonIndex;
        $defaultUrl = '';
        if ($_SERVER['REQUEST_URI'] ?? '') {
            $defaultUrl = substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')) . '/form';
        }
        self::$config[__FUNCTION__][self::$buttonIndex] = [
            'form' => $form,
            'title' => $form,
            'url' => $defaultUrl ?: Admin::url(),
            'place' => '_action',
        ];
        return new TableButton(self::$buttonIndex);
    }

    /**
     * Create a statistic
     * 
     * @param string $key 
     * @return TableStatistic 
     */
    public static function statistic(string $key): TableStatistic
    {
        ++self::$statisticIndex;
        self::$config[__FUNCTION__][self::$statisticIndex] = [
            'key' => $key,
            'title' => ':sum',
            'type' => 'sum',
        ];

        return new TableStatistic(self::$statisticIndex);
    }

    /**
     * Table page render
     * 
     * @param @param string $table 
     * @param null|callable $middleware function($action, &$return){}
     * @throws Exception 
     * @throws ErrorException 
     * @throws InvalidArgumentException 
     * @throws InvalidArgumentException 
     * @throws GlobalInvalidArgumentException 
     * @throws PDOException 
     */
    public static function render(string $table, ?callable $middleware = null)
    {

        $page = (int) (Request::$data['current'] ?? 1);
        $limit = (int) (Request::$data['pageSize'] ?? 20);
        $limit = $limit < 100 ? $limit : 100;
        $order = trim(strip_tags(Request::$data['_order'] ?? '')) ?: 'id';
        $sort = trim(strip_tags(Request::$data['_sort'] ?? '')) ?: 'asc';

        $sortLimit = ['ascend' => 'asc', 'descend' => 'desc'];
        $sort = $sortLimit[$sort] ?? 'asc';

        $userId = Auth::getUserId();
        $userInfo = Model::getUserInfo($userId);

        if (!Request::isAjax()) {
            $column = new ArrayObject();
            if (isset(Table::$config['column'])) {
                foreach (Table::$config['column'] as $k => $v) {
                    if (isset($v['role']) && $v['role'] && !in_array($userInfo['role_id'], $v['role'])) {
                        continue;
                    }

                    if (isset($v['search']) && $v['search'] === 'text[~]') {
                        $v['search'] = 'text';
                    }

                    $column[$k] = $v;
                }
            }

            $toolbar = [];
            $batch = [];
            if (isset(Table::$config['button'])) {
                foreach (Table::$config['button'] as $v) {
                    if (isset($v['role']) && $v['role'] && !in_array($userInfo['role_id'], $v['role'])) {
                        continue;
                    }

                    if ($v['place'] == '_action') {
                        if (!isset($column['_action'])) {
                            $column['_action'] = [
                                'title' => ':action',
                                'database' => false,
                                'locale' => true,
                            ];
                        }
                        $column['_action']['button'][] = $v;
                    } elseif ($v['place'] == '_toolbar') {
                        $toolbar['button'][] = $v;
                    } elseif ($v['place'] == '_batch') {
                        $batch['button'][] = $v;
                    } elseif (isset($column[$v['place']])) {
                        $column[$v['place']]['button'][] = $v;
                    }
                }
            }

            $statistic = [];
            if (isset(Table::$config['statistic'])) {
                foreach (Table::$config['statistic'] as $v) {
                    if (isset($column[$v['key']])) {
                        $statistic[] = $v;
                    }
                }
            }

            $renderData = [
                'column' => $column,
                'toolbar' => $toolbar,
                'batch' => $batch,
                'statistic' => $statistic,
            ];

            if (is_callable($middleware)) {
                $middleware('render', $renderData);
            }

            Model::userLog($userId);

            Response::render('public/alight-admin/index.html', ['title' => Request::$data['_title'] ?? '', 'script' => Admin::globalScript('Table', $renderData)]);
        } else {
            $column = [];
            if (isset(Table::$config['column'])) {
                foreach (Table::$config['column'] as $k => $v) {
                    if (isset($v['role']) && $v['role'] && !in_array($userInfo['role_id'], $v['role'])) {
                        continue;
                    }

                    if (!$v['database']) {
                        continue;
                    }

                    $column[$k] = $v;
                }
            }

            $searchData = self::searchFilter($column, Request::$data);

            if (is_callable($middleware)) {
                $middleware('filter', $searchData);
            }

            $count = Model::tableCount($table, $searchData);
            $list = Model::tableSelect($table, $column, $searchData, $page, $limit, $order, $sort);

            $resData = [
                'count' => $count,
                'list' => $list,
            ];

            if (is_callable($middleware)) {
                $middleware('return', $resData);
            }

            Response::api(0, $resData);
        }
    }

    /**
     * Search data filter
     * 
     * @param array $column 
     * @param array $data 
     * @return array 
     */
    private static function searchFilter(array $column, array $data): array
    {
        $return = [];
        if ($column) {
            foreach ($column as $k => $v) {
                if ($v['database'] && isset($v['search']) && isset($data[$k])) {
                    $_v = trim(strip_tags($data[$k]));
                    if ($_v || is_numeric($_v)) {
                        if ($v['search'] === 'text[~]') {
                            $return[$k . '[~]'] = $_v;
                        } elseif (in_array($v['search'], ['dateRange', 'dateTimeRange', 'timeRange'])) {
                            $return[$k . '[<>]'] = explode(',', $_v);
                        } elseif ($v['search'] === 'checkbox' || ($v['searchProps']['mode'] ?? '') === 'multiple') {
                            $return[$k] = explode(',', $_v);
                        } else {
                            $return[$k] = $_v;
                        }
                    }
                }
            }
        }

        return $return;
    }
}
