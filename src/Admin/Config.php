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

use Alight\Config as AlightConfig;
use Exception;

class Config
{
    public static array $config = [
        'title' => 'Alight Admin', // Admin panel title
        'path' => 'admin', // Admin panel access path
        'locale' => false, // Whether to enable locale language (built-in zh_CN)
        'menu' => '', // Menu configuration file
        'console' => '', // Console configuration file
        'remember' => 86400, // Login renewal cycle
        'cravatar' => false, // Whether to use Cravatar instead of Gravatar to display avatar
        'separator' => '|', // Join separator for convert array to string when storing database
        'errorPageHandler' => [Controller::class, 'result'], // Override error page handler
        'dev' => false, // Use the js from 'npm run dev' when set true
    ];

    /**
     * Merge default configuration and user configuration
     * 
     * @param string $key 
     * @throws Exception 
     */
    public static function init(string $key = '')
    {
        $config = AlightConfig::get('admin');
        if (!$config || !is_array($config)) {
            throw new Exception('Missing admin configuration.');
        }

        if (isset($config['title']) && !is_array($config['title'])) {
            $configAdmin = $config;
        } else {
            if ($key) {
                if (!isset($config[$key]) || !is_array($config[$key])) {
                    throw new Exception('Missing admin configuration about \'' . $key . '\'.');
                }
            } else {
                $key = key($config);
                if (!is_array($config[$key])) {
                    throw new Exception('Missing admin configuration.');
                }
            }
            $configAdmin = $config[$key];
        }

        self::$config = array_replace_recursive(self::$config, $configAdmin);

        AlightConfig::set('app', 'errorPageHandler', self::$config['errorPageHandler']);
    }

    /**
     * Get config values
     * 
     * @param null|string $option
     * @return mixed
     */
    public static function get(?string $option = null)
    {
        return $option ? (self::$config[$option] ?? null) : self::$config;
    }
}
