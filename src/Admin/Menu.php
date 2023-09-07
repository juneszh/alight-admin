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
use ErrorException;
use Exception;
use Symfony\Component\Cache\Exception\InvalidArgumentException;

class Menu
{
    public static array $config = [];
    private static int $index = 0;
    private static int $subIndex = 0;

    public const
        ACTION_IFRAME = 'iframe',
        ACTION_FORM = 'form',
        ACTION_PAGE = 'page',
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
        self::$config[self::$index] = [
            'title' => $title
        ];

        return new MenuItem(self::$index, self::$subIndex);
    }

    /**
     * Create a submenu item
     * 
     * @param string $title 
     * @return MenuItem 
     * @throws Exception 
     */
    public static function subItem(string $title): MenuItem
    {
        if (!self::$index) {
            throw new Exception('Missing parent item definition.');
        }

        ++self::$subIndex;
        self::$config[self::$index]['sub'][self::$subIndex] = [
            'title' => $title,
            'locale' => $title[0] === ':' ? true : false,
        ];

        return new MenuItem(self::$index, self::$subIndex);
    }

    /**
     * Get full menu configuration based on user role
     * 
     * @param int $roleId 
     * @return array 
     * @throws Exception 
     * @throws ErrorException 
     * @throws InvalidArgumentException 
     * @throws InvalidArgumentException 
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
