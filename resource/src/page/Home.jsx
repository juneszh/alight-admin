import { useCallback, useEffect, useRef, useState } from 'react';
import { ConfigProvider, Layout, Menu } from 'antd';
import { DashboardOutlined, SafetyCertificateOutlined, TeamOutlined } from '@ant-design/icons';
import global, { localeInit, localeValue, notEmpty } from '../lib/Util.js';

const Home = props => {
    const localeInitedRef = useRef(false);
    if (!localeInitedRef.current) {
        localeInit(props.locale);
        localeInitedRef.current = true;
    }

    const Icons = {
        DashboardOutlined: <DashboardOutlined />,
        SafetyCertificateOutlined: <SafetyCertificateOutlined />,
        TeamOutlined: <TeamOutlined />
    };

    const [collapsedWidth, setCollapsedWidth] = useState(60);
    const [openKeys, setOpenKeys] = useState([]);
    const [selectedKeys, setSelectedKeys] = useState([]);

    const [{ items, iFrameMap }] = useState(() => {
        const items = [];
        const iFrameMap = {};
        let preTitle = '';
        if (notEmpty(global.config.menu)) {
            for (const [itemKey, itemValue] of Object.entries(global.config.menu)) {
                const item = {
                    key: itemKey,
                    label: itemValue.title
                };
                if (itemKey === '1') {
                    preTitle = itemValue.title + ' - ';
                }
                if (notEmpty(itemValue.sub)) {
                    item.children = [];
                    for (const subValue of Object.values(itemValue.sub)) {
                        const subTitle = itemKey === '1' ? localeValue(subValue.title) : subValue.title;
                        if (subValue.url) {
                            const children = {
                                key: subValue.key ?? subValue.url,
                                label: subTitle,
                                icon: subValue.icon && Icons[subValue.icon] ? Icons[subValue.icon] : undefined,
                            };
                            if ((subValue.action ?? 'iframe') === 'iframe') {
                                iFrameMap[subValue.key ?? subValue.url] = { parent: itemKey, title: preTitle + subTitle, url: subValue.url };
                            }
                            children.label = (
                                <a
                                    href={subValue.url + (subValue.url.indexOf('?') !== -1 ? '&' : '?') + '_title=' + subTitle}
                                    rel='noopener noreferrer'
                                    onClick={e => menuAction(e, subValue)}
                                >{subTitle}</a>
                            );
                            item.children.push(children);
                        }
                    }
                } else if (itemValue.url) {
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
                items.push(item);
            }
        }
        return { items, iFrameMap };
    });

    const [iframeSrc, setIframeSrc] = useState(() => {
        const firstKey = Object.keys(iFrameMap)[0];
        return firstKey ? iFrameMap[firstKey].url : undefined;
    });

    const changeIFrame = useCallback((nextKey) => {
        const firstKey = Object.keys(iFrameMap)[0];
        if (!firstKey) return;
        let key = nextKey;
        if (!key || !iFrameMap[key]) {
            key = window.location.hash ? window.location.hash.substring(1) : firstKey;
        }
        if (!iFrameMap[key]) {
            key = firstKey;
        }
        setOpenKeys(prev => (prev.length === 0 ? [iFrameMap[key].parent] : prev));
        setSelectedKeys([key]);
        setIframeSrc(iFrameMap[key].url);
        window.document.title = iFrameMap[key].title;
    }, [iFrameMap]);

    const menuAction = useCallback((e, item) => {
        e.preventDefault();
        switch (item.action) {
            case 'popup': {
                window.open(item.url);
                break;
            }
            case 'redirect': {
                window.location.assign(item.url);
                break;
            }
            default: {
                const firstKey = Object.keys(iFrameMap)[0];
                let key = item.key ?? item.url;
                if (key === firstKey) {
                    key = '';
                }
                window.history.pushState(null, '', '#' + key);
                changeIFrame(key);
                break;
            }
        }
    }, [changeIFrame, iFrameMap]);

    useEffect(() => {
        changeIFrame();
        const handleHashChange = (e) => {
            const hashKey = e.newURL.split('#')[1];
            changeIFrame(hashKey);
        };
        window.addEventListener('hashchange', handleHashChange);
        return () => {
            window.removeEventListener('hashchange', handleHashChange);
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    return (
        <ConfigProvider
            theme={{
                components: {
                    Layout: {
                        headerHeight: 0
                    },
                },
            }}
        >
            <Layout style={{ height: 'auto', minHeight: '100vh' }}>
                <Layout.Sider
                    breakpoint='lg'
                    collapsedWidth={collapsedWidth}
                    collapsible
                    onBreakpoint={broken => {
                        setCollapsedWidth(broken ? 0 : 60);
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
        </ConfigProvider>
    );
};

export default Home;