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

namespace Alight;

use Alight\Admin\Auth;
use Alight\Admin\Config as AdminConfig;
use Alight\Admin\Controller;
use Composer\InstalledVersions;
use Exception;
use Symfony\Component\VarExporter\Exception\ExceptionInterface;
use InvalidArgumentException;
use PDOException;
use PharData;
use Symfony\Component\VarExporter\VarExporter;

class Admin
{

    /**
     * Admin built-in routes
     * 
     * @param string $configKey 
     */
    public static function route(string $configKey = '')
    {
        Route::beforeHandler([AdminConfig::class, 'init'], [$configKey]);
        Route::authHandler([Auth::class, 'verify']);
        Route::setAnyMethods(['GET', 'POST']);
        Route::group(AdminConfig::get('path'));

        Route::get('', [Controller::class, 'index'])->auth();
        Route::any('login', [Controller::class, 'login'])->cache(0);
        Route::get('logout', [Controller::class, 'logout'])->cache(0);
        Route::get('captcha', [Controller::class, 'captcha'])->cache(0);
        Route::get('console', [Controller::class, 'console'])->auth();
        Route::get('error/{status:\d+}', [Controller::class, 'error']);
        Route::get('role/table', [Controller::class, 'roleTable'])->auth();
        Route::any('role/form', [Controller::class, 'roleForm'])->auth();
        Route::get('user/table', [Controller::class, 'userTable'])->auth();
        Route::any('user/form', [Controller::class, 'userForm'])->auth();
        Route::post('upload', [Controller::class, 'upload'])->auth();
    }

    /**
     * Prefix the admin URL
     * 
     * @param string $url 
     * @return string 
     */
    public static function url(string $url = '')
    {
        if (substr($url, 0, 4) === 'http') {
            return $url;
        } else {
            $url = trim($url, '/');
            return '/' . trim(AdminConfig::get('path'), '/') . ($url ? '/' . $url : '');
        }
    }


    /**
     * Transform configuration to front-end by the javascript
     * 
     * @param string $component 
     * @param array $config 
     * @return string 
     */
    public static function globalScript(string $component, array $config = [])
    {
        $admin = AdminConfig::get();

        $global = [
            'title' => $admin['title'],
            'locale' => $admin['locale'],
            'path' => self::url(),
            'component' => $component,
            'config' => $config,
        ];

        $return = '<script>';
        $return .= 'window.$global=' . json_encode($global, JSON_UNESCAPED_UNICODE);
        $return .= '</script>';

        return $return;
    }

    /**
     * Initialize the configuration and database tables
     * 
     * @throws Exception 
     * @throws ExceptionInterface 
     * @throws InvalidArgumentException 
     * @throws PDOException 
     */
    public static function install()
    {
        if (PHP_SAPI !== 'cli') {
            throw new Exception('Php-cli mode required.');
        }

        $configFile = realpath('config/app.php');
        if (!$configFile) {
            throw new Exception('Missing configuration file: config/app.php');
        } else {
            Config::init($configFile);
            self::insertConfig();
            self::createTable();
        }
    }

    /**
     * Insert the admin sample configuration to app.php
     * 
     * @throws ExceptionInterface 
     */
    private static function insertConfig()
    {
        $configData = require Config::$configFile;
        if (is_array($configData)) {
            $routeAdmin = 'config/routes/admin.php';
            if ($configData['route']) {
                $configRoute = is_string($configData['route']) ? [$configData['route']] : $configData['route'];
                $associative = false;
                $insert = false;
                foreach ($configRoute as $value) {
                    if (is_string($value)) {
                        if (strpos($value, $routeAdmin) !== false) {
                            $insert = true;
                            break;
                        }
                    } elseif (is_array($value)) {
                        foreach ($value as $value2) {
                            if (strpos($value2, $routeAdmin) !== false) {
                                $insert = true;
                                break;
                                break;
                            }
                        }
                    }
                }
                if (!$insert) {
                    foreach ($configRoute as $key => $value) {
                        if (is_string($key)) {
                            $associative = true;
                            if ($key === '*') {
                                if (is_string($value)) {
                                    $configRoute[$key] = [$value];
                                }
                            }
                        }
                    }
                    if ($associative) {
                        if (isset($configRoute['*'])) {
                            $configRoute['*'][] = $routeAdmin;
                        } else {
                            $configRoute['*'] = $routeAdmin;
                        }
                    } else {
                        $configRoute[] = $routeAdmin;
                    }
                }
            } else {
                $configRoute = [$routeAdmin];
            }
            $configData['route'] = $configRoute;

            if (!isset($configData['admin']) || !$configData['admin']) {
                $configData['admin'] = array_intersect_key(AdminConfig::$config, ['title' => 1, 'path' => 1, 'locale' => 1]);
            }

            if (!isset($configData['admin']['menu']) || !$configData['admin']['menu']) {
                $configData['admin']['menu'] = 'config/admin/menu.php';
            }

            if (!isset($configData['admin']['console']) || !$configData['admin']['console']) {
                $configData['admin']['console'] = 'config/admin/console.php';
            }

            if ($configData) {
                file_put_contents(Config::$configFile, '<?php' . PHP_EOL . 'return ' . VarExporter::export($configData) . ';', LOCK_EX);
            }
        }
    }

