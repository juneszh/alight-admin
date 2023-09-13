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
use Alight\Database;
use Alight\Request;
use Exception;
use ErrorException;
use InvalidArgumentException;
use PDOException;
use Psr\SimpleCache\CacheException;
use Symfony\Component\Cache\Exception\InvalidArgumentException as ExceptionInvalidArgumentException;

class Model
{
    /**
     * Get data or cache
     * 
     * @param string $table 
     * @param null|int $id 
     * @param null|int $ttl 
     * @return array 
     * @throws Exception 
     * @throws ErrorException 
     * @throws ExceptionInvalidArgumentException 
     * @throws CacheException 
     */
    public static function getCacheData(string $table, ?int $id = null, ?int $ttl = 86400): array
    {
        $cache = Cache::init();
        $cacheKey = $table . '_data' . ($id === null ? '' : '_' . $id);

        if ($cache->has($cacheKey)) {
            $result = $cache->get($cacheKey);
        } else {
            $db = Database::init();
            if ($id === null) {
                $result = $db->select($table, '*', ['ORDER' => ['id' => 'ASC']]);
            } elseif ($id > 0) {
                $result = $db->get($table, '*', ['id' => $id]);
            } else {
                return [];
            }
            $cache->set($cacheKey, $result, $ttl);
        }

        return $result ?: [];
    }

    /**
     * Get role list
     * 
     * @return array 
     * @throws Exception 
     * @throws ErrorException 
     * @throws ExceptionInvalidArgumentException 
     * @throws CacheException 
     */
    public static function getRoleList(): array
    {
        return self::getCacheData('admin_role');
    }

    /**
     * Get user id by account
     * 
     * @param string $account 
     * @return int 
     * @throws Exception 
     * @throws ErrorException 
     * @throws ExceptionInvalidArgumentException 
     * @throws ExceptionInvalidArgumentException 
     */
    public static function getUserIdByAccount(string $account): int
    {
        $cache = Cache::init();
        $cacheKey = 'admin_user_id_by_account_' . md5($account);

        if ($cache->has($cacheKey)) {
            return $cache->get($cacheKey);
        }

        $db = Database::init();
        $result = $db->get('admin_user', 'id', ['account' => $account]);

        if ($result) {
            $cache->set($cacheKey, (int) $result, 3600);
        }

        return (int) $result;
    }

    /**
     * Get user id by auth hash
     * 
     * @param string $key 
     * @return int 
     * @throws Exception 
     * @throws ErrorException 
     * @throws ExceptionInvalidArgumentException 
     * @throws ExceptionInvalidArgumentException 
     */
    public static function getUserIdByKey(string $key): int
    {
        $cache = Cache::init();
        $cacheKey = 'admin_user_id_by_key_' . $key;

        if ($cache->has($cacheKey)) {
            return $cache->get($cacheKey);
        }

        $db = Database::init();
        $result = $db->get('admin_user', 'id', ['auth_key' => $key]);

        if ($result) {
            $cache->set($cacheKey, (int) $result, 3600);
        }

        return (int) $result;
    }

    /**
     * Get user info
     * 
     * @param int $id 
     * @return array 
     * @throws Exception 
     * @throws ErrorException 
     * @throws ExceptionInvalidArgumentException 
     * @throws ExceptionInvalidArgumentException 
     */
    public static function getUserInfo(int $id): array
    {
        return self::getCacheData('admin_user', $id);
    }

    /**
     * Log user behavior 
     * 
     * @param int $userId 
     * @param bool $edit 
     * @throws Exception 
     * @throws InvalidArgumentException 
     * @throws PDOException 
     */
    public static function userLog(int $userId, bool $edit = false)
    {
        $table = 'admin_log';
        $now = time();
        $date = date('Y-m-d', $now);
        $hour = date('G', $now);

        $db = Database::init();
        if ($db->has($table, ['user_id' => $userId, 'date' => $date, 'hour' => $hour])) {
            $db->update($table, $edit ? ['edit[+]' => 1] : ['view[+]' => 1], ['user_id' => $userId, 'date' => $date, 'hour' => $hour]);
        } else {
            $db->insert($table, [
                'user_id' => $userId,
                'date' => $date,
                'hour' => $hour,
                'view' => $edit ? 0 : 1,
                'edit' => $edit ? 1 : 0,
                'ip' => Request::ip()
            ]);
        }
    }

