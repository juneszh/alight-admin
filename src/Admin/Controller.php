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
use Alight\App;
use Alight\Cache;
use Alight\Request;
use Alight\Response;
use Alight\Utility;
use DateInterval;
use DatePeriod;
use DateTime;
use Exception;
use ErrorException;
use InvalidArgumentException as GlobalInvalidArgumentException;
use PDOException;
use Symfony\Component\Cache\Exception\InvalidArgumentException;

class Controller
{

    /**
     * Home page
     * 
     * @throws Exception 
     * @throws ErrorException 
     * @throws InvalidArgumentException 
     * @throws InvalidArgumentException 
     */
    public static function index()
    {
        $userId = Auth::getUserId();
        Auth::store($userId, true);

        $title = Config::get('title');
        $roleId = Auth::checkRole([]);
        $menu = Menu::build($title, $roleId);

        Response::render('public/alight-admin/index.html', ['title' => $title, 'script' => Admin::globalScript('Home', ['menu' => $menu])]);
    }

    /**
     * Login page
     * 
     * @throws Exception 
     * @throws ErrorException 
     * @throws InvalidArgumentException 
     * @throws InvalidArgumentException 
     * @throws GlobalInvalidArgumentException 
     * @throws PDOException 
     */
    public static function login()
    {
        if (Request::method() === 'POST') {
            $account = trim(strip_tags(Request::$body['account'] ?? ''));
            $password = trim(Request::$body['password'] ?? '');
            $captchaCode = trim(strip_tags(Request::$body['captcha'] ?? ''));

            if (!$account || !$password || !$captchaCode) {
                Response::api(1001, ':missing_param');
                exit;
            }

            $captchaHash = $_COOKIE['admin_captcha'] ?? '';
            $cache = Cache::init();
            $cacheKey = 'admin_captcha_' . $captchaHash;
            $captchaCodeCache = $cache->get($cacheKey);
            $cache->delete($cacheKey);
            setcookie('admin_captcha', '', 0, '/' . trim(Config::get('path'), '/'), '.' . Request::host());

            if (!$captchaCodeCache || $captchaCode != $captchaCodeCache) {
                Response::api(1002, ':invalid_captcha');
                exit;
            }

            $userId = Model::getUserIdByAccount($account);
            if (!$userId) {
                Response::api(1003, ':invalid_account');
                exit;
            }

            $waitMinute = 15;
            $failTimes = (int)$cache->get('admin_user_login_fail_' . $userId);
            if ($failTimes >= 5) {
                Response::api(1004, ':try_again_later');
                exit;
            }

            $userInfo = Model::getUserInfo($userId);
            if (!password_verify($password, $userInfo['password'])) {
                $cache->set('admin_user_login_fail_' . $userId, $failTimes + 1, $waitMinute * 60);
                Response::api(1005, ':invalid_account');
                exit;
            }

            if ($userInfo['status'] != 1) {
                Response::api(1006, ':invalid_account');
                exit;
            }

            Auth::store($userId);

            Response::api(0, ['result' => 'success']);
        } else {
            if (Auth::getUserId()) {
                Response::redirect(Admin::url());
            }

            Response::render('public/alight-admin/index.html', ['title' => Config::get('title'), 'script' => Admin::globalScript('Login')]);
        }
    }