    /**
     * Create the database tables for admin, and return the first Administrator account
     * 
     * @throws Exception 
     * @throws InvalidArgumentException 
     * @throws PDOException 
     */
    private static function createTable()
    {
        $alightAccount = 'alight';
        $alightPassword = Utility::uid(16);

        $db = Database::init();
        $roleCreate = $db->create('admin_role', [
            'id' => [
                "TINYINT",
                "UNSIGNED",
                "NOT NULL",
                "AUTO_INCREMENT",
            ],
            'name' => [
                "VARCHAR(32)",
                "NOT NULL",
                "DEFAULT ''",
            ],
            'create_time' => [
                "TIMESTAMP",
                "NOT NULL",
                "DEFAULT CURRENT_TIMESTAMP",
            ],
            'PRIMARY KEY (<id>)',
        ], [
            "ENGINE" => "InnoDB",
            "DEFAULT CHARSET" => "utf8mb4",
        ]);

        if ($roleCreate) {
            if ($db->has('admin_role', ['id' => 1]) === false) {
                $db->insert('admin_role', [
                    [
                        'id' => 1,
                        'name' => 'Administrator',
                    ],
                ]);
            }
        }

        $userCreate = $db->create('admin_user', [
            'id' => [
                "SMALLINT",
                "UNSIGNED",
                "NOT NULL",
                "AUTO_INCREMENT",
            ],
            'account' => [
                "VARCHAR(32)",
                "NOT NULL",
                "DEFAULT ''",
            ],
            'password' => [
                "VARCHAR(255)",
                "NOT NULL",
                "DEFAULT ''",
            ],
            'name' => [
                "VARCHAR(32)",
                "NOT NULL",
                "DEFAULT ''",
            ],
            'email' => [
                "VARCHAR(255)",
                "NOT NULL",
                "DEFAULT ''",
            ],
            'role_id' => [
                "TINYINT",
                "UNSIGNED",
                "NOT NULL",
                "DEFAULT '0'",
            ],
            'status' => [
                "TINYINT(1)",
                "UNSIGNED",
                "NOT NULL",
                "DEFAULT '1'",
            ],
            'auth_hash' => [
                "VARCHAR(32)",
                "NOT NULL",
                "DEFAULT ''",
            ],
            'create_time' => [
                "TIMESTAMP",
                "NOT NULL",
                "DEFAULT CURRENT_TIMESTAMP",
            ],
            'PRIMARY KEY (<id>)',
            'UNIQUE INDEX <account> (<account>)',
            'INDEX <auth_hash> (<auth_hash>)',
        ], [
            "ENGINE" => "InnoDB",
            "DEFAULT CHARSET" => "utf8mb4",
        ]);

        if ($userCreate) {
            if ($db->has('admin_user', ['id' => 1]) === false) {
                $db->insert('admin_user', [
                    [
                        'id' => 1,
                        'account' => $alightAccount,
                        'password' => password_hash($alightPassword, PASSWORD_DEFAULT),
                        'name' => 'Alight',
                        'role_id' => 1,
                        'auth_hash' => Utility::uid(),
                    ],
                ]);

                if ($db->id()) {
                    echo 'Admin Account: ', $alightAccount, PHP_EOL, 'Admin Password: ', $alightPassword, PHP_EOL;
                }
            }
        }

        $db->create('admin_log', [
            'id' => [
                "INT",
                "UNSIGNED",
                "NOT NULL",
                "AUTO_INCREMENT",
            ],
            'user_id' => [
                "SMALLINT",
                "UNSIGNED",
                "NOT NULL",
                "DEFAULT '0'",
            ],
            'date' => [
                "DATE",
                "NOT NULL",
                "DEFAULT '2022-08-02'",
            ],
            'hour' => [
                "TINYINT",
                "UNSIGNED",
                "NOT NULL",
                "DEFAULT '0'",
            ],
            'view' => [
                "SMALLINT",
                "UNSIGNED",
                "NOT NULL",
                "DEFAULT '0'",
            ],
            'edit' => [
                "SMALLINT",
                "UNSIGNED",
                "NOT NULL",
                "DEFAULT '0'",
            ],
            'ip' => [
                "VARCHAR(45)",
                "NOT NULL",
                "DEFAULT ''",
            ],
            'create_time' => [
                "TIMESTAMP",
                "NOT NULL",
                "DEFAULT CURRENT_TIMESTAMP",
            ],
            'PRIMARY KEY (<id>)',
            'UNIQUE INDEX <user_id_date_hour> (<user_id>, <date>, <hour>)',
        ], [
            "ENGINE" => "InnoDB",
            "DEFAULT CHARSET" => "utf8mb4",
        ]);
    }

    /** 
     * Download the front-end resources from github releases and override
     */
    public static function download()
    {
        if (PHP_SAPI !== 'cli') {
            throw new Exception('Php-cli mode required.');
        }

        $package = 'juneszh/alight-admin';
        $version = InstalledVersions::getPrettyVersion($package);
        echo $version . PHP_EOL;

        if ($version[0] !== 'v') {
            echo 'Unable to download about version: ', $version, PHP_EOL;
        } else {
            $url = 'https://github.com/' . $package . '/releases/download/' . $version . '/build.tar.gz';
            $dir = App::root('storage/admin/');
            $file = $dir . '/build.tar.gz';

            if (!is_dir($dir)) {
                if (!mkdir($dir, 0777, true)) {
                    throw new Exception('Failed to create download directory.');
                }
            } else {
                exec('rm -rf ' . $dir . '/*');
            }

            if (copy($url, $file)) {
                $phar = new PharData($file);
                $phar->decompress();
                $phar->extractTo($dir);
            }
        }
    }
}
