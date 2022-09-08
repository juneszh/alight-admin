import { useEffect, useState } from 'react';
import { Space, Grid, message } from 'antd';
import { BetaSchemaForm, ProFormUploadDragger } from '@ant-design/pro-components';
import global, { localeInit, localeValue, notEmpty, postMessage, ajax } from '../global';
import { useResizeDetector } from 'react-resize-detector';
import { Editor } from '@tinymce/tinymce-react';

const Form = props => {
    localeInit(props.locale);

    const [editorFullScreen, setEditorFullScreen] = useState(false);

    const rootSize = useResizeDetector({
        handleWidth: false,
        refreshMode: 'debounce',
        refreshRate: 50
    });

    const screens = Grid.useBreakpoint();

    const uploadRender = (schema, form) => (
        <><ProFormUploadDragger
            name={schema.dataIndex}
            max={schema.proFieldProps.readonly ? 0 : undefined}
            disabled={(schema.fieldProps.disabled || schema.proFieldProps.readonly) ?? undefined}
            fieldProps={{
                name: 'file',
                listType: 'picture',
                action: global.path + '/upload',
                maxCount: schema.fieldProps.multiple ? 0 : 1,
                onChange: ({ file, fileList }) => {
                    if (file.status === 'done') {
                        if (file.response?.data) {
                            for (const [key, value] of Object.entries(fileList)) {
                                if (value.uid === file.uid) {
                                    fileList[key] = { ...value, ...file.response.data }
                                }
                            }
                        }
                    } else if (file.status === 'error') {
                        for (const [key, value] of Object.entries(fileList)) {
                            if (value.uid === file.uid) {
                                fileList.splice(key, 1);
                                if (file.response?.message) {
                                    message.error(localeValue(file.response?.message));
                                } else {
                                    message.error(localeValue(':upload_failed'));
                                }
                            }
                        }
                    }
                },
                ...schema.fieldProps
            }}
            formItemProps={{
                style: { margin: 0 }
            }}
        /></>
    );

    // https://www.tiny.cloud/docs/tinymce/6
    const richTextRender = (schema, form) => (
        <><Editor
            onInit={(evt, editor) => {
                editor.on('FullscreenStateChanged', e => setEditorFullScreen(e.state));
            }}
            tinymceScriptSrc='/alight-admin/tinymce/tinymce.min.js'
            initialValue={schema.initialValue}
            disabled={(schema.fieldProps.disabled || schema.proFieldProps.readonly) ?? undefined}
            init={{
                language: localeValue(':tinymce'),
                skin: 'tinymce-5',
                plugins: [
                    'advlist', 'anchor', 'autolink', 'autoresize', 'charmap', 'code',
                    'emoticons', 'fullscreen', 'image', 'insertdatetime', 'link', 'lists',
                    'quickbars', 'searchreplace', 'table', 'visualblocks',
                ],
                menubar: schema.proFieldProps.readonly ? false : 'edit view insert format table',
                max_height: 600,
                toolbar: false,
                statusbar: false,
                quickbars_insert_toolbar: false,
                quickbars_selection_toolbar: true,
                contextmenu: 'image charmap emoticons visualblocks code fullscreen',
                contextmenu_never_use_native: true,
                convert_urls: false,
                images_upload_url: global.path + '/upload?' + new URLSearchParams(schema.fieldProps.data).toString(),
                ...schema.fieldProps
            }}
            onEditorChange={(newValue, editor) => form ? form.setFieldsValue({ [schema.key]: newValue }) : false}
        /></>
    );

    const columns = [];
    let layout = 'horizontal';
    if (notEmpty(global.config.field)) {
        let column = {};

        for (const [fieldKey, fieldValue] of Object.entries(global.config.field)) {
            column = {
                dataIndex: fieldKey,
                title: fieldValue.locale ? localeValue(fieldValue.title) : fieldValue.title,
                valueType: fieldValue.type,
                fieldProps: {},
                formItemProps: { rules: [] },
                proFieldProps: {},
                colProps: { xs: 24 }
            };

            if (fieldValue.value) {
                if (fieldValue.type === 'upload') {
                    column.initialValue = [];
                    if (notEmpty(fieldValue.value)) {
                        let basicUrl = fieldValue?.typeProps?.basicUrl ?? window.location.origin;
                        for (const value of Object.values(fieldValue.value)) {
                            let fileUrl = value;
                            if (fileUrl.substring(0, 4) !== 'http') {
                                fileUrl = basicUrl + (fileUrl[0] === '/' ? '' : '/') + fileUrl;
                            }
                            column.initialValue.push({
                                status: 'done',
                                name: value,
                                url: fileUrl,
                            });
                        }
                    }
                } else {
                    column.initialValue = fieldValue.value;
                }
            }

            if (fieldValue.enum) {
                if (fieldValue.locale) {
                    column.valueEnum = {};
                    for (const [enumKey, enumValue] of Object.entries(fieldValue.enum)) {
                        if (typeof enumValue === 'string') {
                            column.valueEnum[enumKey] = localeValue(enumValue);
                        } else {
                            column.valueEnum[enumKey] = enumValue;
                            if (enumValue.text) {
                                column.valueEnum[enumKey].text = localeValue(enumValue.text);
                            }
                        }
                    }
                } else {
                    column.valueEnum = fieldValue.enum;
                }
            }

            if (fieldValue.tooltip) {
                column.tooltip = fieldValue.tooltip;
            }

            if (fieldValue.typeProps) {
                column.fieldProps = fieldValue.typeProps;
            }

            if (fieldValue.type === 'upload') {
                column.render = (dom, entity, index, action, schema) => uploadRender(schema);
                column.renderFormItem = (schema, config, form) => uploadRender(schema);
            } else if (fieldValue.type === 'richText') {
                column.render = (dom, entity, index, action, schema) => richTextRender(schema);
                column.renderFormItem = (schema, config, form) => richTextRender(schema, form);
            }

            if (fieldValue.placeholder) {
                column.fieldProps.placeholder = fieldValue.placeholder;
            }

            if (fieldValue.disabled) {
                column.fieldProps.disabled = fieldValue.disabled;
            }

            if (['money', 'date', 'dateTime', 'dateWeek', 'dateMonth', 'dateQuarter', 'dateYear', 'dateRange', 'dateTimeRange', 'time', 'timeRange', 'progress', 'percent', 'digit', 'fromNow'].indexOf(fieldValue.type) !== -1) {
                column.fieldProps.style = { width: '100%' };
            }

            if (fieldValue.required) {
                column.formItemProps.rules.push({ required: true });
            }

            if (fieldValue.rules) {
                if (Array.isArray(fieldValue.rules)) {
                    column.formItemProps.rules = [...column.formItemProps.rules, ...fieldValue.rules];
                } else {
                    column.formItemProps.rules.push(fieldValue.rules);
                }
            }

            if (fieldValue.confirm) {
                column.formItemProps.rules.push(({ getFieldValue }) => ({
                    validator: (rule, value) => {
                        if (!value || getFieldValue(fieldValue.confirm) === value) {
                            return Promise.resolve();
                        }
                        const fieldTitle = localeValue(global.config.field[fieldValue.confirm].title);
                        const confirmField = localeValue(':confirm_field').replace('{{field}}', fieldTitle);
                        return Promise.reject(new Error(confirmField));
                    }
                }));
            }

            if (fieldValue.hide) {
                column.formItemProps.hidden = fieldValue.hide;
            }

            if (fieldValue.readonly) {
                column.proFieldProps.readonly = fieldValue.readonly;
            }

            if (fieldValue.type === 'richText') {
                layout = 'vertical';
            } else {
                column.formItemProps.labelCol = { sm: 6 }
                column.formItemProps.wrapperCol = fieldValue.grid ?? { sm: 14 }
            }

            columns.push(column);
        }
    }


    useEffect(() => {
        postMessage({ height: editorFullScreen ? 4096 : rootSize.height });
    }, [editorFullScreen, rootSize.height]);

    return (
        <div ref={rootSize.ref}>
            <Space
                style={{
                    width: '100%',
                    padding: '24px ' + (screens.xs ? '24px' : '48px'),
                    backgroundColor: '#ffffff'
                }}
                direction='vertical'
            >
                <BetaSchemaForm
                    shouldUpdate={false}
                    layoutType='Form'
                    layout={layout}
                    labelWrap={true}
                    grid={true}
                    rowProps={{ gutter: 24, justify: 'start' }}
                    columns={columns}
                    submitter={{
                        resetButtonProps: false,
                        submitButtonProps: {
                            style: {
                                float: 'right'
                            }
                        }
                    }}
                    onFinish={async (values) => {
                        if (notEmpty(global.config.field)) {
                            for (const [key, value] of Object.entries(values)) {
                                if (global.config.field[key].type === 'upload') {
                                    values[key] = value.map(x => x['name']);
                                }
                            }
                        }
                        ajax(window.location.href, values).then(result => {
                            if (result && result.error === 0) {
                                postMessage(result);
                            }
                        })
                    }}
                />
            </Space>
        </div>
    );
};

export default Form;