    /** 
     * Console page
     */
    public static function console()
    {
        $userId = Auth::getUserId();

        if (!Request::isAjax()) {
            Model::userLog($userId);

            $userInfo = Model::getUserInfo($userId);

            $avatarDomain = Config::get('cravatar') ? 'cravatar.cn' : 'www.gravatar.com';
            $avatar = $userInfo['email'] ? 'https://' . $avatarDomain . '/avatar/' . md5(strtolower(trim($userInfo['email']))) . '?s=100&d=mp' : '';

            $roleEnum = Model::getRoleEnumList(['id' => $userInfo['role_id']]);
            $roleName = $roleEnum ? reset($roleEnum)['name'] : '';

            Response::render('public/alight-admin/index.html', ['title' => Config::get('title'), 'script' => Admin::globalScript('Console', [
                'user' => [
                    'id' => $userId,
                    'avatar' => $avatar,
                    'account' => $userInfo['account'],
                    'name' => $userInfo['name'],
                    'role' => $roleName,
                ],
                'chart' => [
                    0 => [
                        'component'  => 'Heatmap',
                        'api' => '/admin/console',
                        'config' => [
                            'width' => '100%',
                            'height' => 200,
                            'yField' => 'date',
                            'xField' => 'time',
                            'shape' => 'circle',
                            'colorField' => 'action',
                            'sizeField' => 'size',
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
                        ],
                        'grid' => [
                            'xs' => 24,
                            'sm' => 12,
                            'md' => 16,
                            'lg' => 18,
                            'xxl' => 20,
                        ]
                    ]
                ]
            ])]);
        } else {
            $resData = [];

            $now = time();
            $tomorrow = date('Y-m-d', $now + 86400);
            $lastWeek = date('Y-m-d', $now - 86400 * 6);
            $datePeriod = new DatePeriod(
                new DateTime($lastWeek),
                new DateInterval('P1D'),
                new DateTime($tomorrow)
            );
            foreach ($datePeriod as $value) {
                $log = Model::getUserDateLog($userId, $value->format('Y-m-d'));
                for ($hour = 0; $hour < 24; $hour++) {
                    $resData[] = [
                        'date' => $value->format('n-d'),
                        'time' => $hour . ':00',
                        'size' => isset($log[$hour]) ? 1 : 0,
                        'action' => isset($log[$hour]) ? $log[$hour]['view'] + $log[$hour]['edit'] : 0,
                        'title' => "\u{1F50D}" . ' ' . ($log[$hour]['view'] ?? 0) . ' ' . "\u{270F}" . ' ' . ($log[$hour]['edit'] ?? 0),
                    ];
                }
            }

            Response::api(0, ['data' => $resData]);
        }
    }

    /**
     * Error page
     * 
     * @throws Exception 
     */
    public static function error($status)
    {
        $status = in_array($status, [401, 403, 404, 500]) ? (int) $status : 404;

        Response::render('public/alight-admin/index.html', ['title' => Config::get('title'), 'script' => Admin::globalScript('Error', ['status' => $status])]);
    }

    /**
     * User logout
     * 
     * @throws Exception 
     * @throws ErrorException 
     * @throws InvalidArgumentException 
     * @throws InvalidArgumentException 
     */
    public static function logout()
    {
        $userId = Auth::getUserId();
        Auth::clear($userId);

        Response::redirect(Admin::url('login'));
    }

    /**
     * Captcha image
     * 
     * @throws Exception 
     * @throws ErrorException 
     * @throws InvalidArgumentException 
     * @throws InvalidArgumentException 
     */
    public static function captcha()
    {
        $phraseBuilder = new \Gregwar\Captcha\PhraseBuilder(5, '0123456789');
        $builder = new \Gregwar\Captcha\CaptchaBuilder(null, $phraseBuilder);
        $code = $builder->build(130, 40)->getPhrase();

        $captchaHash = Utility::uid();

        $cache = Cache::init();
        $cache->set('admin_captcha_' . $captchaHash, $code, 300);

        setcookie('admin_captcha', $captchaHash, time() + 300, '/' . trim(Config::get('path'), '/'), '.' . Request::host());

        header('Content-type: image/jpeg');
        $builder->output();
    }

    /**
     * Role table page and api
     * 
     * @throws Exception 
     * @throws ErrorException 
     * @throws InvalidArgumentException 
     * @throws InvalidArgumentException 
     * @throws GlobalInvalidArgumentException 
     * @throws PDOException 
     */
    public static function roleTable()
    {
        Auth::checkRole([1]);

        Table::column('id')->title('ID')->sort('ascend');
        Table::column('name')->title(':role');
        Table::column('create_time')->title(':create_time');

        Table::button('add')->title(':add')->toolbar();
        Table::button('edit')->title(':edit');

        Table::render('admin_role');
    }

    /**
     * Role form page and api
     * 
     * @throws Exception 
     * @throws ErrorException 
     * @throws InvalidArgumentException 
     * @throws InvalidArgumentException 
     * @throws GlobalInvalidArgumentException 
     * @throws PDOException 
     */
    public static function roleForm()
    {
        Auth::checkRole([1]);

        Form::create('add');
        Form::field('name')->title(':name')->required();

        Form::create('edit')->copy('add');

        Form::render('admin_role');
    }

