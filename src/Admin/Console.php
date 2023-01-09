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

use Alight\App;
use Exception;
use ErrorException;
use Symfony\Component\Cache\Exception\InvalidArgumentException;
use InvalidArgumentException as GlobalInvalidArgumentException;
use PDOException;

class Console
{
    public static array $config = [];
    private static int $index = 0;

    /**
     * Create a chart
     *
     * @param string $component
     * @return ConsoleChart
     */
    public static function chart(string $component): ConsoleChart
    {
        ++self::$index;
        self::$config[self::$index] = [
            'component' => ucfirst($component),
            'grid' => ['span' => 24],
        ];

        return new ConsoleChart(self::$index);
    }

    /**
     * Get full console configuration based on user role
     * 
     * @param int $userId 
     * @return array 
     * @throws Exception 
     * @throws ErrorException 
     * @throws InvalidArgumentException 
     * @throws InvalidArgumentException 
     * @throws GlobalInvalidArgumentException 
     * @throws PDOException 
     */
    public static function build(int $userId): array
    {
        $userInfo = Model::getUserInfo($userId);

        $avatarDomain = Config::get('cravatar') ? 'cravatar.cn' : 'www.gravatar.com';
        $avatar = 'https://' . $avatarDomain . '/avatar/' . ($userInfo['email'] ? md5(strtolower(trim($userInfo['email']))) : '') . '?s=100&d=mp';

        $roleEnum = Model::getRoleEnum(['id' => $userInfo['role_id']]);
        $roleName = $roleEnum ? reset($roleEnum)['name'] : '';


        $consoleFile = App::root(Config::get('console'));
        if ($consoleFile && file_exists($consoleFile)) {
            require $consoleFile;
        }

        $chart = self::$config;
        if ($chart) {
            foreach ($chart as $k => $v) {
                if (!$v || (isset($v['role']) && $v['role'] && !in_array($userInfo['role_id'], $v['role']))) {
                    unset($chart[$k]);
                }
            }
        }

        return [
            'user' => [
                'id' => $userId,
                'avatar' => $avatar,
                'account' => $userInfo['account'],
                'name' => $userInfo['name'],
                'role' => $roleName,
            ],
            'chart' => $chart
        ];
    }
}
