import { useRef, useState, useEffect } from 'react';
import { Button, Space, Modal, message, Card, Row, Col, Statistic } from 'antd';
import moment from 'moment';
import { ProTable } from '@ant-design/pro-components';
import { ExclamationCircleOutlined } from '@ant-design/icons';
import '@ant-design/pro-table/dist/table.css';
import global, { localeInit, localeValue, notEmpty, ajax, ModelKit } from '../global';
import { useResizeDetector } from 'react-resize-detector';

const Table = props => {
    localeInit(props.locale);

    const actionRef = useRef();
    const modelRef = useRef();

    const [statJustify, setStatJustify] = useState('space-evenly');

    const statSize = useResizeDetector({
        handleWidth: false,
        refreshMode: 'debounce',
        refreshRate: 50
    });

    const columns = [];
    let tableSearch;
    if (notEmpty(global.config.column)) {
        let column = {};
        for (const [columnKey, columnValue] of Object.entries(global.config.column)) {
            if (columnValue.hide) {
                continue;
            }
            column = {
                dataIndex: columnKey,
                title: columnValue.locale ? localeValue(columnValue.title) : columnValue.title,
                search: false
            };

            if (columnValue.search) {
                tableSearch = true;

                if (['dateRange', 'timeRange', 'dateTimeRange'].indexOf(columnValue.search) !== -1) {
                    column.valueType = columnValue.search.slice(0, -4);
                    let columnSearch = {
                        dataIndex: columnKey,
                        title: columnValue.locale ? localeValue(columnValue.title) : columnValue.title,
                        search: true,
                        hideInTable: true,
                        valueType: columnValue.search
                    };
                    if (columnValue.search === 'dateTimeRange') {
                        columnSearch.fieldProps = { showTime: { defaultValue: [moment('00:00:00', 'HH:mm:ss'), moment('23:59:59', 'HH:mm:ss')] } };
                    }
                    columns.push(columnSearch);
                } else {
                    column.valueType = columnValue.search;
                    column.search = true;
                }

                if (columnValue.enum) {
                    if (columnValue.locale) {
                        column.valueEnum = {};
                        for (const [enumKey, enumValue] of Object.entries(columnValue.enum)) {
                            column.valueEnum[enumKey] = localeValue(enumValue);
                        }
                    } else {
                        column.valueEnum = columnValue.enum;
                    }
                }

                if (columnValue.searchProps) {
                    column.fieldProps = columnValue.searchProps;
                }
            }

            if (columnValue.sort) {
                column.sorter = true;
                if (typeof columnValue.sort === 'string') {
                    column.defaultSortOrder = columnValue.sort;
                }
            }

            if (columnValue.align) {
                column.align = columnValue.align;
            }

            if (columnValue.width) {
                column.width = columnValue.width;
            }

            if (columnValue.fixed) {
                column.fixed = columnValue.fixed;
            }

            if (columnValue.tooltip) {
                column.tooltip = columnValue.tooltip;
            }

            if (columnValue.ellipsis) {
                column.ellipsis = columnValue.ellipsis;
            }

            if (columnValue.button) {
                column.render = (text, record, index, action) => {
                    let buttons = [];
                    if (notEmpty(columnValue.button)) {
                        for (const [buttonKey, buttonValue] of Object.entries(columnValue.button)) {
                            let buttonShow = true;
                            if (notEmpty(buttonValue.if)) {
                                for (const [ifKey, ifValue] of Object.entries(buttonValue.if)) {
                                    if (Array.isArray(ifValue)) {
                                        if (ifValue.indexOf(record[ifKey]) === -1 && ifValue.indexOf(Number(record[ifKey])) === -1) {
                                            buttonShow = false;
                                            continue;
                                        }
                                    } else {
                                        let ifSign = ifKey.slice(-3);
                                        let ifValueStr = typeof ifValue === 'string' ? ifValue : ifValue.toString();
                                        if (ifSign === '>=]') {
                                            if (record[ifKey.slice(0, -4)] < ifValueStr) {
                                                buttonShow = false;
                                                continue;
                                            }
                                        } else if (ifSign === '<=]') {
                                            if (record[ifKey.slice(0, -4)] > ifValueStr) {
                                                buttonShow = false;
                                                continue;
                                            }
                                        } else if (ifSign === '[>]') {
                                            if (record[ifKey.slice(0, -3)] <= ifValueStr) {
                                                buttonShow = false;
                                                continue;
                                            }
                                        } else if (ifSign === '[<]') {
                                            if (record[ifKey.slice(0, -3)] >= ifValueStr) {
                                                buttonShow = false;
                                                continue;
                                            }
                                        } else if (ifSign === '[!]') {
                                            if (record[ifKey.slice(0, -3)] === ifValueStr) {
                                                buttonShow = false;
                                                continue;
                                            }
                                        } else {
                                            if (record[ifKey] !== ifValueStr) {
                                                buttonShow = false;
                                                continue;
                                            }
                                        }
                                    }
                                }
                            }

                            if (buttonShow) {
                                buttons.push(
                                    <Button
                                        key={`column-botton-${columnKey}-${buttonKey}`}
                                        type={buttonValue.type ?? 'default'}
                                        href={buttonHref(buttonValue, record)}
                                        danger={buttonValue.danger ?? undefined}
                                        size='small'
                                        onClick={(e) => { e.preventDefault(); buttonAction(buttonValue, record); }}
                                    >{buttonValue.locale ? localeValue(buttonValue.title) : buttonValue.title}</Button>
                                );
                            }
                        }
                    }
                    return buttons ? <Space wrap>{buttons}</Space> : null;
                };
            }

            columns.push(column);
        }
    }

    const paramReplace = (data, record) => {
        data = Object.assign({}, data);
        if (notEmpty(record)) {
            const re = /\{\{([^}]+)?\}\}/g;
            let match;
            for (const [key, value] of Object.entries(data)) {
                while ((match = re.exec(value)) !== null) {
                    data[key] = data[key].replaceAll(match[0], record[match[1]]);
                }
            }
        }
        return data;
    };

    const buttonParams = (button, record) => {
        const params = {}, arrayRecord = Array.isArray(record);
        params._form = button.form;
        if (notEmpty(record)) {
            if (arrayRecord) {
                params._id = record.join('|');
            } else {
                params._id = record.id;
            }
        }
        if (notEmpty(button.param)) {
            Object.assign(params, arrayRecord ? button.param : paramReplace(button.param, record));
        }
        return [params, arrayRecord];
    };

    const buttonHref = (button, record) => {
        const [params] = buttonParams(button, record);
        return button.url + '?' + new URLSearchParams(params).toString();
    };

    const buttonAction = (button, record) => {
        const [params, arrayRecord] = buttonParams(button, record);
        const action = button.action ?? (arrayRecord ? 'confirm' : 'modal');
        switch (action) {
            case 'modal':
                modelRef.current?.modalOpen(button, params, () => {
                    message.success(localeValue(':success'));
                    actionRef.current?.reload();
                });
                break;
            case 'confirm':
                Modal.confirm({
                    title: localeValue(':confirm_modify'),
                    icon: <ExclamationCircleOutlined />,
                    onOk: () => {
                        ajax(button.url, params).then(result => {
                            if (result && result.error === 0) {
                                message.success(localeValue(':success'));
                                actionRef.current?.reload();
                            }
                        })
                    },
                });
                break;
            case 'submit':
                ajax(button.url, params).then(result => {
                    if (result && result.error === 0) {
                        message.success(localeValue(':success'));
                        actionRef.current?.reload();
                    }
                })
                break;
            case 'popup':
                window.open(button.url + '?' + new URLSearchParams(params).toString());
                break;
            default:
                break;
        }
    };

    const toolbarActions = [];
    if (notEmpty(global.config.toolbar.button)) {
        for (const [buttonKey, buttonValue] of Object.entries(global.config.toolbar.button)) {
            toolbarActions.push(<Button
                key={`toolbar-botton-${buttonKey}`}
                type={buttonValue.type ?? 'primary'}
                href={buttonHref(buttonValue)}
                danger={buttonValue.danger ?? undefined}
                onClick={(e) => { e.preventDefault(); buttonAction(buttonValue); }}
            >{buttonValue.locale ? localeValue(buttonValue.title) : buttonValue.title}</Button>);
        }
    }

    useEffect(() => {
        if (statSize.height) {
            if (statSize.height > 100) {
                setStatJustify('space-between');
            } else {
                setStatJustify('space-evenly');
            }
        }
    }, [statSize.height]);

    return (
        <div style={{ minHeight: '100vh', height: 'auto', backgroundColor: '#f0f2f5' }}>
            <ProTable
                style={{ padding: 24 }}
                cardBordered
                columns={columns}
                actionRef={actionRef}
                request={async (params = {}, sort, filter) => {
                    if (notEmpty(sort)) {
                        params._order = Object.keys(sort)[0];
                        params._sort = sort[params._order];
                    }
                    const result = await ajax(window.location.pathname + '?' + new URLSearchParams(params).toString());
                    return (result && result.error === 0) ? {
                        success: true,
                        data: result.data.list,
                        total: result.data.count,
                    } : false;
                }}
                rowKey='id'
                search={tableSearch ? { labelWidth: 'auto' } : false}
                pagination={{
                    hideOnSinglePage: false,
                    defaultPageSize: 10,
                    pageSizeOptions: [10, 20, 50, 100],
                }}
                dateFormatter='string'
                toolbar={{
                    actions: toolbarActions
                }}
                rowSelection={notEmpty(global.config.batch.button) ? true : undefined}
                tableAlertRender={({ selectedRowKeys, selectedRows, onCleanSelected }) => {
                    const buttons = [];
                    if (notEmpty(global.config.batch.button)) {
                        for (const [buttonKey, buttonValue] of Object.entries(global.config.batch.button)) {
                            buttons.push(
                                <Button
                                    key={`batch-botton-${buttonKey}`}
                                    type={buttonValue.type ?? 'primary'}
                                    href={buttonHref(buttonValue, selectedRowKeys)}
                                    danger={buttonValue.danger ?? undefined}
                                    onClick={(e) => { e.preventDefault(); buttonAction(buttonValue, selectedRowKeys); }}
                                >{buttonValue.locale ? localeValue(buttonValue.title) : buttonValue.title}</Button>
                            );
                        }
                    }
                    return <Space wrap>{buttons}</Space>;
                }}
                columnsState={{
                    persistenceKey: window.location.pathname,
                    persistenceType: 'sessionStorage',
                }}
                tableExtraRender={notEmpty(global.config.statistic) ? (_, pageData) => {
                    const statistic = [];
                    for (const value of Object.values(global.config.statistic)) {
                        let result = pageData.reduce((pre, cur) => +cur[value.key] + pre, 0);
                        if (value.type === 'avg') {
                            result = result / pageData.length;
                        }
                        let columnTitle = localeValue(global.config.column[value.key].title);
                        let statisticTitle = localeValue(value.title).replace('{{field}}', columnTitle);
                        statistic.push(
                            <Col>
                                <Statistic
                                    valueStyle={{ textAlign: 'center' }}
                                    title={statisticTitle}
                                    value={result}
                                    precision={(value.precision && value.precision > -1) ? value.precision : undefined}
                                    prefix={(value.prefix) ?? undefined}
                                    suffix={(value.suffix) ?? undefined}
                                    groupSeparator={(value.thousandsSeparator) ?? undefined}
                                    decimalSeparator={(value.decimalSeparator) ?? undefined}
                                />
                            </Col>
                        );
                    }
                    return (
                        <Card>
                            <Row gutter={[16, 16]} justify={statJustify} ref={statSize.ref}>
                                {statistic}
                            </Row>
                        </Card>
                    );
                } : undefined}
            />
            <ModelKit ref={modelRef} />
        </div>
    );
};

export default Table;