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
use Exception;
use ErrorException;
use InvalidArgumentException;
use PDOException;
use Psr\SimpleCache\CacheException;
use Symfony\Component\Cache\Exception\InvalidArgumentException as ExceptionInvalidArgumentException;
use Symfony\Contracts\Cache\ItemInterface;

class Model
{
    /**
     * Get data or cache
     * 
     * @param string $table 
     * @param null|int $id 
     * @param null|int $ttl 
     * @param array $where 
     * @return array 
     * @throws Exception 
     * @throws ErrorException 
     * @throws ExceptionInvalidArgumentException 
     * @throws CacheException 
     */
    public static function getCacheData(string $table, ?int $id = null, ?int $ttl = 86400, $where = []): array
    {
        $cache6 = Cache::psr6();
        $cacheKey = 'alight.' . $table . ($id === null ? '' : '.' . $id) . ($where ? '.' . md5(json_encode($where)) : '');

        $result = $cache6->get($cacheKey, function (ItemInterface $item) use ($table, $id, $ttl, $where) {
            $db = Database::init();
            if ($id === null) {
                $result = $db->select($table, '*', $where ?: ['ORDER' => ['id' => 'ASC']]);
                $item->tag('alight.' . $table . '.list');
            } elseif ($id > 0) {
                $result = $db->get($table, '*', $where ?: ['id' => $id]);
            } else {
                $result = [];
            }
            $item->expiresAfter($ttl);
            $item->tag('alight.' . $table);
            return $result;
        });

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
        $cache6 = Cache::psr6();
        $cacheKey = 'alight.admin_user.id_by_account.' . md5($account);

        $result = $cache6->get($cacheKey, function (ItemInterface $item) use ($account) {
            $db = Database::init();
            $result = $db->get('admin_user', 'id', ['account' => $account]);

            $item->expiresAfter(3600);
            $item->tag('alight.admin_user');
            return $result;
        });

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
        $cache6 = Cache::psr6();
        $cacheKey = 'alight.admin_user.id_by_key.' . md5($key);

        $result = $cache6->get($cacheKey, function (ItemInterface $item) use ($key) {
            $db = Database::init();
            $result = $db->get('admin_user', 'id', ['auth_key' => $key]);

            $item->expiresAfter(3600);
            $item->tag('alight.admin_user');
            return $result;
        });

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
     * Add a notice
     *  
     * @param string $title 
     * @param string $content 
     * @param array $toRole 
     * @param array $toUser
     * @return array 
     * @throws Exception 
     * @throws PDOException 
     */
    public static function addNotice(string $title, string $content = '', array $toRole = [], array $toUser = []): array
    {
        $db = Database::init();

        if (!$toRole && !$toUser) {
            $userIds = $db->select('admin_user', 'id', ['status' => 1]) ?: [];
        } else {
            $userIds = $toUser ? array_map('intval', $toUser) : [];
            if ($toRole) {
                $roleUserIds = $db->select('admin_user', 'id', ['role_id' => $toRole, 'status' => 1]) ?: [];
                if ($roleUserIds) {
                    $userIds = array_values(array_unique(array_merge($roleUserIds, $userIds)));
                }
            }
        }

        $db->insert('admin_notice', [
            'user_ids' => json_encode($userIds),
            'title' => $title,
            'content' => $content,
        ]);

        return $db->id() ? $userIds : [];
    }

    /**
     * Get a list of notifications based on user_id and role_id
     * 
     * @param int $userId 
     * @param int $roleId 
     * @param int $page 
     * @param int $limit 
     * @return array 
     * @throws Exception 
     * @throws ErrorException 
     * @throws ExceptionInvalidArgumentException 
     * @throws CacheException 
     */
    public static function getNoticeList(int $userId, int $roleId, int $page = 1, int $limit = 4): array
    {
        $data = [
            'count' => 0,
            'list' => [],
        ];

        $start = $page > 1 ? ($page - 1) * $limit : 0;

        $db = Database::init();
        $where = $db::raw('WHERE JSON_CONTAINS(<user_ids>, \'' . $userId . '\') ORDER BY <id> DESC LIMIT 40');

        $list = self::getCacheData('admin_notice', null, 300, $where);
        if ($list) {
            $data['count'] = count($list);
            foreach (array_slice($list, $start, $limit)  as $_info) {
                $data['list'][] = [
                    'id' => $_info['id'],
                    'title' => $_info['title'],
                    'create_time' => strtotime($_info['create_time']),
                    'has_content' => $_info['content'] ? true : false,
                ];
            }
        }

        return $data;
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
        $count = $db->count($table, '*', $search);

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
            $where = $search;
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
            $cache6 = Cache::psr6();
            $cacheKeys = [
                'alight.' . $table,
                'alight.' . $table . '.' . $db->id()
            ];
            $cache6->deleteItems($cacheKeys);
            $cache6->invalidateTags([
                'alight.' . $table . '.list'
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
            $cache6 = Cache::psr6();
            $cacheKeys = [
                'alight.' . $table,
                'alight.' . $table . '.' . $id,
            ];
            $cache6->deleteItems($cacheKeys);
            $cache6->invalidateTags([
                'alight.' . $table . '.list'
            ]);
        }

        return $result->rowCount() ? $id : (!$db->error ? $id : 0);
    }

    /**
     * Update multiple data for Form
     * 
     * @param string $table 
     * @param array $data 
     * @param array $ids 
     * @return array 
     * @throws Exception 
     * @throws InvalidArgumentException 
     * @throws PDOException 
     * @throws ErrorException 
     * @throws ExceptionInvalidArgumentException 
     * @throws ExceptionInvalidArgumentException 
     */
    public static function formUpdateMultiple(string $table, array $data, array $ids): array
    {
        $db = Database::init();
        $result = $db->update($table, $data, ['id' => $ids]);

        if ($result->rowCount()) {
            $cache6 = Cache::psr6();
            $cacheKeys = ['alight.' . $table];
            foreach ($ids as $_id) {
                $cacheKeys[] = 'alight.' . $table . '.' . $_id;
            }
            $cache6->deleteItems($cacheKeys);
            $cache6->invalidateTags([
                'alight.' . $table . '.list'
            ]);
        }

        return $result->rowCount() ? $ids : (!$db->error ? $ids : []);
    }
}
