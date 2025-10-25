import { useEffect, useState } from 'react';
import { Layout, Menu } from 'antd';
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
                window.history.pushState(null, '', '#' + item.url);
                changeIFrame(item.url);
                break;
        }
    };

    const changeIFrame = url => {
        if (!url || !iFrameMap[url]) {
            url = window.location.hash ? window.location.hash.substring(1) : '/console';
        }
        if (!iFrameMap[url]) {
            url = '/console';
        }
        if (openKeys.length === 0) {
            setOpenKeys([iFrameMap[url].parent]);
        }
        setSelectedKeys([url]);
        setIframeSrc(url);
        window.document.title = iFrameMap[url].title;
    }

    const items = [];
    const iFrameMap = {};
    let preTitle = '';
    if (notEmpty(global.config.menu)) {
        for (const [itemKey, itemValue] of Object.entries(global.config.menu)) {
            let item = {
                key: itemKey,
                label: itemValue.title
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
                    let children = {
                        key: subValue.url,
                        label: subValue.title,
                        icon: subValue.icon && Icons[subValue.icon] ? Icons[subValue.icon] : undefined,
                    };
                    if (subValue.url) {
                        if ((subValue.action ?? 'iframe') === 'iframe') {
                            iFrameMap[subValue.url] = { parent: itemKey, title: preTitle + subValue.title };
                        }
                        children.label = (
                            <a
                                href={subValue.url + (subValue.url.indexOf('?') !== -1 ? '&' : '?') + '_title=' + subValue.title}
                                rel='noopener noreferrer'
                                onClick={e => menuAction(e, subValue)}
                            >{subValue.title}</a>
                        );
                    }
                    item.children.push(children);
                }
            } else {
                if (itemValue.url) {
                    if ((itemValue.action ?? 'iframe') === 'iframe') {
                        iFrameMap[itemValue.url] = { parent: itemKey, title: preTitle + itemValue.title };
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