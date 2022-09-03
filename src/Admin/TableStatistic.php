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
    private int $index = 0;

    /**
     * Define the configuration index
     * 
     * @param int $index 
     * @return TableStatistic 
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
     * @return TableStatistic 
     */
    public function title(string $value): TableStatistic
    {
        Table::$config['statistic'][$this->index][__FUNCTION__] = $value;
        return $this;
    }

    /**
     * Set average value
     * 
     * @return TableStatistic 
     */
    public function avg(): TableStatistic
    {
        Table::$config['statistic'][$this->index]['type'] = __FUNCTION__;
        if (Table::$config['statistic'][$this->index]['title'] == ':sum') {
            Table::$config['statistic'][$this->index]['title'] = ':avg';
        }
        return $this;
    }

    /**
     * Set precision
     * 
     * @param int $number 
     * @return TableStatistic 
     */
    public function precision(int $number): TableStatistic
    {
        Table::$config['statistic'][$this->index][__FUNCTION__] = $number;
        return $this;
    }

    /**
     * Set value prefix
     * 
     * @param string $value 
     * @return TableStatistic 
     */
    public function prefix(string $value): TableStatistic
    {
        Table::$config['statistic'][$this->index][__FUNCTION__] = $value;
        return $this;
    }

    /**
     * Set value suffix
     * 
     * @param string $value 
     * @return TableStatistic 
     */
    public function suffix(string $value): TableStatistic
    {
        Table::$config['statistic'][$this->index][__FUNCTION__] = $value;
        return $this;
    }

    /**
     * Set separator
     * 
     * @param string $thousands 
     * @param string $decimal 
     * @return TableStatistic 
     */
    public function separator(string $thousands = ',', string $decimal = '.'): TableStatistic
    {
        Table::$config['statistic'][$this->index]['thousandsSeparator'] = $thousands;
        Table::$config['statistic'][$this->index]['decimalSeparator'] = $decimal;
        return $this;
    }
}
