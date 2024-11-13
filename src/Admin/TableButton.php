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

class TableButton
{
    private int $index;

    /**
     * Define the configuration index
     * 
     * @param int $index 
     * @param string $form 
     * @return $this 
     */
    public function __construct(int $index, string $form)
    {
        $this->index = $index;
        
        $defaultUrl = '';
        if ($_SERVER['REQUEST_URI'] ?? '') {
            $defaultUrl = substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')) . '/form';
        }
        
        Table::$config['button'][$this->index] = [
            'form' => $form,
            'title' => $form,
            'url' => $defaultUrl ?: Admin::url(),
            'action' => 'form',
            'place' => '_column',
        ];

        return $this;
    }

    /**
     * Set click action
     *
     * @param string $value Table::ACTION_*
     * @return TableButton
     */
    public function action(string $value)
    {
        Table::$config['button'][$this->index][__FUNCTION__] = $value;
        return $this;
    }

    /**
     * Put in the batch bar
     * 
     * @return $this 
     */
    public function batch()
    {
        Table::$config['button'][$this->index]['place'] = '_' . __FUNCTION__;
        Table::$config['button'][$this->index]['action'] = 'confirm';
        return $this;
    }

    /**
     * Set color
     *
     * @param string $color Table::COLOR_* or css format color
     * @return $this 
     */
    public function color(string $color)
    {
        Table::$config['button'][$this->index][__FUNCTION__] = $color;
        return $this;
    }

    /**
     * Put in the specified column (Create _column column by default)
     * 
     * @param string $columnKey 
     * @return $this 
     */
    public function column(string $columnKey = '_column')
    {
        if ($columnKey) {
            if ($columnKey !== '_column') {
                $columnKey = '_column_' . $columnKey;
            }
            Table::$config['button'][$this->index]['place'] = $columnKey;
        }
        return $this;
    }

    /**
     * Put in the specified expand column (Create _expand column by default)
     * 
     * @param string $columnKey 
     * @return $this 
     */
    public function expand(string $columnKey = '_expand')
    {
        if ($columnKey) {
            if ($columnKey !== '_expand') {
                $columnKey = '_expand_' . $columnKey;
            }
            Table::$config['button'][$this->index]['place'] = $columnKey;
        }
        return $this;
    }

    /**
     * Set display conditions
     *
     * @param array $keyValues 
     * @return $this 
     */
    public function if(array $keyValues)
    {
        Table::$config['button'][$this->index][__FUNCTION__] = $keyValues;
        return $this;
    }

    /**
     * Set request parameters
     *
     * @param array $keyValues Use {{column_key}} for column data
     * @return TableButton
     */
    public function param(array $keyValues)
    {
        Table::$config['button'][$this->index][__FUNCTION__] = $keyValues;
        return $this;
    }

    /**
     * Which role has permission to view
     *
     * @param array $roleValues
     * @return TableButton
     */
    public function role(array $roleValues)
    {
        Table::$config['button'][$this->index][__FUNCTION__] = $roleValues;
        return $this;
    }

    /**
     * Set title
     * 
     * @param string $value 
     * @return $this 
     */
    public function title(string $value)
    {
        Table::$config['button'][$this->index][__FUNCTION__] = $value;
        if (substr($value, 0, 1) === ':') {
            Table::$config['button'][$this->index]['locale'] = true;
        }
        return $this;
    }

    /**
     * Put in the toolbar
     * 
     * @return $this 
     */
    public function toolbar()
    {
        Table::$config['button'][$this->index]['place'] = '_' . __FUNCTION__;
        return $this;
    }

    /**
     * Set variant
     *
     * @param string $value Table::VARIANT_*
     * @return TableButton
     * 
     * @see https://ant.design/components/button/
     */
    public function variant(string $value)
    {
        Table::$config['button'][$this->index][__FUNCTION__] = $value;
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
        Table::$config['button'][$this->index][__FUNCTION__] = Admin::url($value);
        return $this;
    }
}
