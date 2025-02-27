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

use Alight\App;
use Exception;

class Menu
{
    public static array $config = [];
    private static int $index = 0;
    private static int $subIndex = 0;

    public const
        ACTION_IFRAME = 'iframe',
        ACTION_POPUP = 'popup',
        ACTION_REDIRECT = 'redirect';

    /**
     * Create a menu item
     *
     * @param string $title
     * @return MenuItem
     */
    public static function item(string $title): MenuItem
    {
        ++self::$index;
        self::$subIndex = 0;

        return new MenuItem(self::$index, self::$subIndex, $title);
    }

    /**
     * Create a submenu item
     * 
     * @param string $title 
     * @return MenuItem 
     */
    public static function subItem(string $title): MenuItem
    {
        if (!self::$index) {
            throw new Exception('Missing parent item definition.');
        }

        ++self::$subIndex;

        return new MenuItem(self::$index, self::$subIndex, $title);
    }

    /**
     * Get full menu configuration based on user role
     * 
     * @param int $roleId 
     * @return array 
     */
    public static function build(int $roleId): array
    {
        $menuFile = App::root(Config::get('menu'));
        if ($menuFile && file_exists($menuFile)) {
            require $menuFile;
        }

        $menu = self::$config;
        if ($menu) {
            foreach ($menu as $k => $v) {
                if (!$v || (isset($v['role']) && $v['role'] && !in_array($roleId, $v['role']))) {
                    unset($menu[$k]);
                } else {
                    if (isset($v['sub']) && $v['sub']) {
                        foreach ($v['sub'] as $sk => $sv) {
                            if (!$sv || (isset($sv['role']) && $sv['role'] && !in_array($roleId, $sv['role']))) {
                                unset($menu[$k]['sub'][$sk]);
                            }
                        }
                    }
                }
            }
        }

        return $menu;
    }
}
