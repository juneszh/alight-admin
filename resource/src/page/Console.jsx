import { lazy, useEffect, useRef, useState } from 'react';
import { App, Avatar, Card, Col, List, Row, Switch, Typography, theme } from 'antd';
import { InfoCircleOutlined, EditOutlined, ExclamationCircleOutlined, LockOutlined, PoweroffOutlined, SunOutlined, MoonOutlined } from '@ant-design/icons';
import global, { ajax, localeInit, localeValue, notEmpty, redirect } from '../lib/Util';
import ModelKit from '../lib/ModelKit';
import dayjs from 'dayjs';

const { Text } = Typography;
const { useToken } = theme;

const plots = lazy(() => import('../lib/Plots'));

const Console = props => {
    localeInit(props.locale);
    const { token } = useToken();
    const { message, modal } = App.useApp();

    const modelRef = useRef(undefined);

    const [userData, setUserData] = useState({});
    const [noticeCount, setNoticeCount] = useState(0);
    const [noticeList, setNoticeList] = useState([]);
    const [noticeLoad, setNoticeLoad] = useState(false);
    const [isLight] = useState(localStorage.getItem('alight-dark') ? false : true);

    const chartDataInit = {};
    if (notEmpty(global.config.chart)) {
        for (const [chartKey, chartValue] of Object.entries(global.config.chart)) {
            chartDataInit[chartKey] = chartValue.config.data ?? [];
        }
    }
    const [chartData, setChartData] = useState(chartDataInit);

    const getProfile = () => {
        ajax(message, global.path + '/console/user').then(result => {
            if (notEmpty(result.data)) {
                setUserData(result.data);
            }
        });
    };

    const getNotice = (page = 1) => {
        setNoticeLoad(true);
        ajax(message, global.path + '/console/notice/list?page=' + page).then(result => {
            if (notEmpty(result.data)) {
                setNoticeCount(result.data.count);
                setNoticeList(result.data.list);
            }
        }).finally(() => {
            setNoticeLoad(false);
        });
    };

    const readNotice = (item, index) => {
        if (!noticeList[index].read) {
            const newList = noticeList.map((v, i) => {
                if (i === index) {
                    v.read = true;
                }
                return v;
            });
            setNoticeList(newList);
            ajax(message, global.path + '/console/notice/read', { id: item.id });
        }
        if (item.has_content) {
            modelRef.current?.modalShow({
                action: 'form',
                title: item.title,
                url: global.path + '/console/notice/form',
            }, {
                _form: 'read',
                _id: item.id,
            });
        }
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
        modal.confirm({
            icon: <ExclamationCircleOutlined />,
            onOk: () => {
                redirect(global.path + '/logout');
            },
            title: localeValue(':confirm_logout'),
        });
    };

    const chartComponent = (key, component, config) => {
        config.data = chartData[key];
        let subComponent;
        if (component.substring(0, 4) === 'Tiny') {
            subComponent = component.substring(4);
            component = 'Tiny';
        }
        const Plots = subComponent ? plots[component][subComponent] : plots[component];
        return <Plots {...config} />;
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

    const changeMode = checked => {
        if (checked){
            localStorage.removeItem('alight-dark');
        } else {
            localStorage.setItem('alight-dark', '1');
        }
        window.location.reload();
    }

    useEffect(() => {
        getProfile();
        getNotice();
        if (notEmpty(global.config.chart)) {
            for (const [chartKey, chartValue] of Object.entries(global.config.chart)) {
                if (chartValue.api) {
                    ajax(message, chartValue.api).then(result => {
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
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    return (
        <div style={{ backgroundColor: token.colorBgLayout, height: 'auto', minHeight: '100vh', padding: 24, boxSizing: 'border-box' }}>
            <Row gutter={[16, 16]}>
                <Col xs={24} sm={24} md={8} lg={6} xxl={4}>
                    <Card
                        actions={[
                            <EditOutlined key={1} title={localeValue(':edit_profile')} onClick={editProfile} />,
                            <LockOutlined key={2} title={localeValue(':change_password')} onClick={changePassword} />,
                            <PoweroffOutlined key={3} title={localeValue(':logout')} onClick={logout} />,
                        ]}
                        title={localeValue(':user_profile')}
                        extra={<Switch checkedChildren={<SunOutlined />} unCheckedChildren={<MoonOutlined />} onChange={changeMode} defaultChecked={isLight} />}
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
                            renderItem={(item, index) => (
                                <List.Item extra={<span title={dayjs(item.create_time * 1000).format('YYYY-MM-DD HH:mm:ss')}>{dayjs(item.create_time * 1000).format('MM-DD HH:mm')}</span>}>
                                    <List.Item.Meta
                                        title={<Text
                                            ellipsis={{ tooltip: true, suffix: (item.has_content ? <InfoCircleOutlined key={item.id} style={{ paddingLeft: 10 }} /> : undefined) }}
                                            onClick={() => { readNotice(item, index) }}
                                            style={{ width: '100%', cursor: ((item.has_content || !item.read) ? 'pointer' : undefined) }}
                                            strong={item.read ? false : true}
                                        >{item.title}{item.read}</Text>}
                                    />
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

const MyApp = props => (
    <App>
        <Console locale={props.locale} />
    </App>
);

export default MyApp;