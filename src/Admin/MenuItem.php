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

use Alight\Admin;
use Exception;

class MenuItem
{
    private int $index;
    private int $subIndex;

    /**
     * Define the configuration index
     * 
     * @param int $index 
     * @param int $subIndex 
     * @return $this  
     */
    public function __construct(int $index, int $subIndex)
    {
        $this->index = $index;
        $this->subIndex = $subIndex;
        return $this;
    }

    /**
     * Set click action
     *
     * @param string $value Menu::ACTION_*
     * @return $this  
     * @throws Exception 
     */
    public function action(string $value)
    {
        if ($this->subIndex) {
            Menu::$config[$this->index]['sub'][$this->subIndex][__FUNCTION__] = $value;
        } else {
            Menu::$config[$this->index][__FUNCTION__] = $value;
        }
        return $this;
    }

    /**
     * Set icon
     *
     * @param string $value
     * @return $this 
     * 
     * @see https://ant.design/components/icon/
     * @deprecated Only used in the built-in menu, because more icons must be imported in react
     */
    public function icon(string $value)
    {
        if ($this->subIndex) {
            Menu::$config[$this->index]['sub'][$this->subIndex][__FUNCTION__] = $value;
        } else {
            Menu::$config[$this->index][__FUNCTION__] = $value;
        }
        return $this;
    }

    /**
     * Which role has permission to view
     *
     * @param array $roleValues
     * @return $this 
     */
    public function role(array $roleValues)
    {
        if ($this->subIndex) {
            Menu::$config[$this->index]['sub'][$this->subIndex][__FUNCTION__] = $roleValues;
        } else {
            Menu::$config[$this->index][__FUNCTION__] = $roleValues;
        }
        return $this;
    }

    /**
     * Set url
     * 
     * @param string $value 
     * @return $this  
     */
    public function url(string $value)
    {
        if ($this->subIndex) {
            Menu::$config[$this->index]['sub'][$this->subIndex][__FUNCTION__] = Admin::url($value);
        } else {
            Menu::$config[$this->index][__FUNCTION__] = Admin::url($value);
        }
        return $this;
    }
}
