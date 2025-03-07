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
use Alight\App;
use Alight\Cache;
use Alight\Request;
use Alight\Response;
use Alight\Utility;

class Controller
{

    /**
     * Home page
     */
    public static function index()
    {
        $userId = Auth::getUserId();
        Auth::store($userId, true);

        $roleId = Auth::checkRole([]);
        $menu = Menu::build($roleId);

        Response::render(Admin::path() . '/src/Admin/View.php', ['title' => Config::get('title'), 'script' => Admin::globalScript('Home', ['menu' => $menu])]);
    }

    /**
     * Result page
     * 
     * @param mixed $status 
     * @param mixed $message 
     */
    public static function result($status, $message = null)
    {
        $status = in_array($status, [200, 401, 403, 404, 500]) ? (int) $status : $status;
        $message = $message ? urldecode($message) : null;

        Response::render(Admin::path() . '/src/Admin/View.php', ['title' => Config::get('title'), 'script' => Admin::globalScript('Result', ['status' => $status, 'message' => $message])]);
    }

    /**
     * Captcha image
     */
    public static function captcha()
    {
        $phraseBuilder = new \Gregwar\Captcha\PhraseBuilder(5, '0123456789');
        $builder = new \Gregwar\Captcha\CaptchaBuilder(null, $phraseBuilder);
        $code = $builder->build(130, 40)->getPhrase();

        $captchaHash = Utility::randomHex();

        $cache = Cache::init();
        $cacheTime = 300;
        $cache->set('alight.admin_captcha.' . $captchaHash, $code, $cacheTime);

        setcookie('admin_captcha', $captchaHash, [
            'expires' => time() + $cacheTime,
            'path' => '/' . Config::get('path'),
            'domain' => '.' . Request::host(),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        header('Content-type: image/jpeg');
        $builder->output();
    }

    /**
     * Login page
     */
    public static function login()
    {
        if (Request::method() === 'POST') {
            $account = Request::post('account', '');
            $password = Request::post('password', '');
            $captchaCode = Request::post('captcha', '');

            if (!$account || !$password || !$captchaCode) {
                Response::api(1001, ':missing_param');
                exit;
            }

            $captchaHash = $_COOKIE['admin_captcha'] ?? '';
            $cache = Cache::init();
            $cacheKey = 'alight.admin_captcha.' . $captchaHash;
            $captchaCodeCache = $cache->get($cacheKey);
            $cache->delete($cacheKey);
            setcookie('admin_captcha', '', [
                'expires' => 0,
                'path' => '/' . Config::get('path'),
                'domain' => '.' . Request::host(),
                'httponly' => true,
                'samesite' => 'Strict',
            ]);

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
            $failTimes = (int) $cache->get('alight.admin_login_fail.' . $userId);
            if ($failTimes >= 5) {
                Response::api(1004, ':try_again_later');
                exit;
            }

            $userInfo = Model::getUserInfo($userId);
            if (!password_verify($password, $userInfo['password'])) {
                $cache->set('alight.admin_login_fail.' . $userId, $failTimes + 1, $waitMinute * 60);
                Response::api(1003, ':invalid_account');
                exit;
            }

            if ($userInfo['status'] != 1) {
                Response::api(1005, ':invalid_account');
                exit;
            }

            Auth::store($userId);

            Response::api(0);
        } else {
            if (Auth::getUserId()) {
                Response::redirect(Admin::url());
            }

            Response::render(Admin::path() . '/src/Admin/View.php', ['title' => Config::get('title'), 'script' => Admin::globalScript('Login')]);
        }
    }

    /**
     * User logout
     */
    public static function logout()
    {
        Auth::clear();

        Response::redirect(Admin::url('login'));
    }

    /**
     * Console page
     */
    public static function console()
    {
        $consoleFile = App::root(Config::get('console'));
        if ($consoleFile && file_exists($consoleFile)) {
            require $consoleFile;
        }

        $chart = Console::$config;
        foreach ($chart as $k => $v) {
            if (!$v || (isset($v['role']) && $v['role'] && !in_array($userInfo['role_id'], $v['role']))) {
                unset($chart[$k]);
            }
        }

        Response::render(Admin::path() . '/src/Admin/View.php', ['title' => Config::get('title'), 'script' => Admin::globalScript('Console', ['chart' => $chart])]);
    }

    /**
     * User profile api in console page
     */
    public static function consoleUser()
    {
        $userId = Auth::getUserId();
        $userInfo = Model::getUserInfo($userId);

        preg_match('/^(\d{5,11})@qq\.com$/', $userInfo['email'], $match);
        if (isset($match[1])) {
            $avatar = 'https://q.qlogo.cn/g?b=qq&nk=' . $match[1] . '&s=100';
        } else {
            $avatarDomain = Config::get('cravatar') ? 'cravatar.cn' : 'seccdn.libravatar.org';
            $avatar = 'https://' . $avatarDomain . '/avatar/' . md5(strtolower(trim($userInfo['email']))) . '?s=100&d=mp';
        }

        $roleEnum = Utility::arrayFilter(Model::getRoleList(), ['id' => $userInfo['role_id']]);
        $roleName = $roleEnum ? reset($roleEnum)['name'] : '';


        $consoleFile = App::root(Config::get('console'));
        if ($consoleFile && file_exists($consoleFile)) {
            require $consoleFile;
        }

        $resData =  [
            'id' => $userId,
            'avatar' => $avatar,
            'account' => $userInfo['account'],
            'name' => $userInfo['name'],
            'role' => $roleName,
        ];

        Response::api(0, null, $resData);
    }

    /**
     * Notice list api in console page
     */
    public static function consoleNoticeList()
    {
        $page = Request::request('page', 1);

        $userId = Auth::getUserId();
        $userInfo = Model::getUserInfo($userId);

        $resData = Model::getNoticeList($userInfo['id'], $userInfo['role_id'], $page);

        Response::api(0, null, $resData);
    }

    /**
     * Notice details form page
     */
    public static function consoleNoticeForm()
    {
        Form::create('read');
        Form::field('content')->title('')->type(Form::TYPE_TEXTAREA)->readonly();
        Form::field('create_time')->title('')->readonly();

        Form::render('admin_notice');
    }

    /**
     * Mark notice as read api
     */
    public static function consoleNoticeRead()
    {
        $noticeId = Request::request('id', 0);

        $userId = Auth::getUserId();

        Model::addNoticeRead($userId, $noticeId);

        Response::api(0);
    }

    /**
     * Role table page and api
     */
    public static function roleTable()
    {
        Auth::checkRole([1]);

        Table::column('id')->title('ID')->sort(Table::SORT_ASCEND);
        Table::column('name')->title(':role');
        Table::column('create_time')->title(':create_time');

        Table::button('add')->title(':add')->toolbar();
        Table::button('edit')->title(':edit');

        Table::render('admin_role');
    }

    /**
     * Role form page and api
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
     */
    public static function userTable()
    {
        Auth::checkRole([1]);

        $roleEnum = Utility::arrayFilter(Model::getRoleList(), [], 'id', 'name');
        $statusEnum = [1 => ['text' => ':enable', 'status' => 'success'], 2 => ['text' => ':disable', 'status' => 'error']];

        Table::column('id')->title('ID')->sort(Table::SORT_ASCEND);
        Table::column('account')->title(':account')->search()->sort();
        Table::column('role_id')->title(':role')->search(Table::TYPE_SELECT)->enum($roleEnum);
        Table::column('name')->title(':name')->search();
        Table::column('email')->title(':email')->search();
        Table::column('status')->title(':status')->search(Table::TYPE_SELECT)->enum($statusEnum);
        Table::column('create_time')->title(':create_time')->search(Table::TYPE_DATE_RANGE);

        Table::button('add')->title(':add')->toolbar();
        Table::button('edit')->title(':edit');
        Table::button('password')->title(':password')->color(Table::COLOR_DANGER);

        Table::render('admin_user');
    }

    /**
     * User form page and api
     */
    public static function userForm()
    {
        $role = [1];

        if (substr(Request::request('_form', ''), 0, 3) === 'my_') {
            Request::request('_id', null, (string) Auth::getUserId());
            $role = [];
        }

        Auth::checkRole($role);

        $roleEnum = Utility::arrayFilter(Model::getRoleList(), [], 'id', 'name');
        $statusEnum = [1 => ':enable', 2 => ':disable'];

        Form::create('add');
        Form::field('account')->title(':account')->required();
        Form::field('role_id')->title(':role')->type(Form::TYPE_SELECT)->enum($roleEnum)->required()->default(1);
        Form::field('name')->title(':name')->required();
        Form::field('email')->title(':email');
        Form::field('password')->title(':password')->type(Form::TYPE_PASSWORD)->required();
        Form::field('confirm_password')->database(false)->title(':confirm_password')->type(Form::TYPE_PASSWORD)->required()->confirm('password');

        Form::create('edit')->copy('add');
        Form::field('password')->delete();
        Form::field('confirm_password')->delete();
        Form::field('status')->title(':status')->type(Form::TYPE_RADIO)->enum($statusEnum);

        Form::create('password')->copy('add', ['password', 'confirm_password']);

        Form::create('my_profile')->copy('add', ['name', 'email']);
        Form::create('my_password')->copy('password');

        Form::render('admin_user', function (string $event, array &$data) {
            if ($event == Form::EVENT_REQUEST) {
                if (in_array(Request::request('_form', ''), ['add', 'password', 'my_password'])) {
                    $data['auth_key'] = Utility::randomHex();
                }
            }
        });
    }

    /**
     * File upload api
     */
    public static function upload()
    {
        $path = trim(Request::request('path', ''), '/');
        $local = Request::request('local', 0);
        $keepName = Request::request('keep', 0);
        $tinymce = Request::request('tinymce', 0);
        $file = Request::file('file');

        $fileName = '';
        $now = time();
        $path = str_replace('..', '', $path);
        $filePath = 'upload/' . ($path ?: 'file') . '/' . date('Y', $now) . '/' . date('md', $now);
        $localPath = App::root('public' . '/' . $filePath);
        if (!is_dir($localPath)) {
            @mkdir($localPath, 0777, true);
        }

        if ($file) {
            if ($keepName) {
                $fileName = preg_replace('/[^\w\-_.]/', '', $file['name']);
                $dotPos = strrpos($fileName, '.');
                $mtime = explode(' ', microtime());
                $fileName = substr_replace($fileName, '_' . date('His', (int) $mtime[1]) . $mtime[0] . '.', $dotPos, 1);
            } else {
                $fileName = md5_file($file['tmp_name']);
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
            'name' => '/' . $filePath . '/' . $fileName,
            'url' => Request::scheme() . '://' . Request::host() . '/' . $filePath . '/' . $fileName,
        ];

        if ($tinymce) {
            header('Content-Type: application/json; charset=utf-8', true, 200);
            echo json_encode(['location' => $local ? $resData['name'] : $resData['url']], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            Response::api(0, null, $resData);
        }
    }
}
