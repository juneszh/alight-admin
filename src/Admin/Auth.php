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
    private const COOKIE_AUTH = 'admin_auth';
    private const COOKIE_SESSION = 'admin_session';

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
     * Get auth and session from client request
     * 
     * @return array 
     */
    private static function getClientAuth(): array
    {
        $return = ['', ''];

        if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
            $return[0] = $_SERVER['PHP_AUTH_USER'];
            $return[1] = $_SERVER['PHP_AUTH_PW'];
        } else {
            $return[0] = $_COOKIE[self::COOKIE_AUTH] ?? '';
            $return[1] = $_COOKIE[self::COOKIE_SESSION] ?? '';
        }

        return $return;
    }

    /**
     * Get auth cache key (different according to sso setting)
     * 
     * @param string $auth 
     * @param string $session 
     * @return string 
     */
    private static function getAuthCacheKey(string $auth, string $session): string
    {
        $sso = Config::get('sso');
        if ($sso) {
            $cacheKey = 'alight.admin_user.auth.' . md5($auth);
        } else {
            $cacheKey = 'alight.admin_user.auth.' . md5($session);
        }

        return $cacheKey;
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

        list($auth, $session) = self::getClientAuth();
        if ($auth && $session) {
            $cache = Cache::init();
            $cacheKey = self::getAuthCacheKey($auth, $session);
            $authCache = $cache->get($cacheKey);
            if ($authCache && $authCache['auth'] === $auth && $authCache['session'] === $session) {
                $userId = Model::getUserIdByKey($auth);
                $userInfo = $userId ? Model::getUserInfo($userId) : [];
                if ($userInfo && $userInfo['status'] === 1 && $userInfo['auth_key'] === $auth) {
                    return (int) $userId;
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
            list($auth, $session) = self::getClientAuth();
        } else {
            $userInfo = Model::getUserInfo($userId);
            $auth = $userInfo['auth_key'];
            $session = Utility::randomHex();
        }

        $authCache = [
            'auth' => $auth,
            'session' => $session,
        ];
        $cacheTime = Config::get('remember');

        $cache6 = Cache::psr6();
        $cacheKey = self::getAuthCacheKey($auth, $session);
        $cacheItem = $cache6->getItem($cacheKey);
        $cacheItem->set($authCache);
        $cacheItem->expiresAfter((int) $cacheTime);
        $cacheItem->tag('alight.admin_user');
        $cache6->save($cacheItem);

        setcookie(self::COOKIE_AUTH, $auth, time() + $cacheTime, '/' . Config::get('path'), '.' . Request::host());
        setcookie(self::COOKIE_SESSION, $session, time() + $cacheTime, '/' . Config::get('path'), '.' . Request::host());
    }

    /**
     * Clear login session
     * 
     * @throws Exception 
     * @throws ErrorException 
     * @throws InvalidArgumentException 
     * @throws InvalidArgumentException 
     */
    public static function clear()
    {
        list($auth, $session) = self::getClientAuth();
        if ($auth && $session) {
            $cache = Cache::init();
            $cacheKey = self::getAuthCacheKey($auth, $session);
            $cache->delete($cacheKey);
        }

        setcookie(self::COOKIE_AUTH, '', 0, '/' . Config::get('path'), '.' . Request::host());
        setcookie(self::COOKIE_SESSION, '', 0, '/' . Config::get('path'), '.' . Request::host());
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
            if ($userInfo) {
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
