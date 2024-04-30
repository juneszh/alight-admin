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
        EVENT_RENDER = 'render',
        EVENT_REQUEST = 'request',
        EVENT_RESPONSE = 'response';

    public const
        ACTION_FORM = 'form',
        ACTION_PAGE = 'page',
        ACTION_CONFIRM = 'confirm',
        ACTION_SUBMIT = 'submit',
        ACTION_POPUP = 'popup',
        ACTION_REDIRECT = 'redirect',

        ALIGN_LEFT = 'left',
        ALIGN_CENTER = 'center',
        ALIGN_RIGHT = 'right',

        BUTTON_DEFAULT = 'default',
        BUTTON_PRIMARY = 'primary',
        BUTTON_DASHED = 'dashed',
        BUTTON_TEXT = 'text',
        BUTTON_LINK = 'link',

        FIXED_LEFT = 'left',
        FIXED_RIGHT = 'right',

        SORT_DEFAULT = '',
        SORT_ASCEND = 'ascend',
        SORT_DESCEND = 'descend',

        TYPE_PASSWORD = 'password',
        TYPE_MONEY = 'money',
        TYPE_TEXTAREA = 'textarea',
        TYPE_DATE = 'date',
        TYPE_DATE_TIME = 'dateTime',
        TYPE_DATE_WEEK = 'dateWeek',
        TYPE_DATE_MONTH = 'dateMonth',
        TYPE_DATE_QUARTER = 'dateQuarter',
        TYPE_DATE_YEAR = 'dateYear',
        TYPE_DATE_RANGE = 'dateRange',
        TYPE_DATE_TIME_RANGE = 'dateTimeRange',
        TYPE_TIME = 'time',
        TYPE_TIME_RANGE = 'timeRange',
        TYPE_TEXT = 'text',
        TYPE_SELECT = 'select',
        TYPE_TREE_SELECT = 'treeSelect',
        TYPE_CHECKBOX = 'checkbox',
        TYPE_RATE = 'rate',
        TYPE_RADIO = 'radio',
        TYPE_RADIO_BUTTON = 'radioButton',
        TYPE_PROGRESS = 'progress',
        TYPE_PERCENT = 'percent',
        TYPE_DIGIT = 'digit',
        TYPE_DIGIT_RANGE = 'digitRange',
        TYPE_SECOND = 'second',
        TYPE_AVATAR = 'avatar',
        TYPE_CODE = 'code',
        TYPE_SWITCH = 'switch',
        TYPE_FROM_NOW = 'fromNow',
        TYPE_IMAGE = 'image',
        TYPE_JSON_CODE = 'jsonCode',
        TYPE_COLOR = 'color',
        TYPE_CASCADER = 'cascader',
        //extension
        TYPE_QRCODE = 'qrcode';

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
     * Create a column
     * 
     * @param string $key 
     * @return TableColumn 
     */
    public static function column(string $key): TableColumn
    {
        self::$config[__FUNCTION__][$key] = [
            'title' => $key,
            'database' => ($key !== '_column'),
        ];
        return new TableColumn(__FUNCTION__, $key);
    }

    /**
     * Create a expand column
     * 
     * @param string $key 
     * @return TableExpand 
     */
    public static function expand(string $key): TableExpand
    {
        self::$config[__FUNCTION__][$key] = [
            'title' => $key,
        ];
        return new TableExpand(__FUNCTION__, $key);
    }

    /**
     * Create a Summary
     * 
     * @param string $key 
     * @return TableSummary
     */
    public static function summary(string $key): TableSummary
    {
        self::$config[__FUNCTION__][$key] = [
            'precision' => 2,
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
     * @param null|callable $callback function(string $event, array &$data){} 
     * @throws Exception 
     * @throws ErrorException 
     * @throws InvalidArgumentException 
     * @throws InvalidArgumentException 
     * @throws GlobalInvalidArgumentException 
     * @throws PDOException 
     */
    public static function render(string $table, ?callable $callback = null)
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

            if (is_callable($callback)) {
                $callback(self::EVENT_RENDER, $renderData);
            }

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

            if (is_callable($callback)) {
                $callback(self::EVENT_REQUEST, $searchData);
            }

            $resData = [
                'count' => 0,
                'list' => [],
            ];

            if ($table) {
                $resData['count'] = Model::tableCount($table, $searchData);
                $resData['list'] = Model::tableSelect($table, $column, $searchData, $page, $limit, $order, $sort);
            }

            if (is_callable($callback)) {
                $callback(self::EVENT_RESPONSE, $resData);
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
                if ($v['database'] && isset($v['searchType']) && isset($data[$k])) {
                    $_v = $data[$k];
                    if ($_v || is_numeric($_v)) {
                        if ($v['searchType'] === self::TYPE_TEXT) {
                            $return[$k . '[~]'] = $_v;
                        } elseif (in_array($v['searchType'], [self::TYPE_DATE_RANGE, self::TYPE_DATE_TIME_RANGE, self::TYPE_TIME_RANGE])) {
                            $return[$k . '[<>]'] = explode(',', (string) $_v);
                        } elseif ($v['searchType'] === self::TYPE_CHECKBOX || ($v['searchProps']['mode'] ?? '') === 'multiple') {
                            $return[$k] = explode(',', (string) $_v);
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
