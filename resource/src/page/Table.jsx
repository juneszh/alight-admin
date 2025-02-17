import { useRef, useState } from 'react';
import { Button, Card, Col, message, Modal, Popover, QRCode, Row, Space, Statistic } from 'antd';
import { ExclamationCircleOutlined, ScanOutlined } from '@ant-design/icons';
import { ProTable } from '@ant-design/pro-components';
import dayjs from 'dayjs';
import global, { ajax, ifResult, localeInit, localeValue, notEmpty } from '../lib/Util.js';
import ModelKit from '../lib/ModelKit.jsx';

const Table = props => {
    localeInit(props.locale);

    const isMobile = /Mobi/.test(window.navigator.userAgent);

    const actionRef = useRef();
    const modelRef = useRef();

    const [requestStatistic, setRequestStatistic] = useState({});
    const [mainSetting, setMainSetting] = useState({});

    let tableSearch = false;

    const columnsBuilder = (columnObj, expand) => {
        const columns = [];
        if (notEmpty(columnObj)) {
            for (const [columnKey, columnValue] of Object.entries(columnObj)) {
                const column = {
                    dataIndex: columnKey,
                    title: columnValue.locale ? localeValue(columnValue.title) : columnValue.title,
                    search: false,
                };

                if (columnValue.hide) {
                    column.hideInTable = true;
                }

                if (columnValue.searchType) {
                    tableSearch = true;
                    const valueTypeString = columnValue?.type?.type ?? columnValue.type;

                    if (['dateRange', 'timeRange', 'dateTimeRange', 'digitRange'].indexOf(columnValue.searchType) !== -1 || (valueTypeString && columnValue.searchType !== valueTypeString)) {
                        const columnSearch = {
                            dataIndex: columnKey,
                            title: columnValue.locale ? localeValue(columnValue.title) : columnValue.title,
                            search: true,
                            hideInTable: true,
                            valueType: columnValue.searchType
                        };
                        if (columnValue.searchType === 'dateTimeRange') {
                            columnSearch.fieldProps = { showTime: { defaultValue: [dayjs('00:00:00', 'HH:mm:ss'), dayjs('23:59:59', 'HH:mm:ss')] } };
                        }
                        columns.push(columnSearch);
                    } else {
                        column.valueType = columnValue.searchType;
                        column.search = true;
                    }

                    if (columnValue.searchProps) {
                        column.fieldProps = columnValue.searchProps;
                    }
                }

                if (columnValue.type) {
                    if (columnValue.type === 'qrcode') {
                        column.render = (text) => <Popover content={<QRCode value={text} bordered={false} />} ><ScanOutlined style={{ color: '#1677ff', cursor: 'pointer' }} /></Popover>;
                    } else {
                        column.valueType = columnValue.type;
                    }
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
                    if (expand) {
                        column.sorter = (a, b) => {
                            if (a[columnKey] > b[columnKey]) {
                                return 1;
                            } else if (a[columnKey] < b[columnKey]) {
                                return -1;
                            }
                            return 0;
                        }
                    } else {
                        column.sorter = true;
                    }
                    if (typeof columnValue.sort === 'string') {
                        column.defaultSortOrder = columnValue.sort;
                    }
                }

                if (columnValue.align) {
                    column.align = columnValue.align;
                }

                if (columnValue.width) {
                    column.width = columnValue.width;
                    if (columnValue.ellipsis) {
                        if (columnValue.ellipsis && columnValue.width.slice(-2) === 'px') {
                            column.render = (text) => <div style={{ overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', width: columnValue.width, wordBreak: 'keep-all' }} title={text}>{text}</div>;
                        } else {
                            column.ellipsis = columnValue.ellipsis;
                        }
                    }
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
                        const buttons = [];
                        if (notEmpty(columnValue.button)) {
                            for (const [buttonKey, buttonValue] of Object.entries(columnValue.button)) {
                                if (ifResult(buttonValue.if, record)) {
                                    buttons.push(
                                        <Button
                                            autoInsertSpace={false}
                                            color={buttonValue.color ?? 'default'}
                                            href={buttonHref(buttonValue, record)}
                                            key={buttonKey}
                                            onClick={(e) => { e.preventDefault(); buttonAction(buttonValue, record); }}
                                            size='small'
                                            variant={buttonValue.variant ?? (buttonValue.color ? 'solid' : undefined)}
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
    const expandColumns = columnsBuilder(global.config.expand, true);

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
                params._ids = record.join('|');
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
                    icon: <ExclamationCircleOutlined />,
                    onOk: () => {
                        ajax(button.url, params).then(result => {
                            if (result && result.error === 0) {
                                message.success(localeValue(':success'));
                                actionRef.current?.reload();
                            }
                        })
                    },
                    title: localeValue(':confirm_modify'),
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
            toolbarActions.push(
                <Button
                    color={buttonValue.color ?? 'primary'}
                    href={buttonHref(buttonValue)}
                    key={buttonKey}
                    onClick={(e) => { e.preventDefault(); buttonAction(buttonValue); }}
                    variant={buttonValue.variant ?? 'solid'}
                >{buttonValue.locale ? localeValue(buttonValue.title) : buttonValue.title}</Button>
            );
        }
    }

    return (
        <div style={{ backgroundColor: '#f0f2f5', height: 'auto', minHeight: '100vh' }}>
            <ProTable
                actionRef={actionRef}
                cardBordered
                columns={mainColumns}
                dateFormatter='string'
                expandable={notEmpty(expandColumns) ? {
                    expandedRowRender: (record) => <ProTable
                        columns={expandColumns}
                        dataSource={record?._expand}
                        options={false}
                        pagination={false}
                        search={false}
                    />
                } : undefined}
                columnsState={{
                    onChange: (value) => {
                        setMainSetting(value);
                    }
                }}
                pagination={{
                    showQuickJumper: true,
                }}
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
                rowSelection={notEmpty(global.config.batch.button) ? true : undefined}
                scroll={{ x: 'max-content' }}
                search={tableSearch ? { defaultCollapsed: isMobile } : false}
                style={{ padding: 24 }}
                summary={notEmpty(global.config.summary) ? pageData => {
                    let sumVisible = false;
                    let avgVisible = false;
                    const sumCells = [];
                    const avgCells = [];

                    if (notEmpty(mainColumns) && notEmpty(pageData)) {
                        let titleIndex = 0;
                        const summaryColumns = [];

                        if (notEmpty(global.config.batch.button)) {
                            titleIndex = 1;
                            summaryColumns.push('_batch');
                        }
                        if (notEmpty(mainSetting)) {
                            const summaryColumnsLeft = [];
                            const summaryColumnsCenter = [];
                            const summaryColumnsRight = [];
                            for (const [key, value] of Object.entries(mainSetting)) {
                                if (value.show) {
                                    if (value.order !== undefined) {
                                        if (value.fixed === 'left') {
                                            summaryColumnsLeft[value.order] = key;
                                        } else if (value.fixed === 'right') {
                                            summaryColumnsRight[value.order] = key;
                                        } else {
                                            summaryColumnsCenter[value.order] = key;
                                        }

                                    } else {
                                        if (value.fixed === 'left') {
                                            summaryColumnsLeft.push(key);
                                        } else if (value.fixed === 'right') {
                                            summaryColumnsRight.push(key);
                                        } else {
                                            summaryColumnsCenter.push(key);
                                        }
                                    }
                                }
                            }
                            summaryColumns.push(...summaryColumnsLeft.filter(n => n), ...summaryColumnsCenter.filter(n => n), ...summaryColumnsRight.filter(n => n));
                        } else {
                            for (const column of Object.values(mainColumns)) {
                                if (!column.hideInTable) {
                                    summaryColumns.push(column.dataIndex);
                                }
                            }
                        }

                        for (const [index, key] of Object.entries(summaryColumns)) {
                            if (global.config.summary[key]) {
                                let precision = global.config.summary[key].precision ?? 2;
                                let sum = pageData.reduce((pre, cur) => +cur[key] + pre, 0);
                                let result = sum.toFixed(precision);
                                sumVisible = true;
                                sumCells.push(
                                    <ProTable.Summary.Cell className='ant-table-column-sort' index={index} key={key}>
                                        {index == titleIndex ? <><span style={{ float: 'left' }}>{localeValue(':sum')}</span>{result}</> : result}
                                    </ProTable.Summary.Cell>
                                );
                                if (global.config.summary[key].avg !== undefined) {
                                    avgVisible = true;
                                    result = (sum / pageData.length).toFixed(precision);
                                    avgCells.push(
                                        <ProTable.Summary.Cell className='ant-table-column-sort' index={index} key={key}>
                                            {index == titleIndex ? <><span style={{ float: 'left' }}>{localeValue(':avg')}</span>{result}</> : result}
                                        </ProTable.Summary.Cell>
                                    );
                                } else {
                                    avgCells.push(
                                        <ProTable.Summary.Cell className='ant-table-column-sort' index={index} key={key}>
                                            {index == titleIndex ? <span style={{ float: 'left' }}>{localeValue(':avg')}</span> : undefined}
                                        </ProTable.Summary.Cell>
                                    );
                                }
                            } else {
                                sumCells.push(
                                    <ProTable.Summary.Cell className='ant-table-column-sort' index={index} key={key}>
                                        {index == titleIndex ? <span style={{ float: 'left' }}>{localeValue(':sum')}</span> : undefined}
                                    </ProTable.Summary.Cell>
                                );
                                avgCells.push(
                                    <ProTable.Summary.Cell className='ant-table-column-sort' index={index} key={key}>
                                        {index == titleIndex ? <span style={{ float: 'left' }}>{localeValue(':avg')}</span> : undefined}
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
                tableAlertRender={({ selectedRowKeys }) => {
                    const buttons = [];
                    if (notEmpty(global.config.batch.button)) {
                        for (const [buttonKey, buttonValue] of Object.entries(global.config.batch.button)) {
                            buttons.push(
                                <Button
                                    color={buttonValue.color ?? 'primary'}
                                    href={buttonHref(buttonValue, selectedRowKeys)}
                                    key={buttonKey}
                                    onClick={(e) => { e.preventDefault(); buttonAction(buttonValue, selectedRowKeys); }}
                                    variant={buttonValue.variant ?? 'solid'}
                                >{buttonValue.locale ? localeValue(buttonValue.title) : buttonValue.title}</Button>
                            );
                        }
                    }
                    return <Space wrap>{buttons}</Space>;
                }}
                tableExtraRender={notEmpty(global.config.statistic) ? () => {
                    const statistic = [];
                    for (const [key, value] of Object.entries(global.config.statistic)) {
                        let statValue = value.value ?? undefined;
                        if (notEmpty(requestStatistic)) {
                            statValue = requestStatistic[key] ?? statValue;
                        }
                        statistic.push(
                            <Col key={key}>
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
                            <Row gutter={[16, 16]} justify='space-evenly'>{statistic}</Row>
                        </Card>
                    );
                } : undefined}
                toolbar={{
                    actions: toolbarActions
                }}
            />
            <ModelKit ref={modelRef} />
        </div>
    );
};

export default Table;