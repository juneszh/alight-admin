import { useEffect, useRef, useState } from 'react';
import { Avatar, Card, Col, Modal, Row } from 'antd';
import { EditOutlined, ExclamationCircleOutlined, LockOutlined, PoweroffOutlined } from '@ant-design/icons';
import Plots from '@ant-design/plots';
import global, { ajax, localeInit, localeValue, notEmpty, redirect } from '../lib/Util';
import ModelKit from '../lib/ModelKit';

const Console = props => {
    localeInit(props.locale);

    const modelRef = useRef();

    const chartDataInit = {};
    if (notEmpty(global.config.chart)) {
        for (const [chartKey, chartValue] of Object.entries(global.config.chart)) {
            chartDataInit[chartKey] = chartValue.config.data ?? [];
        }
    }
    const [chartData, setChartData] = useState(chartDataInit);

    const editProfile = () => {
        modelRef.current?.modalShow({
            action: 'form',
            title: localeValue(':user_profile'),
            url: global.path + '/user/form',
        }, {
            _form: 'my_profile'
        }, {
            done: () => {
                window.location.reload();
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
                return <Plots.Area {...config} />;
            case 'Bar':
                return <Plots.Bar {...config} />;
            case 'BidirectionalBar':
                return <Plots.BidirectionalBar {...config} />;
            case 'Box':
                return <Plots.Box {...config} />;
            case 'Bullet':
                return <Plots.Bullet {...config} />;
            case 'Chord':
                return <Plots.Chord {...config} />;
            case 'CirclePacking':
                return <Plots.CirclePacking {...config} />;
            case 'Column':
                return <Plots.Column {...config} />;
            case 'DualAxes':
                return <Plots.DualAxes {...config} />;
            case 'Facet':
                return <Plots.Facet {...config} />;
            case 'Funnel':
                return <Plots.Funnel {...config} />;
            case 'Gauge':
                return <Plots.Gauge {...config} />;
            case 'Heatmap':
                return <Plots.Heatmap {...config} />;
            case 'Histogram':
                return <Plots.Histogram {...config} />;
            case 'Line':
                return <Plots.Line {...config} />;
            case 'Liquid':
                return <Plots.Liquid {...config} />;
            case 'Mix':
                return <Plots.Mix {...config} />;
            case 'Pie':
                return <Plots.Pie {...config} />;
            case 'Progress':
                return <Plots.Progress {...config} />;
            case 'Radar':
                return <Plots.Radar {...config} />;
            case 'RadialBar':
                return <Plots.RadialBar {...config} />;
            case 'RingProgress':
                return <Plots.RingProgress {...config} />;
            case 'Rose':
                return <Plots.Rose {...config} />;
            case 'Sankey':
                return <Plots.Sankey {...config} />;
            case 'Scatter':
                return <Plots.Scatter {...config} />;
            case 'Stock':
                return <Plots.Stock {...config} />;
            case 'Sunburst':
                return <Plots.Sunburst {...config} />;
            case 'TinyArea':
                return <Plots.TinyArea {...config} />;
            case 'TinyColumn':
                return <Plots.TinyColumn {...config} />;
            case 'TinyLine':
                return <Plots.TinyLine {...config} />;
            case 'Treemap':
                return <Plots.Treemap {...config} />;
            case 'Venn':
                return <Plots.Venn {...config} />;
            case 'Violin':
                return <Plots.Violin {...config} />;
            case 'Waterfall':
                return <Plots.Waterfall {...config} />;
            case 'WordCloud':
                return <Plots.WordCloud {...config} />;
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
                        <Card>{chartComponent(chartKey, chartValue.component, chartValue.config)}</Card>
                    </Col>
                );
            }
        }
        return charts;
    };

    useEffect(() => {
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
                <Col xs={24} sm={12} md={8} lg={6} xxl={4}>
                    <Card
                        actions={[
                            <EditOutlined key={1} title={localeValue(':edit_profile')} onClick={editProfile} />,
                            <LockOutlined key={2} title={localeValue(':change_password')} onClick={changePassword} />,
                            <PoweroffOutlined key={3} title={localeValue(':logout')} onClick={logout} />,
                        ]}
                        title={localeValue(':user_profile')}
                    >
                        <Card.Meta
                            avatar={<Avatar src={global.config.user.avatar} size={94} />}
                            description={<><p>{global.config.user.account}</p><p>{global.config.user.role}</p></>}
                            style={{ height: 103 }}
                            title={global.config.user.name}
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