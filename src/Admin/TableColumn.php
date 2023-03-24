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

use Exception;

class TableColumn
{
    private string $type;
    private string $key;

    /**
     * Define the configuration index
     * 
     * @param string $key 
     * @return TableColumn 
     */
    public function __construct(string $type, string $key)
    {
        $this->type = $type;
        $this->key = $key;
        return $this;
    }

    /**
     * Set title
     * 
     * @param string $value 
     * @return TableColumn 
     */
    public function title(string $value): TableColumn
    {
        Table::$config[$this->type][$this->key][__FUNCTION__] = $value;
        if ($value[0] === ':') {
            Table::$config[$this->type][$this->key]['locale'] = true;
        }
        return $this;
    }

    /**
     * Whether to bind the database
     * 
     * @param bool $value 
     * @return TableColumn 
     */
    public function database(bool $value): TableColumn
    {
        Table::$config[$this->type][$this->key][__FUNCTION__] = $value;
        return $this;
    }

    /**
     * Set sort order
     *
     * @param string $order asc|desc
     * @return TableColumn
     */
    public function sort(string $initOrder = ''): TableColumn
    {
        if ($initOrder === 'asc' || $initOrder === 'desc') {
            $initOrder .= 'end';
        }
        Table::$config[$this->type][$this->key][__FUNCTION__] = $initOrder ? $initOrder : true;
        return $this;
    }

    /**
     * Set text align
     *
     * @param string $direction left|center|right
     * @return TableColumn
     */
    public function align(string $direction): TableColumn
    {
        if (!in_array($direction, ['left', 'center', 'right'])) {
            throw new Exception('$direction must be a valid value');
        }

        Table::$config[$this->type][$this->key][__FUNCTION__] = $direction;
        return $this;
    }

    /**
     * Set search (ProComponents) valueType
     *
     * @param string $value password|money|textarea|date|dateTime|dateWeek|dateMonth|dateQuarter|dateYear|dateRange|dateTimeRange|time|timeRange|text|select|treeSelect|checkbox|rate|radio|radioButton|progress|percent|digit|second|avatar|code|switch|fromNow|image|jsonCode|color|cascader
     * @param array $props $props Props for Ant Design components. e.g. multiple select:['mode' => 'multiple']
     * @param bool $raw
     * @return TableColumn
     * 
     * @see https://procomponents.ant.design/en-US/components/schema#valuetype-%E5%88%97%E8%A1%A8
     * @see https://ant.design/components/overview/
     */
    public function search(string $valueType = 'text[~]', array $props = [], bool $raw = false): TableColumn
    {
        $valueTypeLimit = [
            'money',
            'date',
            'dateTime',
            'dateWeek',
            'dateMonth',
            'dateQuarter',
            'dateYear',
            'dateRange',
            'dateTimeRange',
            'time',
            'timeRange',
            'text',
            'select',
            'treeSelect',
            'checkbox',
            'radio',
            'radioButton',
            'percent',
            'digit',
            'color',
            'cascader',
            //extension
            'text[~]',
        ];

        if (!in_array($valueType, $valueTypeLimit)) {
            throw new Exception('$valueType must be a valid value');
        }

        Table::$config[$this->type][$this->key][__FUNCTION__] = $valueType;

        if ($props) {
            Table::$config[$this->type][$this->key][__FUNCTION__ . 'Props'] = $props;
        }

        if ($raw) {
            Table::$config[$this->type][$this->key][__FUNCTION__ . 'Raw'] = $raw;
        }

        return $this;
    }

    /**
     * Set enum to replace value
     *
     * @param array $keyValues
     * @return TableColumn
     * 
     * @example Basic [key=>value, key=>value]
     * @example Badge [key=>[text=>value, status=>status], key=>[text=>value, status=>status]]
     * @see https://ant.design/components/badge/#Badge
     */
    public function enum(array $keyValues): TableColumn
    {
        Table::$config[$this->type][$this->key][__FUNCTION__] = $keyValues;
        return $this;
    }

    /**
     * Hide column
     *
     * @return TableColumn
     */
    public function hide(): TableColumn
    {
        Table::$config[$this->type][$this->key][__FUNCTION__] = true;
        return $this;
    }


    /**
     * Which role has permission to view
     *
     * @param array $roleValues
     * @return TableColumn
     */
    public function role(array $roleValues): TableColumn
    {
        Table::$config[$this->type][$this->key][__FUNCTION__] = $roleValues;
        return $this;
    }

    /**
     * Set width
     *
     * @param string $value
     * @return TableColumn
     */
    public function width(string $value): TableColumn
    {
        Table::$config[$this->type][$this->key][__FUNCTION__] = $value;
        return $this;
    }

    /**
     * Set fixed 
     *
     * @param string $direction left|right
     * @return TableColumn
     */
    public function fixed(string $direction): TableColumn
    {
        if (!in_array($direction, ['left', 'right'])) {
            throw new Exception('$direction must be a valid value');
        }
        Table::$config[$this->type][$this->key][__FUNCTION__] = $direction;
        return $this;
    }

    /**
     * Set tooltip
     * 
     * @param string $value 
     * @return TableColumn 
     */
    public function tooltip(string $value): TableColumn
    {
        Table::$config[$this->type][$this->key][__FUNCTION__] = $value;
        return $this;
    }

    /**
     * Set copyable
     * 
     * @return TableColumn 
     */
    public function copyable(): TableColumn
    {
        Table::$config[$this->type][$this->key][__FUNCTION__] = true;
        return $this;
    }

    /**
     * Set ellipsis
     * 
     * @return TableColumn 
     */
    public function ellipsis(): TableColumn
    {
        Table::$config[$this->type][$this->key][__FUNCTION__] = true;
        return $this;
    }

    /**
     * Set html
     * 
     * @return TableColumn 
     */
    public function html(): TableColumn
    {
        Table::$config[$this->type][$this->key][__FUNCTION__] = true;
        return $this;
    }
}
