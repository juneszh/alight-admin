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

class FormOption
{
    private string $form;

    /**
     * Define the configuration index
     * 
     * @param string $form 
     * @return FormOption 
     */
    public function __construct(string $form)
    {
        $this->form = $form;
        return $this;
    }

    /**
     * Copy form configuration
     * 
     * @param string $form 
     * @return FormOption 
     * @throws Exception 
     */
    public function copy(string $form): FormOption
    {
        if (!isset(Form::$config[$form])) {
            throw new Exception('Missing copy form definition.');
        }

        Form::$config[$this->form] = Form::$config[$form];
        return $this;
    }
}
