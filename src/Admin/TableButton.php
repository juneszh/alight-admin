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

use Alight\Admin;
use Exception;

class TableButton
{
    private int $index;

    /**
     * Define the configuration index
     * 
     * @param int $index 
     * @return TableButton 
     */
    public function __construct(int $index)
    {
        $this->index = $index;
        return $this;
    }

    /**
     * Set title
     * 
     * @param string $value 
     * @return TableButton 
     */
    public function title(string $value): TableButton
    {
        Table::$config['button'][$this->index][__FUNCTION__] = $value;
        if ($value[0] === ':') {
            Table::$config['button'][$this->index]['locale'] = true;
        }
        return $this;
    }

    /**
     * Set url
     * 
     * @param string $value 
     * @return TableButton 
     */
    public function url(string $value): TableButton
    {
        Table::$config['button'][$this->index][__FUNCTION__] = Admin::url($value);
        return $this;
    }

    /**
     * Put in the toolbar
     * 
     * @return TableButton 
     */
    public function toolbar(): TableButton
    {
        Table::$config['button'][$this->index]['place'] = '_' . __FUNCTION__;
        return $this;
    }

    /**
     * Put in the batch bar
     * 
     * @return TableButton 
     */
    public function batch(): TableButton
    {
        Table::$config['button'][$this->index]['place'] = '_' . __FUNCTION__;
        return $this;
    }

    /**
     * Put in the specified column (Create _action column by default)
     * 
     * @param string $columnKey 
     * @return TableButton 
     */
    public function column(string $columnKey): TableButton
    {
        if ($columnKey) {
            Table::$config['button'][$this->index]['place'] = $columnKey;
        }
        return $this;
    }

    /**
     * Set click action
     *
     * @param string $value modal|confirm|submit|popup
     * @return TableButton
     */
    public function action(string $value): TableButton
    {
        if (!in_array($value, ['modal', 'confirm', 'submit', 'popup'])) {
            throw new Exception('$value must be a valid value');
        }

        Table::$config['button'][$this->index][__FUNCTION__] = $value;
        return $this;
    }


    /**
     * Set request parameters
     *
     * @param array $keyValues Use {{column_key}} for column data
     * @return TableButton
     */
    public function param(array $keyValues): TableButton
    {
        Table::$config['button'][$this->index][__FUNCTION__] = $keyValues;
        return $this;
    }

    /**
     * Set display conditions
     *
     * @param array $keyValues 
     * @return TableButton 
     */
    public function if(array $keyValues): TableButton
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
    public function role(array $roleValues): TableButton
    {
        Table::$config['button'][$this->index][__FUNCTION__] = $roleValues;
        return $this;
    }


    /**
     * Set type
     *
     * @param string $value default|primary|dashed|text|link
     * @return TableButton
     * 
     * @see https://ant.design/components/button/
     */
    public function type(string $value): TableButton
    {
        if (!in_array($value, ['default', 'primary', 'dashed', 'text', 'link'])) {
            throw new Exception('$value must be a valid value');
        }

        Table::$config['button'][$this->index][__FUNCTION__] = $value;
        return $this;
    }

    /**
     * Set danger
     * 
     * @return TableButton 
     */
    public function danger(): TableButton
    {
        Table::$config['button'][$this->index][__FUNCTION__] = true;
        return $this;
    }
}
