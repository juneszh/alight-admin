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

class ConsoleChart
{
    private int $index;

    /**
     * Define the configuration index
     * 
     * @param int $index 
     * @return ConsoleChart 
     */
    public function __construct(int $index)
    {
        $this->index = $index;
        return $this;
    }

    /**
     * Set data api
     * 
     * @param string $value 
     * @return ConsoleChart 
     */
    public function api(string $value): ConsoleChart
    {
        Console::$config[$this->index][__FUNCTION__] = Admin::url($value);
        return $this;
    }

    /**
     * Which role has permission to view
     *
     * @param array $roleValues
     * @return ConsoleChart
     */
    public function role(array $roleValues): ConsoleChart
    {
        Console::$config[$this->index][__FUNCTION__] = $roleValues;
        return $this;
    }

    /**
     * Set chart config
     *
     * @param array $value
     * @return ConsoleChart 
     */
    public function config(array $value): ConsoleChart
    {
        Console::$config[$this->index][__FUNCTION__] = $value;
        return $this;
    }

    /**
     * Set chart grid
     *
     * @param array $value
     * @return ConsoleChart 
     */
    public function grid(array $value): ConsoleChart
    {
        Console::$config[$this->index][__FUNCTION__] = $value;
        return $this;
    }
}
