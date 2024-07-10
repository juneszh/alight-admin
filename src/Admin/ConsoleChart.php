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

use Alight\Admin;

class ConsoleChart
{
    private int $index;

    /**
     * Define the configuration index
     * 
     * @param int $index 
     * @param string $component 
     * @return $this 
     */
    public function __construct(int $index, string $component)
    {
        $this->index = $index;
        
        Console::$config[$this->index] = [
            'component' => $component,
            'grid' => ['span' => 24],
        ];

        return $this;
    }

    /**
     * Set data api
     * 
     * @param string $value 
     * @return $this 
     */
    public function api(string $value)
    {
        Console::$config[$this->index][__FUNCTION__] = Admin::url($value);
        return $this;
    }

    /**
     * Set chart config
     *
     * @param array $value
     * @return $this 
     */
    public function config(array $value)
    {
        Console::$config[$this->index][__FUNCTION__] = $value;
        return $this;
    }

    /**
     * Set chart grid
     *
     * @param array $value
     * @return $this 
     */
    public function grid(array $value)
    {
        Console::$config[$this->index][__FUNCTION__] = $value;
        return $this;
    }

    /**
     * Which role has permission to view
     *
     * @param array $roleValues
     * @return ConsoleChart
     */
    public function role(array $roleValues)
    {
        Console::$config[$this->index][__FUNCTION__] = $roleValues;
        return $this;
    }
}
