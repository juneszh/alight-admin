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
        'locale' => '', // Whether to enable locale language (default: en_US)
        'menu' => '', // Menu configuration file
        'console' => '', // Console configuration file
        'remember' => 86400, // Login renewal cycle
        'sso' => true, // Single sign-on
        'cravatar' => false, // Whether to use Cravatar instead of Libravatar (Gravatar) to display avatar
        'errorPageHandler' => [Controller::class, 'result'], // Override error page handler
        'noticeCallback' => null, // Callback operation after successful notification
        'dev' => false, // For 'npm run dev' set to true, or set a url instead of 'http://localhost:5173'
    ];

    /**
     * Merge default configuration and user configuration
     * 
     * @param string $key 
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
