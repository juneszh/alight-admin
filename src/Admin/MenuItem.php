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

class MenuItem
{
    private int $index;
    private int $subIndex;

    /**
     * Define the configuration index
     * 
     * @param int $index 
     * @param int $subIndex 
     * @param string $title 
     * @return $this  
     */
    public function __construct(int $index, int $subIndex, string $title)
    {
        $this->index = $index;
        $this->subIndex = $subIndex;

        if ($this->subIndex) {
            Menu::$config[$this->index]['sub'][$this->subIndex] = [
                'title' => $title,
                'locale' =>  substr($title, 0, 1) === ':' ? true : false,
            ];
        } else {
            Menu::$config[$this->index] = [
                'title' => $title
            ];
        }

        return $this;
    }

    /**
     * Common setting config values
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    private function config(string $key, $value)
    {
        if ($this->subIndex) {
            Menu::$config[$this->index]['sub'][$this->subIndex][$key] = $value;
        } else {
            Menu::$config[$this->index][$key] = $value;
        }
    }

    /**
     * Set click action
     *
     * @param string $value Menu::ACTION_*
     * @return $this
     */
    public function action(string $value)
    {
        $this->config(__FUNCTION__, $value);
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
        $this->config(__FUNCTION__, $value);
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
        $this->config(__FUNCTION__, $roleValues);
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
        $this->config(__FUNCTION__, Admin::url($value));
        return $this;
    }
}
