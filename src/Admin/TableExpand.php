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
     * @example Basic [key=>value, key=>value]
     * @example Badge [key=>[text=>value, status=>status], key=>[text=>value, status=>status]]
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
        if ($value[0] === ':') {
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
     * Set width
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
