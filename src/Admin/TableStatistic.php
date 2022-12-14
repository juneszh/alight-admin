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

class TableStatistic
{
    private string $key;

    /**
     * Define the configuration index
     * 
     * @param string $key
     * @return TableStatistic 
     */
    public function __construct(string $key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * Set title
     * 
     * @param string $value 
     * @return TableStatistic 
     */
    public function title(string $value): TableStatistic
    {
        Table::$config['statistic'][$this->key][__FUNCTION__] = $value;
        return $this;
    }

    /**
     * Set value
     * 
     * @param mixed $value 
     * @return TableStatistic 
     */
    public function value($value): TableStatistic
    {
        Table::$config['statistic'][$this->key][__FUNCTION__] = $value;
        return $this;
    }
}
