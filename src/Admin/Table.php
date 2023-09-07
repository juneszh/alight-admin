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

    public const
        SEARCH_MONEY = 'money',
        SEARCH_DATE = 'date',
        SEARCH_DATE_TIME = 'dateTime',
        SEARCH_DATE_WEEK = 'dateWeek',
        SEARCH_DATE_MONTH = 'dateMonth',
        SEARCH_DATE_QUARTER = 'dateQuarter',
        SEARCH_DATE_YEAR = 'dateYear',
        SEARCH_DATE_RANGE = 'dateRange',
        SEARCH_DATE_TIME_RANGE = 'dateTimeRange',
        SEARCH_TIME = 'time',
        SEARCH_TIME_RANGE = 'timeRange',
        SEARCH_TEXT = 'text',
        SEARCH_SELECT = 'select',
        SEARCH_TREE_SELECT = 'treeSelect',
        SEARCH_CHECKBOX = 'checkbox',
        SEARCH_RADIO = 'radio',
        SEARCH_RADIO_BUTTON = 'radioButton',
        SEARCH_PERCENT = 'percent',
        SEARCH_DIGIT = 'digit',
        SEARCH_COLOR = 'color',
        SEARCH_CASCADER = 'cascader',
        //extension
        SEARCH_TEXT_LIKE = 'text[~]',

        SORT_DEFAULT = '',
        SORT_ASC = 'ascend',
        SORT_DESC = 'descend',

        ALIGN_LEFT = 'left',
        ALIGN_CENTER = 'center',
        ALIGN_RIGHT = 'right',

        FIXED_LEFT = 'left',
        FIXED_RIGHT = 'right',

        ACTION_FORM = 'form',
        ACTION_PAGE = 'page',
        ACTION_CONFIRM = 'confirm',
        ACTION_SUBMIT = 'submit',
        ACTION_POPUP = 'popup',
        ACTION_REDIRECT = 'redirect',

        TYPE_DEFAULT = 'default',
        TYPE_PRIMARY = 'primary',
        TYPE_DASHED = 'dashed',
        TYPE_TEXT = 'text',
        TYPE_LINK = 'link';

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
        return new TableColumn(__FUNCTION__, $key);
    }

    public static function expand(string $key): TableColumn
    {
        if (!isset(self::$config[__FUNCTION__][$key])) {
            self::$config[__FUNCTION__][$key] = [
                'title' => $key,
                'database' => false,
            ];
        }
        return new TableColumn(__FUNCTION__, $key);
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
            'action' => 'form',
            'place' => '_column',
        ];
        return new TableButton(self::$buttonIndex);
    }

    /**
     * Create a column summary
     * 
     * @param string $key 
     * @return TableSummary 
     */
    public static function summary(string $key): TableSummary
    {
        self::$config[__FUNCTION__][$key] = [
            'type' => 'sum',
        ];

        return new TableSummary($key);
    }

    /**
     * Create a statistic
     * 
     * @param string $key 
     * @return TableStatistic
     */
    public static function statistic(string $key): TableStatistic
    {
        self::$config[__FUNCTION__][$key] = [
            'key' => $key,
            'title' => $key,
        ];

        return new TableStatistic($key);
    }

    /**
     * Table page render
     * 
     * @param string $table 
     * @param null|callable $middleware function(string $action, array &$data){} 
     * @throws Exception 
     * @throws ErrorException 
     * @throws InvalidArgumentException 
     * @throws InvalidArgumentException 
     * @throws GlobalInvalidArgumentException 
     * @throws PDOException 
     */
    public static function render(string $table, ?callable $middleware = null)
    {
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

            $expand = new ArrayObject();
            if (isset(Table::$config['expand'])) {
                foreach (Table::$config['expand'] as $k => $v) {
                    if (isset($v['role']) && $v['role'] && !in_array($userInfo['role_id'], $v['role'])) {
                        continue;
                    }

                    if (isset($v['search'])) {
                        unset($v['search']);
                    }

                    $expand[$k] = $v;
                }
            }

            $toolbar = [];
            $batch = [];
            if (isset(Table::$config['button'])) {
                foreach (Table::$config['button'] as $v) {
                    if (isset($v['role']) && $v['role'] && !in_array($userInfo['role_id'], $v['role'])) {
                        continue;
                    }

                    if ($v['place'] === '_toolbar') {
                        $toolbar['button'][] = $v;
                    } elseif ($v['place'] === '_batch') {
                        $batch['button'][] = $v;
                    } elseif (substr($v['place'], 0, 7) === '_column') {
                        $_place = substr($v['place'], 8);
                        if (!$_place) {
                            $_place = $v['place'];
                            if (!isset($column[$_place])) {
                                $column[$_place] = [
                                    'title' => ':action',
                                    'database' => false,
                                    'locale' => true,
                                ];
                            }
                        }
                        $column[$_place]['button'][] = $v;
                    } elseif (substr($v['place'], 0, 7) === '_expand') {
                        $_place = substr($v['place'], 8);
                        if (!$_place) {
                            $_place = $v['place'];
                            if (!isset($expand[$_place])) {
                                $expand[$_place] = [
                                    'title' => ':action',
                                    'database' => false,
                                    'locale' => true,
                                ];
                            }
                        }
                        $expand[$_place]['button'][] = $v;
                    }
                }
            }

            $summary = [];
            if (isset(Table::$config['summary'])) {
                foreach (Table::$config['summary'] as $k => $v) {
                    if (isset($column[$k])) {
                        $summary[$k] = $v;
                    }
                }
            }

            $renderData = [
                'column' => $column,
                'expand' => $expand,
                'toolbar' => $toolbar,
                'batch' => $batch,
                'summary' => $summary,
                'statistic' => Table::$config['statistic'] ?? [],
            ];

            if (is_callable($middleware)) {
                $middleware('render', $renderData);
            }

            Model::userLog($userId);

            Response::render(Admin::path() . '/src/Admin/View.phtml', ['title' => Request::request('_title', ''), 'script' => Admin::globalScript('Table', $renderData)]);
        } else {
            $page = Request::request('current', 1);
            $limit = Request::request('pageSize', 20);
            $limit = $limit < 100 ? $limit : 100;
            $order = Request::request('_order', '') ?: 'id';
            $sort = Request::request('_sort', '') ?: 'asc';

            $sortLimit = ['ascend' => 'asc', 'descend' => 'desc'];
            $sort = $sortLimit[$sort] ?? 'asc';

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

            $searchData = self::searchFilter($column, Request::request());

            if (is_callable($middleware)) {
                $middleware('request', $searchData);
            }

            $count = Model::tableCount($table, $searchData);
            $list = Model::tableSelect($table, $column, $searchData, $page, $limit, $order, $sort);

            $resData = [
                'count' => $count,
                'list' => $list,
            ];

            if (is_callable($middleware)) {
                $middleware('response', $resData);
            }

            Response::api(0, null, $resData);
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
                    $_v = isset($v['searchRaw']) ? $data[$k] : trim((string) $data[$k]);
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
