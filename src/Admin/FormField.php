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

class FormField
{
    private string $form;
    private string $key;

    /**
     * Define the configuration index
     * 
     * @param string $form 
     * @param string $key 
     * @return $this 
     */
    public function __construct(string $form, string $key)
    {
        $this->form = $form;
        $this->key = $key;
        return $this;
    }

    /**
     * Field confirm (e.g. password confirm)
     *
     * @param string $key
     * @return FormField
     */
    public function confirm(string $key)
    {
        Form::$config[$this->form][$this->key][__FUNCTION__] = $key;
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
        Form::$config[$this->form][$this->key][__FUNCTION__] = $value;
        return $this;
    }

    /**
     * Set default
     *
     * @param mixed $value
     * @return FormField
     */
    public function default($value)
    {
        Form::$config[$this->form][$this->key]['value'] = $value;
        return $this;
    }

    /** 
     * Delete current field
     */
    public function delete()
    {
        unset(Form::$config[$this->form][$this->key]);
    }

    /**
     * Set disabled
     *
     * @return FormField
     */
    public function disabled()
    {
        Form::$config[$this->form][$this->key][__FUNCTION__] = true;
        return $this;
    }

    /**
     * Set enum to replace value
     *
     * @param array $keyValues
     * @return FormField
     * 
     * @example Basic [key=>value, key=>value]
     * @example Badge [key=>[text=>value, status=>status], key=>[text=>value, status=>status]]
     * @see https://ant.design/components/badge/#Badge
     */
    public function enum(array $keyValues)
    {
        Form::$config[$this->form][$this->key][__FUNCTION__] = $keyValues;

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
        Form::$config[$this->form][$this->key][__FUNCTION__] = $value;
        return $this;
    }

    /**
     * Hide field
     *
     * @return FormField
     */
    public function hide()
    {
        Form::$config[$this->form][$this->key][__FUNCTION__] = true;
        return $this;
    }

    /**
     * Set placeholder
     *
     * @param string $value key
     * @return FormField
     */
    public function placeholder(string $value)
    {
        Form::$config[$this->form][$this->key][__FUNCTION__] = $value;
        return $this;
    }

    /**
     * Keep the raw data when submit (trim default)
     *
     * @return FormField
     */
    public function raw()
    {
        Form::$config[$this->form][$this->key][__FUNCTION__] = true;
        return $this;
    }

    /**
     * Set readonly
     *
     * @return FormField
     */
    public function readonly()
    {
        Form::$config[$this->form][$this->key][__FUNCTION__] = true;
        return $this;
    }

    /**
     * Set required
     *
     * @return FormField
     */
    public function required()
    {
        Form::$config[$this->form][$this->key][__FUNCTION__] = true;
        return $this;
    }

    /**
     * Which role has permission to view
     *
     * @param array $roleValues
     * @return FormField
     */
    public function role(array $roleValues)
    {
        Form::$config[$this->form][$this->key][__FUNCTION__] = $roleValues;
        return $this;
    }

    /**
     * Rules for field validation
     *
     * @param array $keyValues ['type' => 'string', 'min' => 6, 'max' => 20]
     * @return FormField
     * 
     * @see https://ant.design/components/form/#Rule
     */
    public function rules(array $keyValues)
    {
        Form::$config[$this->form][$this->key][__FUNCTION__] = $keyValues;
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
        Form::$config[$this->form][$this->key][__FUNCTION__] = $value;
        if ($value[0] === ':') {
            Form::$config[$this->form][$this->key]['locale'] = true;
        }
        return $this;
    }

    /**
     * Set tooltip
     *
     * @param string $value key
     * @return FormField
     */
    public function tooltip(string $value)
    {
        Form::$config[$this->form][$this->key][__FUNCTION__] = $value;
        return $this;
    }

    /**
     * Set field (ProComponents) valueType
     *
     * @param string $value Form::TYPE_*
     * @param array $props Props for Ant Design components. e.g. multiple select:['mode' => 'multiple'] or upload:['action' => 'api url', 'multiple' => true, 'data' => ['path' => 'test'], 'accept' => 'image/*,.pdf', 'basicUrl' => 'https://alight.cc']
     * @return FormField
     * 
     * @see https://procomponents.ant.design/en-US/components/schema#valuetype-%E5%88%97%E8%A1%A8
     * @see https://ant.design/components/overview/
     * @see https://ant.design/components/upload/#API
     */
    public function type(string $valueType, array $props = [])
    {
        Form::$config[$this->form][$this->key][__FUNCTION__] = $valueType;

        if ($valueType === 'richText') {
            $props['data']['tinymce'] = 1;
        }

        Form::$config[$this->form][$this->key][__FUNCTION__ . 'Props'] = $props;
        return $this;
    }
}