    /**
     * User table page and api
     * 
     * @throws Exception 
     * @throws ErrorException 
     * @throws InvalidArgumentException 
     * @throws InvalidArgumentException 
     * @throws GlobalInvalidArgumentException 
     * @throws PDOException 
     */
    public static function userTable()
    {
        Auth::checkRole([1]);

        $roleEnum = Model::getRoleEnumList();
        $statusEnum = [1 => ':enable', 2 => ':disable'];

        Table::column('id')->title('ID')->sort('ascend');
        Table::column('account')->title(':account')->search()->sort();
        Table::column('role_id')->title(':role')->search('select')->enum($roleEnum, 'id', 'name');
        Table::column('name')->title(':name')->search();
        Table::column('email')->title(':email')->search();
        Table::column('status')->title(':status')->search('select')->enum($statusEnum);
        Table::column('create_time')->title(':create_time');
        Table::column('_action')->database(false)->title(':action')->fixed('right');

        Table::button('add')->title(':add')->toolbar();
        Table::button('edit')->title(':edit');
        Table::button('password')->title(':password')->danger();
        Table::button('status')->title(':enable')->column('status')->action('confirm')->param(['status' => 2])->if(['status' => 1])->type('primary');
        Table::button('status')->title(':disable')->column('status')->action('confirm')->param(['status' => 1])->if(['status' => 2])->type('primary')->danger();
        Table::button('status')->title(':disable')->batch()->param(['status' => 1]);

        Table::render('admin_user');
    }

    /**
     * User form page and api
     * 
     * @throws Exception 
     * @throws ErrorException 
     * @throws InvalidArgumentException 
     * @throws InvalidArgumentException 
     * @throws GlobalInvalidArgumentException 
     * @throws PDOException 
     */
    public static function userForm()
    {
        $role = [1];

        if (substr(Request::$data['_form'] ?? '', 0, 3) === 'my_') {
            Request::$data['_id'] = (string) Auth::getUserId();
            $role = [];
        }

        Auth::checkRole($role);

        $roleEnum = Model::getRoleEnumList();

        Form::create('add');
        Form::field('account')->title(':account')->required();
        Form::field('role_id')->title(':role')->type('select')->enum($roleEnum, 'id', 'name')->required()->default('1');
        Form::field('name')->title(':name')->required();
        Form::field('email')->title(':email');
        Form::field('password')->title(':password')->type('password')->required();
        Form::field('confirm_password')->database(false)->title(':confirm_password')->type('password')->required()->confirm('password');

        Form::create('edit')->copy('add');
        Form::field('password')->delete();
        Form::field('confirm_password')->delete();

        Form::create('my_profile')->copy('edit');
        Form::field('account')->delete();
        Form::field('role_id')->delete();

        Form::create('password');
        Form::field('password')->title(':password')->type('password')->required();
        Form::field('confirm_password')->title(':confirm_password')->type('password')->required()->confirm('password');

        Form::create('my_password')->copy('password');

        Form::create('status');
        Form::field('status')->title(':status');

        Form::render('admin_user', function ($action, &$return) {
            if ($action == 'filter') {
                if (in_array(Request::$data['_form'], ['add', 'password', 'my_password'])) {
                    $return['auth_hash'] = Utility::uid();
                }
            }
        });
    }

    /**
     * File upload api
     * 
     * @throws Exception 
     */
    public static function upload()
    {
        $path = trim(strip_tags(Request::$data['path'] ?? ''));
        $local = (int) (Request::$data['local'] ?? 0);
        $keepName = (int) (Request::$data['keep'] ?? 0);
        $tinymce = (int) (Request::$data['tinymce'] ?? 0);
        $file = $_FILES['file'] ?? null;

        $fileName = '';
        $now = time();
        $filePath = 'upload/' . (trim($path, '/') ? trim($path, '/') : 'file') . '/' . date('ym', $now) . '/' . date('d', $now);
        $localPath = App::root('public' . '/' . $filePath);
        if (!is_dir($localPath)) {
            mkdir($localPath, 0775, true);
        }

        if ($file) {
            if ($keepName) {
                $fileName = preg_replace('/[^\w\-_.]/', '', $file['name']);
                $dotPos = strrpos($fileName, '.');
                $fileName = substr_replace($fileName, '_' . date('His') . '.', $dotPos, 1);
            } else {
                $fileName = md5($file['name']);
                $extIndex = strrpos($file['name'], '.');
                if ($extIndex !== false) {
                    $fileName .= strtolower(substr($file['name'], $extIndex));
                }
            }
            move_uploaded_file($file['tmp_name'], $localPath . '/' . $fileName);
        }

        if (!$fileName) {
            Response::api(400, ':status_400');
            exit;
        }

        $resData = [
            'name' => $filePath . '/' . $fileName,
            'url' => Request::scheme() . '://' . Request::host() . '/' . $filePath . '/' . $fileName,
        ];

        if ($tinymce) {
            header('Content-Type: application/json; charset=utf-8', true, 200);
            echo json_encode(['location' => $local ? $resData['name'] : $resData['url']], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            Response::api(0, $resData);
        }
    }
}
