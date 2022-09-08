<?php

declare(strict_types=1);

use Alight\Admin\Config;
use Alight\Admin\Menu;

Menu::item(Config::get('title'));
Menu::subItem(':console')->url('console')->icon('DashboardOutlined');
Menu::subItem(':role')->url('role/table')->icon('SafetyCertificateOutlined')->role([1]);
Menu::subItem(':user')->url('user/table')->icon('TeamOutlined')->role([1]);

// Menu::item('Customize');
// Menu::subItem('Submenu')->url('user/table');