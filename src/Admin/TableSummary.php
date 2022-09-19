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

class TableSummary
{
    private string $key;

    /**
     * Define the configuration index
     * 
     * @param string $key 
     * @return TableSummary 
     */
    public function __construct(string $key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * Set average value
     * 
     * @return TableSummary 
     */
    public function avg(): TableSummary
    {
        Table::$config['summary'][$this->key]['type'] = __FUNCTION__;
        return $this;
    }

    /**
     * Set precision
     * 
     * @param int $number 
     * @return TableSummary 
     */
    public function precision(int $number): TableSummary
    {
        Table::$config['summary'][$this->key][__FUNCTION__] = $number;
        return $this;
    }
}
