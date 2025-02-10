import { useCallback, useEffect, useRef } from 'react';
import { message } from 'antd';
import { BetaSchemaForm, ProFormUploadDragger } from '@ant-design/pro-components';
import { useResizeDetector } from 'react-resize-detector';
import { Editor } from '@tinymce/tinymce-react';
import global, { ajax, ifResult, inIframe, localeInit, localeValue, notEmpty, numberToString, postMessage, redirect } from '../lib/Util';

const Form = props => {
    localeInit(props.locale);

    const rootSize = useResizeDetector({
        handleWidth: false,
        refreshMode: 'debounce',
        refreshRate: 200
    });

    const formRef = useRef();

    const uploadRender = (schema) => (
        <>
            <ProFormUploadDragger
                disabled={(schema.fieldProps.disabled || schema.proFieldProps.readonly) ?? undefined}
                fieldProps={{
                    action: global.path + '/upload',
                    listType: 'picture',
                    maxCount: schema.fieldProps.multiple ? 0 : 1,
                    name: 'file',
                    onChange: ({ file, fileList }) => {
                        if (file.status === 'error') {
                            for (const [key, value] of Object.entries(fileList)) {
                                if (value.uid === file.uid) {
                                    fileList.splice(key, 1);
                                    if (file.response?.message) {
                                        message.error(localeValue(file.response.message));
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
                max={schema.proFieldProps.readonly ? 0 : undefined}
                name={schema.dataIndex}
            />
        </>
    );

    // https://www.tiny.cloud/docs/tinymce/6
    const richTextRender = (schema, form) => (
        <>
            <Editor
                disabled={(schema.fieldProps.disabled || schema.proFieldProps.readonly) ?? undefined}
                init={{
                    branding: false,
                    contextmenu: 'image charmap emoticons visualblocks code fullscreen',
                    contextmenu_never_use_native: true,
                    convert_urls: false,
                    fullscreen_native: true,
                    images_upload_url: global.path + '/upload?' + new URLSearchParams(schema.fieldProps.data).toString(),
                    language: localeValue(':tinymce') ? localeValue(':tinymce') : undefined,
                    max_height: 600,
                    menubar: schema.proFieldProps.readonly ? false : 'edit view insert format table',
                    plugins: [
                        'advlist', 'anchor', 'autolink', 'autoresize', 'charmap', 'code',
                        'emoticons', 'fullscreen', 'image', 'insertdatetime', 'link', 'lists',
                        'quickbars', 'searchreplace', 'table', 'visualblocks',
                    ],
                    promotion: false,
                    quickbars_insert_toolbar: false,
                    quickbars_selection_toolbar: true,
                    toolbar: false,
                    ...schema.fieldProps
                }}
                initialValue={schema.initialValue}
                onEditorChange={(newValue) => form ? form.setFieldsValue({ [schema.key]: newValue }) : false}
                tinymceScriptSrc='/alight-admin/tinymce/tinymce.min.js'
            />
        </>
    );

    let layout = 'horizontal';
    let showButton = false;

    if (notEmpty(global.config.field)) {
        for (const fieldValue of Object.values(global.config.field)) {
            if (['textarea', 'code', 'jsonCode', 'richText', 'group', 'formList', 'formSet'].indexOf(fieldValue.type) !== -1) {
                layout = 'vertical';
                break;
            }
        }
    }

    const columnsBuilder = (columnObj) => {
        const columns = [];
        if (notEmpty(columnObj)) {
            for (const [fieldKey, fieldValue] of Object.entries(columnObj)) {
                const column = {
                    dataIndex: fieldKey,
                    title: fieldValue.locale ? localeValue(fieldValue.title) : fieldValue.title,
                    valueType: fieldValue.type,
                    fieldProps: {},
                    formItemProps: { rules: [] },
                    proFieldProps: {}
                };

                if (layout === 'vertical') {
                    column.colProps = fieldValue.grid ?? { span: 24 };
                } else {
                    column.colProps = { span: 24 };
                    column.formItemProps.labelCol = { sm: 6 };
                    column.formItemProps.wrapperCol = fieldValue.grid ?? { sm: 14 };
                }

                if (fieldValue.value !== undefined && fieldValue.value !== '') {
                    if (fieldValue.type === 'upload') {
                        if (typeof fieldValue.value === 'string') {
                            fieldValue.value = [fieldValue.value];
                        }
                        column.initialValue = [];
                        if (notEmpty(fieldValue.value)) {
                            let basicUrl = fieldValue?.typeProps?.basicUrl ?? window.location.origin;
                            for (const value of Object.values(fieldValue.value)) {
                                if (value) {
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
                        }
                    } else {
                        column.initialValue = numberToString(fieldValue.value);
                    }
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

                if (['group', 'formList', 'formSet'].indexOf(fieldValue.type) !== -1) {
                    if (fieldValue.sub) {
                        if (fieldValue.type === 'formList') {
                            column.columns = [{ valueType: 'group', columns: columnsBuilder(fieldValue.sub) }];
                        } else {
                            column.columns = columnsBuilder(fieldValue.sub);
                        }
                    }
                } else {
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

                    if (fieldValue.type === 'upload') {
                        column.render = (dom, entity, index, action, schema) => uploadRender(schema);
                        column.renderFormItem = (schema) => uploadRender(schema);
                    } else if (fieldValue.type === 'richText') {
                        column.render = (dom, entity, index, action, schema) => richTextRender(schema);
                        column.renderFormItem = (schema, config, form) => richTextRender(schema, form);
                    } else if (fieldValue.type === 'color') {
                        column.fieldProps.showText = true;
                        column.fieldProps.style = { display: 'inline-flex' };
                    }

                    if (fieldValue.placeholder) {
                        column.fieldProps.placeholder = fieldValue.placeholder;
                    }

                    if (fieldValue.disabled) {
                        column.fieldProps.disabled = fieldValue.disabled;
                    }

                    if (fieldValue.typeProps) {
                        column.fieldProps = { ...column.fieldProps, ...fieldValue.typeProps };
                    }

                    if (fieldValue.confirm) {
                        column.formItemProps.rules.push(({ getFieldValue }) => ({
                            validator: (rule, value) => {
                                if (!value || getFieldValue(fieldValue.confirm) === value) {
                                    return Promise.resolve();
                                }
                                const fieldTitle = localeValue(columnObj[fieldValue.confirm].title);
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
                    } else {
                        showButton = true;
                    }
                }

                if (notEmpty(fieldValue.if)) {
                    columns.push({
                        valueType: 'dependency',
                        name: Object.keys(fieldValue.if),
                        columns: (record) => {
                            return ifResult(fieldValue.if, record) ? [column] : [];
                        },
                    });
                } else {
                    columns.push(column);
                }
            }
        }
        return columns;
    }
    const mainColumns = columnsBuilder(global.config.field);

    const getMessage = useCallback(event => {
        if (event.origin === window.location.origin) {
            if (event.data.submit) {
                formRef.current?.submit();
            }
        }
    }, []);

    useEffect(() => {
        if (inIframe()) {
            window.addEventListener('message', getMessage);
            if (showButton) {
                postMessage({ button: showButton });
            }
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    useEffect(() => {
        if (inIframe() && rootSize.height) {
            postMessage({ size: { height: rootSize.height, width: 816 } });
        }
    }, [rootSize.height]);

    return (
        <div ref={rootSize.ref}>
            <BetaSchemaForm
                columns={mainColumns}
                formRef={formRef}
                grid={true}
                labelWrap={true}
                layout={layout}
                layoutType='Form'
                onFinish={async (values) => {
                    if (notEmpty(global.config.field)) {
                        for (const [key, value] of Object.entries(values)) {
                            if (global.config.field[key]) {
                                if (global.config.field[key].type === 'upload') {
                                    values[key] = value.map(e => (e.response?.data?.name ?? e.name));
                                    if (!global.config.field[key]?.typeProps?.multiple) {
                                        values[key] = values[key][0];
                                    }
                                } else if (global.config.field[key].type === 'color') {
                                    if (typeof value !== 'string') {
                                        values[key] = value.toCssString();
                                    }
                                }
                            }
                        }
                    }
                    return ajax(window.location.href, values).then(result => {
                        if (result && result.error === 0) {
                            if (inIframe()) {
                                postMessage(result);
                            } else {
                                redirect(global.path + '/result/200');
                            }
                        }
                    })
                }}
                rowProps={{
                    gutter: 24,
                    justify: 'start'
                }}
                shouldUpdate={false}
                style={{
                    padding: 24,
                    width: '100%'
                }}
                submitter={inIframe() || !showButton ? false : {
                    resetButtonProps: false,
                    submitButtonProps: {
                        style: {
                            float: 'right'
                        }
                    }
                }}
            />
        </div>
    );
};

export default Form;