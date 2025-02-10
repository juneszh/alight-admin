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

    const menuAction = (e, item, itemKey, subKey) => {
        e.preventDefault();
        switch (item.action) {
            case 'popup':
                window.open(item.url);
                break;
            case 'redirect':
                window.location.assign(item.url);
                break;
            default:
                if (subKey) {
                    window.history.pushState(null, '', '#' + itemKey + '-' + subKey);
                    changeIFrame(itemKey + '-' + subKey);
                } else {
                    window.history.pushState(null, '', '#' + itemKey);
                    changeIFrame(itemKey);
                }
                break;
        }
    };

    const changeIFrame = hashKey => {
        if (!hashKey || !iFrameMap[hashKey]) {
            hashKey = window.location.hash ? window.location.hash.substring(1) : '1-1';
        }
        if (!iFrameMap[hashKey]) {
            hashKey = '1-1';
        }
        if (openKeys.length === 0) {
            setOpenKeys([hashKey.split('-')[0]]);
        }
        setSelectedKeys([hashKey]);
        setIframeSrc(iFrameMap[hashKey].url);
        window.document.title = iFrameMap[hashKey].title;
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
                for (const [subKey, subValue] of Object.entries(itemValue.sub)) {
                    if (itemKey === '1') {
                        subValue.title = localeValue(subValue.title);
                    }
                    let children = {
                        key: itemKey + '-' + subKey,
                        label: subValue.title,
                        icon: subValue.icon && Icons[subValue.icon] ? Icons[subValue.icon] : undefined,
                    };
                    if (subValue.url) {
                        if ((subValue.action ?? 'iframe') === 'iframe') {
                            iFrameMap[itemKey + '-' + subKey] = { url: subValue.url, title: preTitle + subValue.title };
                        }
                        children.label = (
                            <a
                                href={subValue.url + (subValue.url.indexOf('?') !== -1 ? '&' : '?') + '_title=' + subValue.title}
                                rel='noopener noreferrer'
                                onClick={e => menuAction(e, subValue, itemKey, subKey)}
                            >{subValue.title}</a>
                        );
                    }
                    item.children.push(children);
                }
            } else {
                if (itemValue.url) {
                    if ((itemValue.action ?? 'iframe') === 'iframe') {
                        iFrameMap[itemKey] = { url: itemValue.url, title: preTitle + itemValue.title };
                    }
                    item.label = (
                        <a
                            href={itemValue.url + (itemValue.url.indexOf('?') !== -1 ? '&' : '?') + '_title=' + itemValue.title}
                            rel='noopener noreferrer'
                            onClick={e => menuAction(e, itemValue, itemKey)}
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