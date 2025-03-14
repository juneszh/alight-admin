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

namespace Alight;

use Alight\Admin\Auth;
use Alight\Admin\Config as AdminConfig;
use Alight\Admin\Controller;
use Alight\Admin\Model;
use Composer\InstalledVersions;
use Exception;
use PharData;
use Symfony\Component\VarExporter\VarExporter;

class Admin
{
    private const PACKAGE = 'juneszh/alight-admin';
    private const PUBLIC = 'public/alight-admin';
    /**
     * Admin built-in routes
     * 
     * @param string $configKey 
     */
    public static function start(string $configKey = '')
    {
        Route::beforeHandler([AdminConfig::class, 'init'], [$configKey]);
        Route::authHandler([Auth::class, 'verify']);
        Route::setAnyMethods(['GET', 'POST']);

        AdminConfig::init($configKey);
        Route::group(AdminConfig::get('path'));

        Route::get('', [Controller::class, 'index'])->auth();
        Route::any('login', [Controller::class, 'login'])->cache(0);
        Route::get('logout', [Controller::class, 'logout'])->cache(0);
        Route::get('captcha', [Controller::class, 'captcha'])->cache(0);
        Route::get('console', [Controller::class, 'console'])->auth();
        Route::get('console/user', [Controller::class, 'consoleUser'])->auth();
        Route::get('console/notice/list', [Controller::class, 'consoleNoticeList'])->auth()->cache(60, 0);
        Route::get('console/notice/form', [Controller::class, 'consoleNoticeForm'])->auth()->cache(86400, 0);
        Route::post('console/notice/read', [Controller::class, 'consoleNoticeRead'])->auth();
        Route::get('role/table', [Controller::class, 'roleTable'])->auth();
        Route::any('role/form', [Controller::class, 'roleForm'])->auth();
        Route::get('user/table', [Controller::class, 'userTable'])->auth();
        Route::any('user/form', [Controller::class, 'userForm'])->auth();
        Route::post('upload', [Controller::class, 'upload'])->auth();
        Route::get('result/{status}[/{message}]', [Controller::class, 'result']);
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
            $path = AdminConfig::get('path');
            $url = trim($url, '/');
            return ($path ? '/' . $path : '') . ($url ? '/' . $url : '');
        }
    }


    /**
     * Transform configuration to front-end by the javascript
     * 
     * @param string $page 
     * @param array $config 
     * @return string 
     */
    public static function globalScript(string $page, array $config = [])
    {
        $admin = AdminConfig::get();

        $global = [
            'title' => $admin['title'],
            'locale' => $admin['locale'],
            'path' => self::url(),
            'page' => $page,
            'config' => $config,
        ];

        $return = '<script>';
        $return .= 'window.$global=' . json_encode($global, JSON_UNESCAPED_UNICODE);
        $return .= '</script>' . PHP_EOL;

        $indent = '    ';

        if ($admin['dev']) {
            $devSrc = is_string($admin['dev']) ? $admin['dev'] : 'http://localhost:5173';
            $return .= $indent . '<script type="module">import RefreshRuntime from "' . $devSrc . '/alight-admin/@react-refresh";RefreshRuntime.injectIntoGlobalHook(window);window.$RefreshReg$ = () => {};window.$RefreshSig$ = () => (type) => type;window.__vite_plugin_react_preamble_installed__ = true;</script>' . PHP_EOL;
            $return .= $indent . '<script type="module" src="' . $devSrc . '/alight-admin/@vite/client"></script>' . PHP_EOL;
            $return .= $indent . '<script type="module" src="' . $devSrc . '/alight-admin/src/main.jsx"></script>' . PHP_EOL;
        } else {
            $manifest = App::root(self::PUBLIC . '/.vite/manifest.json');
            if (file_exists($manifest)) {
                $manifestData = json_decode(file_get_contents($manifest), true);
                if (isset($manifestData['index.html']['file'])) {
                    $return .= $indent . '<script type="module" crossorigin src="/alight-admin/' . $manifestData['index.html']['file'] . '"></script>' . PHP_EOL;
                }
                if (isset($manifestData['index.html']['css'])) {
                    foreach ($manifestData['index.html']['css'] as $_css) {
                        $return .= $indent . '<link rel="stylesheet" crossorigin href="/alight-admin/' . $_css . '">' . PHP_EOL;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * Initialize the configuration and database tables
     */
    public static function install()
    {
        if (PHP_SAPI !== 'cli') {
            throw new Exception('PHP-CLI required.');
        }

        self::insertConfig();
        self::createTable();
    }

    /**
     * Insert the admin sample configuration to app.php
     */
    private static function insertConfig()
    {
        exec('cp -rn ' . self::path() . '/example/config/* ' . App::root('config/'));

        $configData = Config::get();
        $configFile = App::root(Config::FILE);
        $routeAdmin = 'config/route/admin.php';
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
            file_put_contents($configFile, '<?php' . PHP_EOL . 'return ' . VarExporter::export($configData) . ';', LOCK_EX);
        }
    }

    /**
     * Create the database tables for admin, and return the first Administrator account
     */
    private static function createTable()
    {
        $alightAccount = 'alight';
        $alightPassword = Utility::randomHex(16);

        $db = Database::init();
        $roleCreate = $db->create('admin_role', [
            'id' => [
                'TINYINT',
                'UNSIGNED',
                'NOT NULL',
                'AUTO_INCREMENT',
            ],
            'name' => [
                'VARCHAR(32)',
                'NOT NULL',
                'DEFAULT \'\'',
            ],
            'create_time' => [
                'TIMESTAMP',
                'NOT NULL',
                'DEFAULT CURRENT_TIMESTAMP',
            ],
            'PRIMARY KEY (<id>)',
        ], [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
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
                'SMALLINT',
                'UNSIGNED',
                'NOT NULL',
                'AUTO_INCREMENT',
            ],
            'account' => [
                'VARCHAR(32)',
                'NOT NULL',
                'DEFAULT \'\'',
            ],
            'password' => [
                'VARCHAR(255)',
                'NOT NULL',
                'DEFAULT \'\'',
            ],
            'name' => [
                'VARCHAR(32)',
                'NOT NULL',
                'DEFAULT \'\'',
            ],
            'email' => [
                'VARCHAR(255)',
                'NOT NULL',
                'DEFAULT \'\'',
            ],
            'role_id' => [
                'TINYINT',
                'UNSIGNED',
                'NOT NULL',
                'DEFAULT \'0\'',
            ],
            'status' => [
                'TINYINT',
                'UNSIGNED',
                'NOT NULL',
                'DEFAULT \'1\'',
            ],
            'auth_key' => [
                'VARCHAR(32)',
                'NOT NULL',
                'DEFAULT \'\'',
            ],
            'create_time' => [
                'TIMESTAMP',
                'NOT NULL',
                'DEFAULT CURRENT_TIMESTAMP',
            ],
            'PRIMARY KEY (<id>)',
            'UNIQUE INDEX <account> (<account>)',
            'INDEX <auth_key> (<auth_key>)',
        ], [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
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
                        'auth_key' => Utility::randomHex(),
                    ],
                ]);

                if ($db->id()) {
                    echo 'Admin Account: ', $alightAccount, PHP_EOL, 'Admin Password: ', $alightPassword, PHP_EOL;
                }
            }
        }

        $db->create('admin_notice', [
            'id' => [
                'INT',
                'UNSIGNED',
                'NOT NULL',
                'AUTO_INCREMENT',
            ],
            'unique_id' => [
                'VARCHAR(32)',
                'NOT NULL',
                'DEFAULT \'\'',
            ],
            'user_ids' => [
                'JSON',
                'NOT NULL',
            ],
            'title' => [
                'VARCHAR(255)',
                'NOT NULL',
                'DEFAULT \'\'',
            ],
            'content' => [
                'VARCHAR(1000)',
                'NOT NULL',
                'DEFAULT \'\'',
            ],
            'interval' => [
                'INT',
                'UNSIGNED',
                'NOT NULL',
                'DEFAULT \'0\'',
            ],
            'create_time' => [
                'TIMESTAMP',
                'NOT NULL',
                'DEFAULT CURRENT_TIMESTAMP',
            ],
            'PRIMARY KEY (<id>)',
            'INDEX <unique_id_create_time> (<unique_id>, <create_time>)',
        ], [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
        ]);

        $db->create('admin_notice_read', [
            'id' => [
                'INT',
                'UNSIGNED',
                'NOT NULL',
                'AUTO_INCREMENT',
            ],
            'user_id' => [
                'SMALLINT',
                'UNSIGNED',
                'NOT NULL',
                'DEFAULT \'0\'',
            ],
            'notice_id' => [
                'INT',
                'UNSIGNED',
                'NOT NULL',
                'DEFAULT \'0\'',
            ],
            'create_time' => [
                'TIMESTAMP',
                'NOT NULL',
                'DEFAULT CURRENT_TIMESTAMP',
            ],
            'PRIMARY KEY (<id>)',
            'UNIQUE INDEX <user_id_notice_id> (<user_id>, <notice_id>)',
        ], [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
        ]);
    }

    /** 
     * Download the front-end resources from github releases and override
     */
    public static function download()
    {
        if (PHP_SAPI !== 'cli') {
            throw new Exception('PHP-CLI required.');
        }

        $version = self::version();

        if ($version[0] !== 'v') {
            echo 'Unable to download about version: ', $version, PHP_EOL;
        } else {
            $url = 'https://github.com/' . self::PACKAGE . '/releases/download/' . $version . '/alight-admin.tar.gz';
            $storagePath = Config::get('app', 'storagePath') ?: 'storage';
            $dir = App::root($storagePath . '/download/');
            $file = $dir . '/alight-admin.tar.gz';

            if (!is_dir($dir) && !@mkdir($dir, 0777, true)) {
                throw new Exception('Failed to create download directory.');
            } else {
                exec('rm -rf ' . $dir . '/*');
            }

            exec('wget -O ' . $file . ' ' . $url);

            if (file_exists($file)) {
                $phar = new PharData($file);
                $phar->decompress();
                $result = $phar->extractTo($dir);

                if ($result) {
                    self::publish(App::root($storagePath . '/download/dist'));
                    unlink($file);
                    unlink(substr($file, 0, -3));
                }
            }
        }
    }

    /**
     * Get package version from composer
     * 
     * @return string|null 
     */
    public static function version(): ?string
    {
        return InstalledVersions::getPrettyVersion(self::PACKAGE);
    }


    /**
     * Get package install path from composer
     * 
     * @return null|string 
     */
    public static function path(): ?string
    {
        return InstalledVersions::getInstallPath(self::PACKAGE);
    }

    /**
     * Move the dist files to public
     * 
     * @param $source 
     */
    public static function publish($source)
    {
        if (!is_string($source)) {
            $source = self::path() . '/resource/dist';
        }
        $dest = App::root(self::PUBLIC);
        exec('rm -rf ' . $dest . ' && rm -f ' . $source . '/index.html && mv ' . $source . ' ' . $dest);
    }

    /**
     * Add a notice via callback
     * 
     * @param string $title 
     * @param string $content 
     * @param array $toRole 
     * @param array $toUser 
     * @param int $interval 
     * @param null|string $uniqueId 
     */
    public static function notice(string $title, string $content = '', array $toRole = [], array $toUser = [], int $interval = 60, ?string $uniqueId = null)
    {
        $userIds = Model::addNotice($title, $content, $toRole, $toUser, $interval, $uniqueId);
        if ($userIds) {
            $callback = AdminConfig::get('noticeCallback');
            if (is_callable($callback)) {
                $callback(['to_role' => $toRole, 'to_user' => $toUser, 'title' => $title, 'content' => $content, 'user_ids' => $userIds]);
            }
        }
    }
}
