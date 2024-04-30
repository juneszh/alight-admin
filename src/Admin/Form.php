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
use ErrorException;
use Exception;
use Symfony\Component\Cache\Exception\InvalidArgumentException;
use InvalidArgumentException as GlobalInvalidArgumentException;
use PDOException;

class Form
{
    public static array $config = [];
    private static string $form;
    private static string $key;

    public const
        EVENT_RENDER = 'render',
        EVENT_REQUEST = 'request',
        EVENT_RESPONSE = 'response';

    public const
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
        TYPE_GROUP = 'group',
        TYPE_FORM_LIST = 'formList',
        TYPE_FORM_SET = 'formSet',
        //extension
        TYPE_UPLOAD = 'upload',
        TYPE_RICH_TEXT = 'richText';

    /**
     * Create a form
     * 
     * @param string $form 
     * @return FormOption 
     */
    public static function create(string $form): FormOption
    {
        self::$form = $form;
        self::$key = '';
        if (!isset(self::$config[$form])) {
            self::$config[$form] = [];
        }

        return new FormOption(self::$form);
    }

    /**
     * Create a form field
     * 
     * @param string $key 
     * @return FormField 
     * @throws Exception 
     */
    public static function field(string $key): FormField
    {
        if (!self::$form) {
            throw new Exception('Missing form definition.');
        }
        self::$key = $key;
        self::$config[self::$form][$key] = [
            'title' => $key,
            'database' => true,
            'type' => self::TYPE_TEXT,
        ];

        return new FormField(self::$form, $key, '');
    }

    /**
     * Create a form sub field
     * 
     * @param string $subKey 
     * @return FormField 
     * @throws Exception 
     */
    public static function subField(string $subKey): FormField
    {
        if (!self::$form) {
            throw new Exception('Missing form definition.');
        }
        if (!self::$key) {
            throw new Exception('Missing key definition.');
        }
        $database = false;
        if (self::$config[self::$form][self::$key]['type'] === self::TYPE_GROUP) {
            $database = true;
        } elseif (self::$config[self::$form][self::$key]['type'] === self::TYPE_FORM_SET) {
            $subKey = self::$key . '[' . $subKey . ']';
        }
        self::$config[self::$form][self::$key]['sub'][$subKey] = [
            'title' => $subKey,
            'database' => $database,
            'type' => self::TYPE_TEXT,
        ];

        return new FormField(self::$form, self::$key, $subKey);
    }

    /**
     * Form page render
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
        $_id = Request::request('_id', 0);
        $_form = Request::request('_form', '');
        $_title = Request::request('_title', '');

        $separator = (string) Config::get('separator');
        $_ids = Request::request('_ids');
        if ($_ids) {
            $_ids = is_string($_ids) ? explode($separator, $_ids) : (array) $_ids;
            $_ids = array_values(array_filter(array_map('intval', $_ids)));
        }

        $userId = Auth::getUserId();
        $userInfo = Model::getUserInfo($userId);

        $field = Form::$config[$_form] ?? [];
        foreach ($field as $k => $v) {
            if (isset($v['role']) && $v['role'] && !in_array($userInfo['role_id'], $v['role'])) {
                unset($field[$k]);
            }
        }

        if (!Request::isAjax()) {
            $value = [];
            if ($table && $_id) {
                $value = Model::formGet($table, $_id);
            }
            foreach ($field as $k => $v) {
                if ($value && isset($value[$k]) && $field[$k]['type'] !== self::TYPE_PASSWORD) {
                    $field[$k]['value'] = $value[$k];
                }
                if (isset($field[$k]['value']) && is_string($field[$k]['value']) && (in_array($field[$k]['type'], [self::TYPE_CHECKBOX, self::TYPE_FORM_LIST, self::TYPE_FORM_SET, self::TYPE_UPLOAD]) || ($field[$k]['typeProps']['mode'] ?? '') === 'multiple')) {
                    $field[$k]['value'] = json_decode($field[$k]['value'], true);
                }
            }

            $renderData = [
                'adminPath' => Admin::url(),
                'field' => $field,
            ];

            if (is_callable($callback)) {
                $callback(self::EVENT_RENDER, $renderData);
            }

            Response::render(Admin::path() . '/src/Admin/View.phtml', ['title' => $_title, 'script' => Admin::globalScript('Form', $renderData)]);
        } else {
            $sqlData = self::dataFilter($field, Request::request());

            if (is_callable($callback)) {
                $callback(self::EVENT_REQUEST, $sqlData);
            }

            $rsId = 0;
            $rsIds = [];
            if ($table && $sqlData) {
                if ($_ids) {
                    $rsIds = Model::formUpdateMultiple($table, $sqlData, $_ids);
                } elseif ($_id) {
                    $rsId = Model::formUpdate($table, $sqlData, $_id);
                } else {
                    $rsId = Model::formInsert($table, $sqlData);
                }
            }

            $resData = [
                'id' => $rsId,
                'ids' => $rsIds,
            ];

            if ($rsId || $rsIds) {
                if (is_callable($callback)) {
                    $callback(self::EVENT_RESPONSE, $resData);
                }
            }

            Response::api(0, null, $resData);
        }
    }

    /**
     * Request data filter
     * 
     * @param array $field 
     * @param array $data 
     * @return array 
     * @throws Exception 
     */
    private static function dataFilter(array $field, array $data): array
    {
        $return = [];
        foreach ($field as $k => $v) {
            if ($v['database'] && !isset($v['disabled'])) {
                if (isset($data[$k])) {
                    if (isset($v['confirm'])) {
                        if ($data[$v['confirm']] != $data[$k]) {
                            Response::api(400, ':status_400');
                            exit;
                        }
                    } elseif ($v['type'] === self::TYPE_PASSWORD) {
                        $return[$k] = password_hash($data[$k], PASSWORD_DEFAULT);
                    } elseif (is_array($data[$k])) {
                        $return[$k] = json_encode($data[$k], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    } else {
                        $return[$k] = isset($v['raw']) ? $data[$k] : trim((string) $data[$k]);
                    }
                }
            } elseif ($v['type'] === self::TYPE_GROUP && isset($v['sub'])) {
                $_return = self::dataFilter($v['sub'], $data);
                if ($_return) {
                    $return = array_merge($return, $_return);
                }
            }
        }

        return $return;
    }
}
