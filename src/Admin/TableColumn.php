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

class TableColumn extends TableExpand
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

        parent::__construct($type, $key);

        return $this;
    }

    /**
     * Whether to bind the database
     * 
     * @param bool $value 
     * @return $this 
     */
    public function database(bool $value = true)
    {
        Table::$config[$this->type][$this->key][__FUNCTION__] = $value;
        return $this;
    }

    /**
     * Set ellipsis (Only takes effect when width is set to '{n}px')
     * 
     * @return $this 
     */
    public function ellipsis()
    {
        Table::$config[$this->type][$this->key][__FUNCTION__] = true;
        return $this;
    }

    /**
     * Set search (ProComponents) valueType
     *
     * @param string $valueType Table::TYPE_*
     * @param array $props Props for Ant Design components. e.g. multiple select:['mode' => 'multiple']
     * @return $this
     * 
     * @see https://procomponents.ant.design/en-US/components/schema#valuetype-lists
     */
    public function search(string $valueType = 'text', array $props = [])
    {
        Table::$config[$this->type][$this->key][__FUNCTION__ . 'Type'] = $valueType;

        if ($props) {
            Table::$config[$this->type][$this->key][__FUNCTION__ . 'Props'] = $props;
        }

        return $this;
    }
}
