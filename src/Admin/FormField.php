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

class FormField
{
    private string $form;
    private string $key;


    /**
     * Define the configuration index
     * 
     * @param string $form 
     * @param string $key 
     * @return FormField 
     */
    public function __construct(string $form, string $key)
    {
        $this->form = $form;
        $this->key = $key;
        return $this;
    }

    /**
     * Set title
     * 
     * @param string $value 
     * @return FormField 
     */
    public function title(string $value): FormField
    {
        Form::$config[$this->form][$this->key][__FUNCTION__] = $value;
        if ($value[0] === ':') {
            Form::$config[$this->form][$this->key]['locale'] = true;
        }
        return $this;
    }

    /**
     * Whether to bind the database
     * 
     * @param bool $value 
     * @return FormField 
     */
    public function database(bool $value): FormField
    {
        Form::$config[$this->form][$this->key][__FUNCTION__] = $value;
        return $this;
    }

    /**
     * Set field (ProComponents) valueType
     *
     * @param string $value password|money|textarea|date|dateTime|dateWeek|dateMonth|dateQuarter|dateYear|dateRange|dateTimeRange|time|timeRange|text|select|treeSelect|checkbox|rate|radio|radioButton|progress|percent|digit|second|avatar|code|switch|fromNow|image|jsonCode|color|cascader|upload|richText
     * @param array $props Props for Ant Design components. e.g. multiple select:['mode' => 'multiple'] or upload:['action' => 'api url', 'multiple' => true, 'data' => ['path' => 'test'], 'accept' => 'image/*,.pdf']
     * @return FormField
     * 
     * @see https://procomponents.ant.design/en-US/components/schema#valuetype-%E5%88%97%E8%A1%A8
     * @see https://ant.design/components/overview/
     * @see https://ant.design/components/upload/#API
     */
    public function type(string $valueType, array $props = []): FormField
    {
        $valueTypeLimit = [
            'password',
            'money',
            'textarea',
            'date',
            'dateTime',
            'dateWeek',
            'dateMonth',
            'dateQuarter',
            'dateYear',
            'dateRange',
            'dateTimeRange',
            'time',
            'timeRange',
            'text',
            'select',
            'treeSelect',
            'checkbox',
            'rate',
            'radio',
            'radioButton',
            'progress',
            'percent',
            'digit',
            'second',
            'avatar',
            'code',
            'switch',
            'fromNow',
            'image',
            'jsonCode',
            'color',
            'cascader',
            //extension
            'upload',
            'richText'
        ];

        if (!in_array($valueType, $valueTypeLimit)) {
            throw new Exception('$valueType must be a valid value');
        }

        Form::$config[$this->form][$this->key][__FUNCTION__] = $valueType;

        if ($valueType === 'richText') {
            $props['data']['tinymce'] = 1;
        }

        Form::$config[$this->form][$this->key][__FUNCTION__ . 'Props'] = $props;
        return $this;
    }

    /**
     * Set tooltip
     *
     * @param string $value key
     * @return FormField
     */
    public function tooltip(string $value): FormField
    {
        Form::$config[$this->form][$this->key][__FUNCTION__] = $value;
        return $this;
    }

    /**
     * Set placeholder
     *
     * @param string $value key
     * @return FormField
     */
    public function placeholder(string $value): FormField
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
    public function default($value): FormField
    {
        Form::$config[$this->form][$this->key]['value'] = $value;
        return $this;
    }

    /**
     * Set enum to replace value
     *
     * @param array $keyValues [id=>name]|[[keyName=>x, valueName=>y], [keyName=>x, valueName=>y]]
     * @param string $keyName
     * @param string $valueName
     * @return FormField
     */
    public function enum(array $keyValues, string $keyName = '', string $valueName = ''): FormField
    {
        if ($keyName && $valueName) {
            $keyValues = array_column($keyValues, $valueName, $keyName);
        }
        Form::$config[$this->form][$this->key][__FUNCTION__] = $keyValues;

        return $this;
    }

    /**
     * Set required
     *
     * @return FormField
     */
    public function required(): FormField
    {
        Form::$config[$this->form][$this->key][__FUNCTION__] = true;
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
    public function rules(array $keyValues): FormField
    {
        Form::$config[$this->form][$this->key][__FUNCTION__] = $keyValues;
        return $this;
    }

    /**
     * Field confirm (e.g. password confirm)
     *
     * @param string $key
     * @return FormField
     */
    public function confirm(string $key): FormField
    {
        Form::$config[$this->form][$this->key][__FUNCTION__] = $key;
        return $this;
    }

    /**
     * Hide field
     *
     * @return FormField
     */
    public function hide(): FormField
    {
        Form::$config[$this->form][$this->key][__FUNCTION__] = true;
        return $this;
    }

    /**
     * Set readonly
     *
     * @return FormField
     */
    public function readonly(): FormField
    {
        Form::$config[$this->form][$this->key][__FUNCTION__] = true;
        return $this;
    }

    /**
     * Set disabled
     *
     * @return FormField
     */
    public function disabled(): FormField
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
    public function role(array $roleValues): FormField
    {
        Form::$config[$this->form][$this->key][__FUNCTION__] = $roleValues;
        return $this;
    }

    /**
     * Set the grid span
     * 
     * @param int $value 1-24
     * @return FormField 
     */
    public function span(int $value): FormField
    {
        Form::$config[$this->form][$this->key][__FUNCTION__] = $value;
        return $this;
    }

    /** 
     * Delete current field
     */
    public function delete()
    {
        unset(Form::$config[$this->form][$this->key]);
    }
}
