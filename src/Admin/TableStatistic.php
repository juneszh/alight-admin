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

class TableStatistic
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

        Table::$config['statistic'][$this->key] = [
            'key' => $this->key,
            'title' => $this->key,
        ];

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
        Table::$config['statistic'][$this->key][__FUNCTION__] = $value;
        return $this;
    }

    /**
     * Set value
     * 
     * @param mixed $value 
     * @return $this 
     */
    public function value($value)
    {
        Table::$config['statistic'][$this->key][__FUNCTION__] = $value;
        return $this;
    }
}
