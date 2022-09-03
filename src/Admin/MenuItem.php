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
use Exception;

class MenuItem
{
    private int $index;
    private int $subIndex;

    /**
     * Define the configuration index
     * 
     * @param int $index 
     * @param int $subIndex 
     * @return MenuItem 
     */
    public function __construct(int $index, int $subIndex)
    {
        $this->index = $index;
        $this->subIndex = $subIndex;
        return $this;
    }

    /**
     * Set url
     * 
     * @param string $value 
     * @return MenuItem 
     */
    public function url(string $value): MenuItem
    {
        if ($this->subIndex) {
            Menu::$config[$this->index]['sub'][$this->subIndex][__FUNCTION__] = Admin::url($value);
        } else {
            Menu::$config[$this->index][__FUNCTION__] = Admin::url($value);
        }
        return $this;
    }

    /**
     * Which role has permission to view
     *
     * @param array $roleValues
     * @return MenuItem
     */
    public function role(array $roleValues): MenuItem
    {
        if ($this->subIndex) {
            Menu::$config[$this->index]['sub'][$this->subIndex][__FUNCTION__] = $roleValues;
        } else {
            Menu::$config[$this->index][__FUNCTION__] = $roleValues;
        }
        return $this;
    }

    /**
     * Set click action
     *
     * @param string $value iframe|modal|location|popup
     * @return MenuItem 
     * @throws Exception 
     */
    public function action(string $value): MenuItem
    {
        if (!in_array($value, ['iframe', 'modal', 'location', 'popup'])) {
            throw new Exception('$value must be a valid value');
        }
        
        if ($this->subIndex) {
            Menu::$config[$this->index]['sub'][$this->subIndex][__FUNCTION__] = $value;
        } else {
            Menu::$config[$this->index][__FUNCTION__] = $value;
        }
        return $this;
    }

    /**
     * Set icon
     *
     * @param string $value
     * @return MenuItem
     * 
     * @see https://ant.design/components/icon/
     * @deprecated Only used in the built-in menu, because more icons must be imported in react
     */
    public function icon(string $value): MenuItem
    {
        if ($this->subIndex) {
            Menu::$config[$this->index]['sub'][$this->subIndex][__FUNCTION__] = $value;
        } else {
            Menu::$config[$this->index][__FUNCTION__] = $value;
        }
        return $this;
    }
}
