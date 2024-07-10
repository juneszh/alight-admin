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

class TableExpand
{
    private string $type;
    private string $key;

    /**
     * Define the configuration index
     * 
     * @param string $key 
     * @return $this 
     */
    public function __construct(string $type, string $key)
    {
        $this->type = $type;
        $this->key = $key;

        Table::$config[$this->type][$this->key] = [
            'title' => $this->key,
            'database' => ($this->type === 'column' && $this->key !== '_column'),
        ];

        return $this;
    }

    /**
     * Set text align
     *
     * @param string $direction Table::ALIGN_*
     * @return $this
     */
    public function align(string $direction)
    {
        Table::$config[$this->type][$this->key][__FUNCTION__] = $direction;
        return $this;
    }

    /**
     * Set copyable
     * 
     * @return $this 
     */
    public function copyable()
    {
        Table::$config[$this->type][$this->key][__FUNCTION__] = true;
        return $this;
    }

    /**
     * Set enum to replace value
     *
     * @param array $keyValues
     * @return $this
     * 
     * @example Basic [0=>'bad', 1=>'good']
     * @example Badge with status [0=>['text'=>'bad', 'status'=>'error'], 1=>['text'=>'good', 'status'=>'success']]
     * @example Badge with css color [0=>['text'=>'bad', 'color'=>'#f00'], 1=>['text'=>'good', 'color'=>'green']]
     * @see https://ant.design/components/badge/#Badge
     */
    public function enum(array $keyValues)
    {
        Table::$config[$this->type][$this->key][__FUNCTION__] = $keyValues;
        return $this;
    }

    /**
     * Set fixed 
     *
     * @param string $direction Table::FIXED_*
     * @return $this
     */
    public function fixed(string $direction)
    {
        Table::$config[$this->type][$this->key][__FUNCTION__] = $direction;
        return $this;
    }

    /**
     * Hide column
     *
     * @return $this
     */
    public function hide()
    {
        Table::$config[$this->type][$this->key][__FUNCTION__] = true;
        return $this;
    }

    /**
     * Set html
     * 
     * @return $this 
     */
    public function html()
    {
        Table::$config[$this->type][$this->key][__FUNCTION__] = true;
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
        Table::$config[$this->type][$this->key][__FUNCTION__] = $roleValues;
        return $this;
    }

    /**
     * Set sort order
     *
     * @param string $order Table::ORDER_*
     * @return $this
     */
    public function sort(string $initOrder = '')
    {
        Table::$config[$this->type][$this->key][__FUNCTION__] = $initOrder ? $initOrder : true;
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
        Table::$config[$this->type][$this->key][__FUNCTION__] = $value;
        if (substr($value, 0, 1) === ':') {
            Table::$config[$this->type][$this->key]['locale'] = true;
        }
        return $this;
    }

    /**
     * Set tooltip
     * 
     * @param string $value 
     * @return $this 
     */
    public function tooltip(string $value)
    {
        Table::$config[$this->type][$this->key][__FUNCTION__] = $value;
        return $this;
    }
    
    /**
     * Set field (ProComponents) valueType
     *
     * @param string $value Table::TYPE_*
     * @param array $props Props for Ant Design components
     * @return $this
     * 
     * @see https://procomponents.ant.design/en-US/components/schema#valuetype-lists
     * @see https://procomponents.ant.design/en-US/components/field
     */
    public function type(string $valueType, array $props = [])
    {
        if ($props) {
            $props['type'] = $valueType;
            Table::$config[$this->type][$this->key][__FUNCTION__] = $props;
        } else {
            Table::$config[$this->type][$this->key][__FUNCTION__] = $valueType;
        }

        return $this;
    }

    /**
     * Set width (Support '{n}px' or '{n}%')
     *
     * @param string $value
     * @return $this
     */
    public function width(string $value)
    {
        Table::$config[$this->type][$this->key][__FUNCTION__] = $value;
        return $this;
    }
}
