import { useEffect, useState } from 'react';
import { Badge, Layout, Menu } from 'antd';
import { DashboardOutlined, SafetyCertificateOutlined, TeamOutlined } from '@ant-design/icons';
import global, { localeInit, localeValue, notEmpty } from '../lib/Util';

const Home = props => {
    localeInit(props.locale);

    const Icons = {
        DashboardOutlined: <DashboardOutlined />,
        SafetyCertificateOutlined: <SafetyCertificateOutlined />,
        TeamOutlined: <TeamOutlined />
    };

    const [collapsedWidth, setCollapsedWidth] = useState(48);
    const [iframeSrc, setIframeSrc] = useState();
    const [openKeys, setOpenKeys] = useState([]);
    const [selectedKeys, setSelectedKeys] = useState([]);
    const [noticeL1, setNoticeL1] = useState({});
    const [noticeL2, setNoticeL2] = useState({});


    const menuAction = (e, item) => {
        e.preventDefault();
        switch (item.action) {
            case 'popup':
                window.open(item.url);
                break;
            case 'redirect':
                window.location.assign(item.url);
                break;
            default:
                window.history.pushState(null, '', '#' + (item.key ?? item.url));
                changeIFrame((item.key ?? item.url));
                break;
        }
    };

    const changeIFrame = key => {
        if (!key || !iFrameMap[key]) {
            key = window.location.hash ? window.location.hash.substring(1) : Object.keys(iFrameMap)[0];
        }
        if (!iFrameMap[key]) {
            key = Object.keys(iFrameMap)[0];
        }
        if (openKeys.length === 0) {
            console.log(iFrameMap, key);
            setOpenKeys([iFrameMap[key].parent]);
        }
        setSelectedKeys([key]);
        setIframeSrc(iFrameMap[key].url);
        window.document.title = iFrameMap[key].title;
    }

    const items = [];
    const iFrameMap = {};
    let preTitle = '';
    if (notEmpty(global.config.menu)) {
        for (const [itemKey, itemValue] of Object.entries(global.config.menu)) {
            const item = {
                key: itemKey,
                label: <>{itemValue.title} {noticeL1[itemKey] !== undefined ? <Badge status="error" /> : undefined}</>
            };
            if (itemKey == 1) {
                preTitle = itemValue.title + ' - ';
            }
            if (notEmpty(itemValue.sub)) {
                item.children = [];
                for (const subValue of Object.values(itemValue.sub)) {
                    if (itemKey === '1') {
                        subValue.title = localeValue(subValue.title);
                    }
                    if (subValue.url) {
                        const children = {
                            key: subValue.key ?? subValue.url,
                            label: subValue.title,
                            icon: subValue.icon && Icons[subValue.icon] ? Icons[subValue.icon] : undefined,
                        };
                        if ((subValue.action ?? 'iframe') === 'iframe') {
                            iFrameMap[subValue.key ?? subValue.url] = { parent: itemKey, title: preTitle + subValue.title, url: subValue.url };
                        }
                        children.label = (
                            <a
                                href={subValue.url + (subValue.url.indexOf('?') !== -1 ? '&' : '?') + '_title=' + subValue.title}
                                rel='noopener noreferrer'
                                onClick={e => menuAction(e, subValue)}
                            >{subValue.title} {noticeL2[children.key] !== undefined ? <Badge status="error" /> : undefined}</a>
                        );
                        item.children.push(children);
                    }
                }
            } else {
                if (itemValue.url) {
                    if ((itemValue.action ?? 'iframe') === 'iframe') {
                        iFrameMap[itemValue.key ?? itemValue.url] = { parent: itemKey, title: preTitle + itemValue.title, url: itemValue.url };
                    }
                    item.label = (
                        <a
                            href={itemValue.url + (itemValue.url.indexOf('?') !== -1 ? '&' : '?') + '_title=' + itemValue.title}
                            rel='noopener noreferrer'
                            onClick={e => menuAction(e, itemValue)}
                        >{itemValue.title}</a>
                    );
                }
            }
            items.push(item);
        }
    }

    useEffect(() => {
        changeIFrame();
        window.addEventListener('hashchange', e => {
            const hashKey = e.newURL.split('#')[1];
            changeIFrame(hashKey);
        })
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    return (
        <Layout style={{ height: 'auto', minHeight: '100vh' }}>
            <Layout.Sider
                breakpoint='lg'
                collapsedWidth={collapsedWidth}
                collapsible
                onBreakpoint={broken => {
                    setCollapsedWidth(broken ? 0 : 48);
                }}
            >
                <Menu
                    forceSubMenuRender={true}
                    inlineIndent={16}
                    items={items}
                    mode='inline'
                    onOpenChange={openKeys => {
                        setOpenKeys(openKeys);
                    }}
                    openKeys={openKeys}
                    selectedKeys={selectedKeys}
                    theme='dark'
                />
            </Layout.Sider>
            <Layout className='site-layout'>
                <Layout.Content style={{ display: 'flex' }}>
                    <iframe
                        key={iframeSrc}
                        src={iframeSrc}
                        style={{ border: 'none', flex: '1 1 auto', overflow: 'auto' }}
                        title='homeFrame'
                    />
                </Layout.Content>
            </Layout>
        </Layout>
    );
};

export default Home;