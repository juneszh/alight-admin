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

use Exception;

class FormField
{
    private string $form;
    private string $key;
    private string $subKey;

    /**
     * Define the configuration index
     * 
     * @param string $form 
     * @param string $key 
     * @param string $subKey 
     * @return $this 
     */
    public function __construct(string $form, string $key, string $subKey)
    {
        $this->form = $form;
        $this->key = $key;
        $this->subKey = $subKey;

        $this->init();

        return $this;
    }

    /**
     * Common initialization config
     * 
     * @param bool $force
     */
    private function init(bool $force = false)
    {
        if ($this->subKey) {
            if (!isset(Form::$config[$this->form][$this->key]['sub'][$this->subKey]) || $force) {
                $database = false;
                if (Form::$config[$this->form][$this->key]['type'] === Form::TYPE_GROUP) {
                    $database = true;
                } elseif (Form::$config[$this->form][$this->key]['type'] === Form::TYPE_FORM_SET) {
                    $this->subKey = $this->key . '[' . $this->subKey . ']';
                }

                Form::$config[$this->form][$this->key]['sub'][$this->subKey] = [
                    'title' => $this->subKey,
                    'database' => $database,
                    'type' => Form::TYPE_TEXT,
                ];
            }
        } else {
            if (!isset(Form::$config[$this->form][$this->key]) || $force) {
                Form::$config[$this->form][$this->key] = [
                    'title' => $this->key,
                    'database' => true,
                    'type' => Form::TYPE_TEXT,
                ];
            }
        }
    }

    /**
     * Common setting config values
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    private function config(string $key, $value)
    {
        if ($this->subKey) {
            Form::$config[$this->form][$this->key]['sub'][$this->subKey][$key] = $value;
        } else {
            Form::$config[$this->form][$this->key][$key] = $value;
        }
    }

    /**
     * Field confirm (e.g. password confirm)
     *
     * @param string $key
     * @return $this
     */
    public function confirm(string $key)
    {
        $this->config(__FUNCTION__, $key);
        return $this;
    }

    /**
     * Whether to bind the database
     * 
     * @param bool $value 
     * @return $this 
     */
    public function database(bool $value)
    {
        $this->config(__FUNCTION__, $value);
        return $this;
    }

    /**
     * Set default
     *
     * @param mixed $value
     * @return $this
     */
    public function default($value)
    {
        $this->config('value', $value);
        return $this;
    }

    /** 
     * Delete current field
     */
    public function delete()
    {
        if ($this->subKey) {
            unset(Form::$config[$this->form][$this->key]['sub'][$this->subKey]);
        } else {
            unset(Form::$config[$this->form][$this->key]);
        }
    }

    /**
     * Set disabled
     *
     * @return $this
     */
    public function disabled()
    {
        $this->config(__FUNCTION__, true);
        return $this;
    }

    /**
     * Set enum to replace value
     *
     * @param array $keyValues
     * @return $this
     * 
     * @example Basic [key=>value, key=>value]
     * @example Badge [key=>[text=>value, status=>status], key=>[text=>value, status=>status]]
     * @see https://ant.design/components/badge/#Badge
     */
    public function enum(array $keyValues)
    {
        $this->config(__FUNCTION__, $keyValues);
        return $this;
    }

    /**
     * Set the grid
     * 
     * @param array $value
     * @return $this 
     */
    public function grid(array $value)
    {
        $this->config(__FUNCTION__, $value);
        return $this;
    }

    /**
     * Hide field
     *
     * @return $this
     */
    public function hide()
    {
        $this->config(__FUNCTION__, true);
        return $this;
    }

    /**
     * Set display conditions
     *
     * @return $this
     */
    public function if(array $keyValues)
    {
        $this->config(__FUNCTION__, $keyValues);
        return $this;
    }

    /**
     * Set placeholder
     *
     * @param string $value key
     * @return $this
     */
    public function placeholder(string $value)
    {
        $this->config(__FUNCTION__, $value);
        return $this;
    }

    /**
     * Keep the raw data when submit (trim default)
     *
     * @return $this
     */
    public function raw()
    {
        $this->config(__FUNCTION__, true);
        return $this;
    }

    /**
     * Set readonly
     *
     * @return $this
     */
    public function readonly()
    {
        $this->config(__FUNCTION__, true);
        return $this;
    }

    /**
     * Set required
     *
     * @return $this
     */
    public function required()
    {
        $this->config(__FUNCTION__, true);
        return $this;
    }

    /**
     * Reset this field to default settings
     *
     * @return $this
     */
    public function reset()
    {
        $this->init(true);
        return $this;
    }

    /**
     * Which role has permission to view
     *
     * @param array $roleValues
     * @return $this
     */
    public function role(array $roleValues)
    {
        $this->config(__FUNCTION__, $roleValues);
        return $this;
    }

    /**
     * Rules for field validation
     *
     * @param array $keyValues ['type' => 'string', 'min' => 6, 'max' => 20]
     * @return $this
     * 
     * @see https://ant.design/components/form/#Rule
     */
    public function rules(array $keyValues)
    {
        $this->config(__FUNCTION__, $keyValues);
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
        $this->config(__FUNCTION__, $value);
        if (substr($value, 0, 1) === ':') {
            $this->config('locale', true);
        }
        return $this;
    }

    /**
     * Set tooltip
     *
     * @param string $value key
     * @return $this
     */
    public function tooltip(string $value)
    {
        $this->config(__FUNCTION__, $value);
        return $this;
    }

    /**
     * Set field (ProComponents) valueType
     *
     * @param string $value Form::TYPE_*
     * @param array $props Props for Ant Design components. e.g. multiple select:['mode' => 'multiple'] or upload:['action' => 'api url', 'multiple' => true, 'data' => ['path' => 'test'], 'accept' => 'image/*,.pdf', 'basicUrl' => 'https://alight.cc']
     * @return $this
     * 
     * @see https://procomponents.ant.design/en-US/components/schema#valuetype-lists
     * @see https://ant.design/components/overview/
     * @see https://ant.design/components/upload/#API
     */
    public function type(string $valueType, array $props = [])
    {
        $this->config(__FUNCTION__, $valueType);

        if ($valueType === Form::TYPE_RICH_TEXT) {
            $props['data']['tinymce'] = 1;
        } elseif ($valueType === Form::TYPE_GROUP) {
            $this->database(false);
        }

        if ($props) {
            $this->config(__FUNCTION__ . 'Props', $props);
        }
        return $this;
    }
}
