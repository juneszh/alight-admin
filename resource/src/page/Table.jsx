import { useRef, useState, useEffect } from 'react';
import { Button, Space, Modal, message, Card, Row, Col, Statistic } from 'antd';
import { ExclamationCircleOutlined } from '@ant-design/icons';
import { ProTable } from '@ant-design/pro-components';
import { useResizeDetector } from 'react-resize-detector';
import dayjs from 'dayjs';
import global, { localeInit, localeValue, notEmpty, ajax } from '../lib/Util';
import ModelKit from '../lib/ModelKit';

const Table = props => {
    localeInit(props.locale);

    const actionRef = useRef();
    const modelRef = useRef();

    const [requestStatistic, setRequestStatistic] = useState({});
    const [statisticJustify, setStatisticJustify] = useState('space-evenly');

    const statSize = useResizeDetector({
        handleWidth: false,
        refreshMode: 'debounce',
        refreshRate: 100
    });

    let tableSearch;

    const columnsBuilder = (columnObj) => {
        const columns = [];
        if (notEmpty(columnObj)) {
            let column = {};
            for (const [columnKey, columnValue] of Object.entries(columnObj)) {
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
                            columnSearch.fieldProps = { showTime: { defaultValue: [dayjs('00:00:00', 'HH:mm:ss'), dayjs('23:59:59', 'HH:mm:ss')] } };
                        }
                        columns.push(columnSearch);
                    } else {
                        column.valueType = columnValue.search;
                        column.search = true;
                    }

                    if (columnValue.searchProps) {
                        column.fieldProps = columnValue.searchProps;
                    }
                }

                if (columnValue.ellipsis) {
                    column.ellipsis = columnValue.ellipsis;
                }

                if (columnValue.enum) {
                    if (columnValue.locale) {
                        column.valueEnum = {};
                        for (const [enumKey, enumValue] of Object.entries(columnValue.enum)) {
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
                        column.valueEnum = columnValue.enum;
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

                if (columnValue.copyable) {
                    column.copyable = columnValue.copyable;
                }

                if (columnValue.html) {
                    column.render = (text) => {
                        return <div dangerouslySetInnerHTML={{ __html: text }} />;
                    }
                } else if (columnValue.button) {
                    column.render = (text, record) => {
                        let buttons = [];
                        if (notEmpty(columnValue.button)) {
                            for (const [buttonKey, buttonValue] of Object.entries(columnValue.button)) {
                                let buttonShow = true;
                                if (notEmpty(buttonValue.if)) {
                                    for (const [ifKey, ifValue] of Object.entries(buttonValue.if)) {
                                        if (Array.isArray(ifValue)) {
                                            if (ifValue.indexOf(record[ifKey]) === -1) {
                                                buttonShow = false;
                                                continue;
                                            }
                                        } else {
                                            let ifSign = ifKey.slice(-3);
                                            if (ifSign === '>=]') {
                                                if (record[ifKey.slice(0, -4)] < ifValue) {
                                                    buttonShow = false;
                                                    continue;
                                                }
                                            } else if (ifSign === '<=]') {
                                                if (record[ifKey.slice(0, -4)] > ifValue) {
                                                    buttonShow = false;
                                                    continue;
                                                }
                                            } else if (ifSign === '[>]') {
                                                if (record[ifKey.slice(0, -3)] <= ifValue) {
                                                    buttonShow = false;
                                                    continue;
                                                }
                                            } else if (ifSign === '[<]') {
                                                if (record[ifKey.slice(0, -3)] >= ifValue) {
                                                    buttonShow = false;
                                                    continue;
                                                }
                                            } else if (ifSign === '[!]') {
                                                if (record[ifKey.slice(0, -3)] === ifValue) {
                                                    buttonShow = false;
                                                    continue;
                                                }
                                            } else {
                                                if (record[ifKey] !== ifValue) {
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
        return columns;
    }

    const mainColumns = columnsBuilder(global.config.column);
    const expandColumns = columnsBuilder(global.config.expand);

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
                params._ids = record.join(global.join);
            } else {
                params._id = record.id;
            }
        }
        if (notEmpty(button.param)) {
            Object.assign(params, arrayRecord ? button.param : paramReplace(button.param, record));
        }
        return params;
    };

    const buttonHref = (button, record) => {
        const params = buttonParams(button, record);
        return button.url + '?' + new URLSearchParams(params).toString();
    };

    const buttonAction = (button, record) => {
        const params = buttonParams(button, record);
        switch (button.action) {
            case 'form':
                modelRef.current?.modalShow(button, params, {
                    done: () => {
                        message.success(localeValue(':success'));
                        actionRef.current?.reload();
                    }
                });
                break;
            case 'page':
                modelRef.current?.modalShow(button, params, {
                    close: () => {
                        actionRef.current?.reload();
                    }
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
                window.open(button.url);
                break;
            case 'redirect':
                window.location.assign(button.url);
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
                setStatisticJustify('start');
            } else {
                setStatisticJustify('space-evenly');
            }
        }
    }, [statSize.height]);

    return (
        <div style={{ minHeight: '100vh', height: 'auto', backgroundColor: '#f0f2f5' }}>
            <ProTable
                style={{ padding: 24 }}
                cardBordered
                scroll={{ x: 'max-content' }}
                columns={mainColumns}
                actionRef={actionRef}
                request={async (params = {}, sort) => {
                    if (notEmpty(sort)) {
                        params._order = Object.keys(sort)[0];
                        params._sort = sort[params._order];
                    }
                    const result = await ajax(window.location.pathname + (window.location.search ? window.location.search + '&' : '?') + new URLSearchParams(params).toString());
                    if (result && result.error === 0) {
                        setRequestStatistic(result.data.statistic ?? {});
                        return {
                            success: true,
                            data: result.data.list,
                            total: result.data.count,
                        };
                    } else {
                        setRequestStatistic({});
                        return {
                            success: false,
                            data: [],
                            total: 0,
                        };
                    }
                }}
                revalidateOnFocus={false}
                rowKey='id'
                search={tableSearch ? { labelWidth: 'auto', defaultCollapsed: false } : false}
                pagination={{
                    hideOnSinglePage: false,
                    defaultPageSize: 10,
                    pageSizeOptions: [10, 25, 50, 100],
                }}
                dateFormatter='string'
                toolbar={{
                    actions: toolbarActions
                }}
                rowSelection={notEmpty(global.config.batch.button) ? true : undefined}
                tableAlertRender={({ selectedRowKeys }) => {
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
                options={{
                    setting: false
                }}
                summary={notEmpty(global.config.summary) ? pageData => {
                    let sumVisible = false;
                    let avgVisible = false;
                    let sumCells = [];
                    let avgCells = [];

                    if (notEmpty(mainColumns) && notEmpty(pageData)) {
                        let titleIndex = '0';
                        let summaryColumns = [];

                        if (notEmpty(global.config.batch.button)) {
                            titleIndex = '1';
                            summaryColumns.push('_batch')
                        }
                        for (const column of Object.values(mainColumns)) {
                            summaryColumns.push(column.dataIndex)
                        }

                        for (const [index, key] of Object.entries(summaryColumns)) {
                            if (global.config.summary[key]) {
                                let precision = global.config.summary[key].precision ?? 2;
                                let sum = pageData.reduce((pre, cur) => +cur[key] + pre, 0);
                                let result = sum.toFixed(precision);
                                sumVisible = true;
                                sumCells.push(
                                    <ProTable.Summary.Cell className='ant-table-column-sort' index={index} >
                                        {index === titleIndex ? <><span style={{ float: 'left' }}>{localeValue(':sum')}</span>{result}</> : result}
                                    </ProTable.Summary.Cell>
                                );
                                if (global.config.summary[key].type === 'sum') {
                                    avgCells.push(
                                        <ProTable.Summary.Cell className='ant-table-column-sort' index={index} >
                                            {index === titleIndex ? <span style={{ float: 'left' }}>{localeValue(':avg')}</span> : undefined}
                                        </ProTable.Summary.Cell>
                                    );
                                } else {
                                    avgVisible = true;
                                    result = (sum / pageData.length).toFixed(precision);
                                    avgCells.push(
                                        <ProTable.Summary.Cell className='ant-table-column-sort' index={index} >
                                            {index === titleIndex ? <><span style={{ float: 'left' }}>{localeValue(':avg')}</span>{result}</> : result}
                                        </ProTable.Summary.Cell>
                                    );
                                }
                            } else {
                                sumCells.push(
                                    <ProTable.Summary.Cell className='ant-table-column-sort' index={index} >
                                        {index === titleIndex ? <span style={{ float: 'left' }}>{localeValue(':sum')}</span> : undefined}
                                    </ProTable.Summary.Cell>
                                );
                                avgCells.push(
                                    <ProTable.Summary.Cell className='ant-table-column-sort' index={index} >
                                        {index === titleIndex ? <span style={{ float: 'left' }}>{localeValue(':avg')}</span> : undefined}
                                    </ProTable.Summary.Cell>
                                );
                            }
                        }
                    }

                    return sumVisible || avgVisible ? (
                        <ProTable.Summary>
                            {sumVisible ? (<ProTable.Summary.Row style={{ textAlign: 'right' }}>{sumCells}</ProTable.Summary.Row>) : undefined}
                            {avgVisible ? (<ProTable.Summary.Row style={{ textAlign: 'right' }}>{avgCells}</ProTable.Summary.Row>) : undefined}
                        </ProTable.Summary>
                    ) : undefined;
                } : undefined}
                tableExtraRender={notEmpty(global.config.statistic) ? () => {
                    const statistic = [];
                    for (const [key, value] of Object.entries(global.config.statistic)) {
                        let statValue = value.value ?? undefined;
                        if (notEmpty(requestStatistic)) {
                            statValue = requestStatistic[key] ?? statValue;
                        }
                        statistic.push(
                            <Col>
                                <Statistic
                                    style={{ textAlign: 'center' }}
                                    title={value.title}
                                    value={statValue}
                                />
                            </Col>
                        );
                    }
                    return (
                        <Card>
                            <Row gutter={[16, 16]} justify={statisticJustify} ref={statSize.ref}>{statistic}</Row>
                        </Card>
                    );
                } : undefined}
                expandable={notEmpty(expandColumns) ? {
                    expandedRowRender: (record) => <ProTable
                        columns={expandColumns}
                        dataSource={record?._expand}
                        search={false}
                        options={false}
                        pagination={false}
                    />
                } : undefined}
            />
            <ModelKit ref={modelRef} />
        </div>
    );
};

export default Table;