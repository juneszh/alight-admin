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
     * Set request
     *
     * @param string $title 
     * @param string $url 
     * @param array $params 
     * @return $this
     */
    public function button(string $title, string $url, array $params = [])
    {
        $this->config(__FUNCTION__, $title);
        $this->config(__FUNCTION__ . 'Url', $url ? Admin::url($url) : null);
        $this->config(__FUNCTION__ . 'Params', $params ?: null);
        return $this;
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
            if ($value === null) {
                unset(Form::$config[$this->form][$this->key]['sub'][$this->subKey][$key]);
            } else {
                Form::$config[$this->form][$this->key]['sub'][$this->subKey][$key] = $value;
            }
        } else {
            if ($value === null) {
                unset(Form::$config[$this->form][$this->key][$key]);
            } else {
                Form::$config[$this->form][$this->key][$key] = $value;
            }
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
        $this->config(__FUNCTION__, $key ?: null);
        return $this;
    }

    /**
     * Whether to bind the database
     * 
     * @param bool $bool 
     * @return $this 
     */
    public function database(bool $bool)
    {
        $this->config(__FUNCTION__, $bool);
        return $this;
    }

    /**
     * Set default
     *
     * @param mixed $value clears the value when set to false
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
     * @param bool $bool 
     * @return $this
     */
    public function disabled(bool $bool = true)
    {
        $this->config(__FUNCTION__, $bool ?: null);
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
        $this->config(__FUNCTION__, $keyValues ?: null);
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
        $this->config(__FUNCTION__, $value ?: null);
        return $this;
    }

    /**
     * Hide field
     *
     * @param bool $bool 
     * @return $this
     */
    public function hide(bool $bool = true)
    {
        $this->config(__FUNCTION__, $bool ?: null);
        return $this;
    }

    /**
     * Set display conditions
     *
     * @param array $keyValues
     * @return $this
     */
    public function if(array $keyValues)
    {
        $this->config(__FUNCTION__, $keyValues ?: null);
        return $this;
    }

    /**
     * Set placeholder
     *
     * @param string $value
     * @return $this
     */
    public function placeholder(string $value)
    {
        $this->config(__FUNCTION__, $value ?: null);
        return $this;
    }

    /**
     * Keep the raw data when submit, default: password_hash(password)/trim(string)/json_encode(array)
     *
     * @param bool $bool 
     * @return $this
     */
    public function raw(bool $bool = true)
    {
        $this->config(__FUNCTION__, $bool ?: null);
        return $this;
    }

    /**
     * Set readonly
     *
     * @param bool $bool 
     * @return $this
     */
    public function readonly(bool $bool = true)
    {
        $this->config(__FUNCTION__, $bool ?: null);
        return $this;
    }

    /**
     * Set request
     *
     * @param string $url 
     * @param array $params 
     * @return $this
     */
    public function request(string $url, array $params = [])
    {
        $this->config(__FUNCTION__, $url ? Admin::url($url) : null);
        $this->config(__FUNCTION__ . 'Params', $params ?: null);
        return $this;
    }

    /**
     * Set required
     *
     * @param bool $bool 
     * @return $this
     */
    public function required(bool $bool = true)
    {
        $this->config(__FUNCTION__, $bool ?: null);
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
        $this->config(__FUNCTION__, $roleValues ?: null);
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
        $this->config(__FUNCTION__, $keyValues ?: null);
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
        $this->config(__FUNCTION__, $value ?: null);
        return $this;
    }

    /**
     * Set field (ProComponents) valueType
     *
     * @param string $value Form::TYPE_*
     * @param null|array $props Props for Ant Design components. e.g. multiple select:['mode' => 'multiple'] or upload:['action' => 'api url', 'multiple' => true, 'data' => ['path' => 'test'], 'accept' => 'image/*,.pdf', 'basicUrl' => 'https://alight.cc', 'imgCrop' => ['aspect' => 2/1]]
     * @return $this
     * 
     * @see https://procomponents.ant.design/en-US/components/schema#valuetype-lists
     * @see https://ant.design/components/overview/
     * @see https://ant.design/components/upload/#API
     * @see https://github.com/nanxiaobei/antd-img-crop
     */
    public function type(string $valueType, ?array $props = null)
    {
        $this->config(__FUNCTION__, $valueType);

        if ($valueType === Form::TYPE_RICH_TEXT) {
            $props['data']['tinymce'] = 1;
        } elseif ($valueType === Form::TYPE_GROUP) {
            $this->database(false);
        }

        $this->config(__FUNCTION__ . 'Props', $props);

        return $this;
    }
}
