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

use Alight\Cache;
use Alight\Request;
use Alight\Response;
use Alight\Router;
use Alight\Utility;
use Exception;
use ErrorException;
use Symfony\Component\Cache\Exception\InvalidArgumentException;

class Auth
{
    /**
     * Verify authorization
     * 
     * @return int 
     * @throws Exception 
     * @throws ErrorException 
     * @throws InvalidArgumentException 
     * @throws InvalidArgumentException 
     */
    public static function verify(): int
    {
        $userId = self::getUserId();
        if ($userId) {
            return $userId;
        }

        if (Request::isAjax()) {
            Response::api(401, ':status_401');
            exit;
        } else {
            Controller::result(401);
            exit;
        }
    }

    /**
     * Get authorized user id
     * 
     * @return int 
     * @throws Exception 
     * @throws ErrorException 
     * @throws InvalidArgumentException 
     * @throws InvalidArgumentException 
     */
    public static function getUserId(): int
    {
        $authId = Router::getAuthId();
        if ($authId) {
            return (int) $authId;
        }

        if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
            $auth = $_SERVER['PHP_AUTH_USER'];
            $session = $_SERVER['PHP_AUTH_PW'];
        } else {
            $auth = $_COOKIE['admin_auth'] ?? '';
            $session = $_COOKIE['admin_session'] ?? '';
        }

        if ($auth && $session) {
            $userId = Model::getUserIdByKey($auth);
            if ($userId) {
                $cache = Cache::init();
                $cacheKey = 'admin_user_auth_' . $userId;
                $authInfo = $cache->get($cacheKey);
                if ($authInfo && $authInfo['session'] == $session) {
                    $userInfo = Model::getUserInfo($userId);
                    if ($userInfo['status'] == 1 && ($authInfo['auth'] ?? '') == $userInfo['auth_key']) {
                        return (int) $userId;
                    }
                }
            }
        }

        return 0;
    }

    /**
     * Store login session
     * 
     * @param int $userId 
     * @param bool $renew 
     * @throws Exception 
     * @throws ErrorException 
     * @throws InvalidArgumentException 
     * @throws InvalidArgumentException 
     */
    public static function store(int $userId, bool $renew = false)
    {
        if ($renew) {
            $auth = $_COOKIE['admin_auth'] ?? '';
            $session = $_COOKIE['admin_session'] ?? '';
        } else {
            $userInfo = Model::getUserInfo($userId);
            $auth = $userInfo['auth_key'];
            $session = Utility::randomHex();
        }

        $authInfo = [
            'auth' => $auth,
            'session' => $session,
        ];
        $cache = Cache::init();
        $cacheKey = 'admin_user_auth_' . $userId;
        $cacheTime = Config::get('remember');
        $cache->set($cacheKey, $authInfo, $cacheTime);

        setcookie('admin_auth', $auth, time() + $cacheTime, '/' . Config::get('path'), '.' . Request::host());
        setcookie('admin_session', $session, time() + $cacheTime, '/' . Config::get('path'), '.' . Request::host());
    }

    /**
     * Clear login session
     * 
     * @param int $userId 
     * @throws Exception 
     * @throws ErrorException 
     * @throws InvalidArgumentException 
     * @throws InvalidArgumentException 
     */
    public static function clear(int $userId)
    {
        if ($userId) {
            $cache = Cache::init();
            $cacheKey = 'admin_user_auth_' . $userId;
            $cache->delete($cacheKey);
        }

        setcookie('admin_auth', '', 0, '/' . Config::get('path'), '.' . Request::host());
        setcookie('admin_session', '', 0, '/' . Config::get('path'), '.' . Request::host());
    }

    /**
     * Check role permission and return role id
     * 
     * @param array $roleIds 
     * @return int 
     * @throws Exception 
     * @throws ErrorException 
     * @throws InvalidArgumentException 
     * @throws InvalidArgumentException 
     */
    public static function checkRole(array $roleIds)
    {
        $userId = self::getUserId();
        if ($userId) {
            $userInfo = Model::getUserInfo($userId);
            if ($userInfo){
                $roleId = (int) $userInfo['role_id'];
                if ($roleIds) {
                    if (in_array($roleId, $roleIds)) {
                        return $roleId;
                    }
                } else {
                    return $roleId;
                }
            }
        }

        if (Request::isAjax()) {
            Response::api(403, ':status_403');
            exit;
        } else {
            Controller::result(403);
            exit;
        }
    }
}