    /**
     * Get user behavior logs by date
     * 
     * @param int $userId 
     * @param string $date 
     * @return array 
     * @throws Exception 
     * @throws ErrorException 
     * @throws ExceptionInvalidArgumentException 
     * @throws ExceptionInvalidArgumentException 
     * @throws InvalidArgumentException 
     * @throws PDOException 
     */
    public static function getUserDateLog(int $userId, string $date): array
    {
        $cache = Cache::init();
        $cacheKey = 'admin_user_date_log_' . $userId . '_' . str_replace('-', '', $date);
        if ($cache->has($cacheKey)) {
            return $cache->get($cacheKey);
        }

        $now = time();
        $today = date('Y-m-d', $now);

        $db = Database::init();
        if ($date === $today) {
            $result = $db->select('admin_log', ['hour' => ['view', 'edit']], ['user_id' => $userId, 'date' => $date, 'hour[<]' => date('G', $now)]);
            $cacheTime = 3600 - ($now - strtotime(date('Y-m-d H:00:00', $now)));
        } else {
            $result = $db->select('admin_log', ['hour' => ['view', 'edit']], ['user_id' => $userId, 'date' => $date]);
            $cacheTime = 86400;
        }

        $cache->set($cacheKey, $result ?: [], $cacheTime);

        return $result ?: [];
    }

    /**
     * Count database for Table
     * 
     * @param string $table 
     * @param array $search 
     * @return int
     * @throws Exception 
     * @throws InvalidArgumentException 
     * @throws PDOException 
     */
    public static function tableCount(string $table, array $search = []): int
    {
        $db = Database::init();

        $where = [];
        if ($search) {
            $where['AND'] = $search;
        }

        $count = $db->count($table, '*', $where);

        return $count ?: 0;
    }

    /**
     * Select database for Table
     * 
     * @param string $table 
     * @param array $field 
     * @param array $search 
     * @param int $page 
     * @param int $limit 
     * @param string $order 
     * @param string $sort 
     * @return array
     * @throws Exception 
     * @throws InvalidArgumentException 
     * @throws PDOException 
     */
    public static function tableSelect(string $table, array $field, array $search = [], int $page = 1, int $limit = 20, string $order = 'id', string $sort = 'asc'): array
    {
        $db = Database::init();

        $where = [];
        if ($search) {
            $where['AND'] = $search;
        }

        $start = $page > 1 ? ($page - 1) * $limit : 0;

        $data = [];
        if ($field) {
            if ($limit) {
                $where['LIMIT'] = [$start, $limit];
            }
            $where['ORDER'] = [$order => strtoupper($sort)];
            if ($order != 'id') {
                $where['ORDER']['id'] = strtoupper($sort);
            }
            $columns = array_keys($field);
            if (!isset($field['id'])) {
                $columns[] = 'id';
            }
            $data = $db->select($table, $columns, $where);
        }

        return $data ?: [];
    }
    /**
     * Insert data for Form
     * 
     * @param string $table 
     * @param array $data 
     * @return int 
     * @throws Exception 
     * @throws PDOException 
     * @throws ErrorException 
     * @throws ExceptionInvalidArgumentException 
     * @throws ExceptionInvalidArgumentException 
     */
    public static function formInsert(string $table, array $data): int
    {
        $db = Database::init();
        $db->insert($table, $data);

        if ($db->id()) {
            $cache = Cache::init();
            $cache->deleteMultiple([
                $table . '_data',
                $table . '_data_' . $db->id()
            ]);
        }

        return (int) $db->id();
    }

    /**
     * Get data for From
     * 
     * @param string $table 
     * @param int $id 
     * @return array 
     * @throws Exception 
     */
    public static function formGet(string $table, int $id): array
    {
        $db = Database::init();

        $data = $db->get($table, '*', ['id' => $id]);

        return $data ?: [];
    }

    /**
     * Update data for Form
     * 
     * @param string $table 
     * @param array $data 
     * @param int $id 
     * @return int 
     * @throws Exception 
     * @throws InvalidArgumentException 
     * @throws PDOException 
     * @throws ErrorException 
     * @throws ExceptionInvalidArgumentException 
     * @throws ExceptionInvalidArgumentException 
     */
    public static function formUpdate(string $table, array $data, int $id): int
    {
        $db = Database::init();
        $result = $db->update($table, $data, ['id' => $id]);

        if ($result->rowCount()) {
            $cache = Cache::init();
            $cache->deleteMultiple([
                $table . '_data',
                $table . '_data_' . $id
            ]);
        }

        return $result->rowCount() ? $id : (!$db->error ? $id : 0);
    }

    /**
     * Update multiple data for Form
     * 
     * @param string $table 
     * @param array $data 
     * @param array $id 
     * @return array 
     * @throws Exception 
     * @throws InvalidArgumentException 
     * @throws PDOException 
     * @throws ErrorException 
     * @throws ExceptionInvalidArgumentException 
     * @throws ExceptionInvalidArgumentException 
     */
    public static function formUpdateMultiple(string $table, array $data, array $id): array
    {
        $db = Database::init();
        $result = $db->update($table, $data, ['id' => $id]);

        if ($result->rowCount()) {
            $cache = Cache::init();
            $cacheKeys = [$table . '_data'];
            foreach ($id as $_id) {
                $cacheKeys[] = $table . '_data_' . $_id;
            }
            $cache->deleteMultiple($cacheKeys);
        }

        return $result->rowCount() ? $id : (!$db->error ? $id : []);
    }

}
