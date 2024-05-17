import { useEffect, useRef, useState } from 'react';
import { Avatar, Card, Col, List, Modal, Row, Typography } from 'antd';
import { EditOutlined, ExclamationCircleOutlined, LockOutlined, PoweroffOutlined } from '@ant-design/icons';
import { Area, Bar, Base, BidirectionalBar, Box, Bullet, CirclePacking, Column, DualAxes, Funnel, Gauge, Heatmap, Histogram, Line, Liquid, Mix, Pie, Radar, RadialBar, Rose, Sankey, Scatter, Stock, Sunburst, Tiny, Treemap, Venn, Violin, Waterfall, WordCloud } from '@ant-design/plots';
import global, { ajax, localeInit, localeValue, notEmpty, redirect } from '../lib/Util';
import ModelKit from '../lib/ModelKit';
import dayjs from 'dayjs';

const { Text, Link } = Typography;

const Console = props => {
    localeInit(props.locale);

    const modelRef = useRef();

    const [userData, setUserData] = useState({});
    const [noticeCount, setNoticeCount] = useState(0);
    const [noticeList, setNoticeList] = useState([]);
    const [noticeLoad, setNoticeLoad] = useState(false);

    const chartDataInit = {};
    if (notEmpty(global.config.chart)) {
        for (const [chartKey, chartValue] of Object.entries(global.config.chart)) {
            chartDataInit[chartKey] = chartValue.config.data ?? [];
        }
    }
    const [chartData, setChartData] = useState(chartDataInit);

    const getProfile = () => {
        ajax(global.path + '/console/user').then(result => {
            if (notEmpty(result.data)) {
                setUserData(result.data);
            }
        });
    };

    const getNotice = (page = 1) => {
        setNoticeLoad(true);
        ajax(global.path + '/console/notice/list?page=' + page).then(result => {
            if (notEmpty(result.data)) {
                setNoticeCount(result.data.count);
                setNoticeList(result.data.list);
            }
        }).finally(()=>{
            setNoticeLoad(false);
        });
    };

    const showNotice = (id, title) => {
        modelRef.current?.modalShow({
            action: 'form',
            title: title,
            url: global.path + '/console/notice/form',
        }, {
            _form: 'read',
            _id: id,
        });
    }

    const editProfile = () => {
        modelRef.current?.modalShow({
            action: 'form',
            title: localeValue(':user_profile'),
            url: global.path + '/user/form',
        }, {
            _form: 'my_profile'
        }, {
            done: () => {
                getProfile();
            }
        });
    };

    const changePassword = () => {
        modelRef.current?.modalShow({
            action: 'form',
            title: localeValue(':change_password'),
            url: global.path + '/user/form',
        }, {
            _form: 'my_password'
        }, {
            done: () => {
                window.location.reload();
            }
        });
    };

    const logout = () => {
        Modal.confirm({
            icon: <ExclamationCircleOutlined />,
            onOk: () => {
                redirect(global.path + '/logout');
            },
            title: localeValue(':confirm_logout'),
        });
    };

    const chartComponent = (key, component, config) => {
        config.data = chartData[key];
        switch (component) {
            case 'Area':
                return <Area {...config} />;
            case 'Bar':
                return <Bar {...config} />;
            case 'Base':
                return <Base {...config} />;
            case 'BidirectionalBar':
                return <BidirectionalBar {...config} />;
            case 'Box':
                return <Box {...config} />;
            case 'Bullet':
                return <Bullet {...config} />;
            case 'CirclePacking':
                return <CirclePacking {...config} />;
            case 'Column':
                return <Column {...config} />;
            case 'DualAxes':
                return <DualAxes {...config} />;
            case 'Funnel':
                return <Funnel {...config} />;
            case 'Gauge':
                return <Gauge {...config} />;
            case 'Heatmap':
                return <Heatmap {...config} />;
            case 'Histogram':
                return <Histogram {...config} />;
            case 'Line':
                return <Line {...config} />;
            case 'Liquid':
                return <Liquid {...config} />;
            case 'Mix':
                return <Mix {...config} />;
            case 'Pie':
                return <Pie {...config} />;
            case 'Radar':
                return <Radar {...config} />;
            case 'RadialBar':
                return <RadialBar {...config} />;
            case 'Rose':
                return <Rose {...config} />;
            case 'Sankey':
                return <Sankey {...config} />;
            case 'Scatter':
                return <Scatter {...config} />;
            case 'Stock':
                return <Stock {...config} />;
            case 'Sunburst':
                return <Sunburst {...config} />;
            case 'TinyArea':
                return <Tiny.Area {...config} />;
            case 'TinyColumn':
                return <Tiny.Column {...config} />;
            case 'TinyLine':
                return <Tiny.Line {...config} />;
            case 'TinyProgress':
                return <Tiny.Progress {...config} />;
            case 'TinyRing':
                return <Tiny.Ring {...config} />;
            case 'Treemap':
                return <Treemap {...config} />;
            case 'Venn':
                return <Venn {...config} />;
            case 'Violin':
                return <Violin {...config} />;
            case 'Waterfall':
                return <Waterfall {...config} />;
            case 'WordCloud':
                return <WordCloud {...config} />;
            default:
                return null;
        }
    };

    const ChartList = () => {
        const charts = [];
        if (notEmpty(global.config.chart)) {
            for (const [chartKey, chartValue] of Object.entries(global.config.chart)) {
                charts.push(
                    <Col key={chartKey} {...chartValue.grid}>
                        <Card title={chartValue.title ?? undefined}>{chartComponent(chartKey, chartValue.component, chartValue.config)}</Card>
                    </Col>
                );
            }
        }
        return charts;
    };

    useEffect(() => {
        getProfile();
        getNotice();
        if (notEmpty(global.config.chart)) {
            for (const [chartKey, chartValue] of Object.entries(global.config.chart)) {
                if (chartValue.api) {
                    ajax(chartValue.api).then(result => {
                        if (notEmpty(result.data)) {
                            setChartData(prevState => ({
                                ...prevState,
                                [chartKey]: result.data.data
                            }));
                        }
                    });
                }
            }
        }
    }, []);

    return (
        <div style={{ backgroundColor: '#f0f2f5', height: 'auto', minHeight: '100vh', padding: 24 }}>
            <Row gutter={[16, 16]}>
                <Col xs={24} sm={24} md={8} lg={6} xxl={4}>
                    <Card
                        actions={[
                            <EditOutlined key={1} title={localeValue(':edit_profile')} onClick={editProfile} />,
                            <LockOutlined key={2} title={localeValue(':change_password')} onClick={changePassword} />,
                            <PoweroffOutlined key={3} title={localeValue(':logout')} onClick={logout} />,
                        ]}
                        title={localeValue(':user_profile')}
                    >
                        <Card.Meta
                            avatar={<Avatar src={userData.avatar} size={94} />}
                            description={<><p>{userData.account}</p><p>{userData.role}</p></>}
                            style={{ height: 110 }}
                            title={userData.name}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={24} md={16} lg={18} xxl={20}>
                    <Card>
                        <List
                            dataSource={noticeList}
                            loading={noticeLoad}
                            pagination={{
                                hideOnSinglePage: true,
                                onChange: (page) => {
                                    getNotice(page);
                                },
                                pageSize: 4,
                                size: 'small',
                                style: { position: 'absolute', right: 0, bottom: 0 },
                                total: noticeCount
                            }}
                            renderItem={(item) => (
                                <List.Item extra={<span title={dayjs(item.create_time * 1000).format('YYYY-MM-DD HH:mm:ss')}>{dayjs(item.create_time * 1000).format('MM-DD HH:mm')}</span>}>
                                    {(item.has_content ?
                                        <List.Item.Meta
                                            title={<Link
                                                ellipsis={true}
                                                onClick={() => { showNotice(item.id, item.title) }}
                                                style={{ paddingRight: 16 }}
                                            >{item.title}</Link>}
                                        />
                                        :
                                        <List.Item.Meta
                                            title={<Text
                                                ellipsis={{ tooltip: true }}
                                                style={{ paddingRight: 16, width: '100%' }}
                                            >{item.title}</Text>}
                                        />
                                    )}
                                </List.Item>
                            )}
                            size='small'
                            style={{ height: 206 }}
                        />
                    </Card>
                </Col>
                <ChartList />
            </Row>
            <ModelKit ref={modelRef} />
        </div>
    );
};

export default Console;