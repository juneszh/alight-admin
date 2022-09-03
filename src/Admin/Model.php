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
use Alight\Database;
use Alight\Request;
use Alight\Utility;
use Exception;
use ErrorException;
use InvalidArgumentException;
use PDOException;
use Symfony\Component\Cache\Exception\InvalidArgumentException as ExceptionInvalidArgumentException;

class Model
{
    /**
     * Get role enum list
     * 
     * @param array $filter 
     * @return array 
     * @throws Exception 
     * @throws ErrorException 
     * @throws ExceptionInvalidArgumentException 
     * @throws ExceptionInvalidArgumentException 
     * @throws InvalidArgumentException 
     * @throws PDOException 
     */
    public static function getRoleEnumList(array $filter = []): array
    {
        $cache = Cache::init();
        $cacheKey = 'admin_role_enum_list';

        if ($cache->has($cacheKey)) {
            $result = $cache->get($cacheKey);
        } else {
            $db = Database::init();
            $result = $db->select('admin_role', ['id', 'name'], ['ORDER' => ['id' => 'ASC']]);

            $cache->set($cacheKey, $result, 86400);
        }

        if ($filter) {
            $result = Utility::arrayFilter($result, $filter);
        }

        return $result;
    }

    /**
     * Get user enum list
     * 
     * @param array $filter 
     * @return array 
     * @throws Exception 
     * @throws ErrorException 
     * @throws ExceptionInvalidArgumentException 
     * @throws ExceptionInvalidArgumentException 
     * @throws InvalidArgumentException 
     * @throws PDOException 
     */
    public static function getUserEnumList(array $filter = []): array
    {
        $cache = Cache::init();
        $cacheKey = 'admin_user_enum_list';

        if ($cache->has($cacheKey)) {
            $result = $cache->get($cacheKey);
        } else {
            $db = Database::init();
            $result = $db->select('admin_user', ['id', 'name', 'role_id', 'status'], ['ORDER' => ['id' => 'ASC']]);

            $cache->set($cacheKey, $result, 86400);
        }

        if ($filter) {
            $result = Utility::arrayFilter($result, $filter);
        }

        return $result;
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
     * @param string $hash 
     * @return int 
     * @throws Exception 
     * @throws ErrorException 
     * @throws ExceptionInvalidArgumentException 
     * @throws ExceptionInvalidArgumentException 
     */
    public static function getUserIdByHash(string $hash): int
    {
        $cache = Cache::init();
        $cacheKey = 'admin_user_id_by_hash_' . $hash;

        if ($cache->has($cacheKey)) {
            return $cache->get($cacheKey);
        }

        $db = Database::init();
        $result = $db->get('admin_user', 'id', ['auth_hash' => $hash]);

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
        return $id > 0 ? self::formGet('admin_user', $id) : [];
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
            $cacheKey = $table . '_info_' . $db->id();
            $cache->delete($cacheKey);
        }

        return (int) $db->id();
    }

    /**
     * Update data for Form
     * 
     * @param string $table 
     * @param array $data 
     * @param mixed $id 
     * @return mixed 
     * @throws Exception 
     * @throws InvalidArgumentException 
     * @throws PDOException 
     * @throws ErrorException 
     * @throws ExceptionInvalidArgumentException 
     * @throws ExceptionInvalidArgumentException 
     */
    public static function formUpdate(string $table, array $data, $id)
    {
        $db = Database::init();
        $result = $db->update($table, $data, ['id' => $id]);

        if ($result->rowCount()) {
            $cache = Cache::init();
            if (is_array($id)) {
                $cacheKey = [];
                foreach ($id as $_id) {
                    $cacheKey[] = $table . '_info_' . $_id;
                }
                $cache->deleteMultiple($cacheKey);
            } else {
                $cacheKey = $table . '_info_' . $id;
                $cache->delete($cacheKey);
            }
        }

        return $result->rowCount() ? $id : (!$db->error ? $id : null);
    }
}
