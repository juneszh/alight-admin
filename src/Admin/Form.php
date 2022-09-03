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
use Alight\Cache;
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

    /**
     * Create a form
     * 
     * @param string $form 
     * @return FormOption 
     */
    public static function create(string $form): FormOption
    {
        self::$form = $form;
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

        if (!isset(self::$config[self::$form][$key])) {
            self::$config[self::$form][$key] = [
                'title' => $key,
                'database' => true,
                'type' => 'text',
            ];
        }

        return new FormField(self::$form, $key);
    }

    /**
     * Form page render
     * 
     * @param string $table 
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
        Request::$data['_id'] = trim(strip_tags(Request::$data['_id'] ?? ''));
        Request::$data['_form'] = trim(strip_tags(Request::$data['_form'] ?? ''));
        Request::$data['_title'] = trim(strip_tags(Request::$data['_title'] ?? ''));

        if (strpos(Request::$data['_id'], '|') !== false) {
            Request::$data['_id'] = explode('|', Request::$data['_id']);
        }

        $userId = Auth::getUserId();
        $userInfo = Model::getUserInfo($userId);

        $field = Form::$config[Request::$data['_form']] ?? [];
        foreach ($field as $k => $v) {
            if (isset($v['role']) && $v['role'] && !in_array($userInfo['role_id'], $v['role'])) {
                unset($field[$k]);
            }
        }

        if (!Request::isAjax()) {
            $value = [];
            if ($table && Request::$data['_id'] && !is_array(Request::$data['_id'])) {
                $value = Model::formGet($table, (int) Request::$data['_id']);
            }
            foreach ($field as $k => $v) {
                if ($value && isset($value[$k]) && $field[$k]['type'] !== 'password') {
                    $field[$k]['value'] = $value[$k];
                }
                if (isset($field[$k]['value']) && (in_array($field[$k]['type'], ['checkbox', 'upload']) || ($field[$k]['typeProps']['mode'] ?? '') === 'multiple')) {
                    $field[$k]['value'] = explode('|', $field[$k]['value']);
                }
            }

            $renderData = [
                'adminPath' => Admin::url(),
                'field' => $field,
            ];

            if (is_callable($middleware)) {
                $middleware('render', $renderData);
            }

            Response::render('public/alight-admin/index.html', ['title' => Request::$data['_title'] ?? '', 'script' => Admin::globalScript('Form', $renderData)]);
        } else {
            $sqlData = self::dataFilter($field, Request::$data);

            if (is_callable($middleware)) {
                $middleware('filter', $sqlData);
            }

            $rsId = 0;
            if ($table && $sqlData) {
                if (Request::$data['_id']) {
                    $rsId = Model::formUpdate($table, $sqlData, Request::$data['_id']);
                } else {
                    $rsId = Model::formInsert($table, $sqlData);
                }
            }

            if ($rsId) {
                $cache = Cache::init();
                $cache->delete($table . '_list');
                $cache->delete($table . '_enum_list');

                if (is_callable($middleware)) {
                    $middleware('return', $rsId);
                }

                Model::userLog($userId, true);
            }

            Response::api(0, ['result' => 'success']);
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
                    } elseif ($v['type'] === 'password') {
                        $return[$k] = password_hash($data[$k], PASSWORD_DEFAULT);
                    } elseif (in_array($v['type'], ['checkbox', 'upload']) && is_array($data[$k])) {
                        $return[$k] = join('|', $data[$k]);
                    } elseif ($v['type'] === 'select' && ($v['typeProps']['mode'] ?? '') === 'multiple') {
                        $return[$k] = join('|', $data[$k]);
                    } else {
                        $return[$k] = $data[$k];
                    }
                }
            }
        }

        return $return;
    }
}
