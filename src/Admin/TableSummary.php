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

class TableSummary
{
    private string $key;

    /**
     * Define the configuration index
     * 
     * @param string $key 
     * @return $this 
     */
    public function __construct(string $key)
    {
        $this->key = $key;

        Table::$config['summary'][$this->key] = [
            'precision' => 2,
        ];

        return $this;
    }

    /**
     * Set average value
     * 
     * @return $this 
     */
    public function avg()
    {
        Table::$config['summary'][$this->key][__FUNCTION__] = true;
        return $this;
    }

    /**
     * Set precision
     * 
     * @param int $number 
     * @return $this 
     */
    public function precision(int $number)
    {
        Table::$config['summary'][$this->key][__FUNCTION__] = $number;
        return $this;
    }
}
