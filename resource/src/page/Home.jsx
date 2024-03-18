import { useEffect, useRef, useState } from 'react';
import { Layout, Menu } from 'antd';
import { DashboardOutlined, SafetyCertificateOutlined, TeamOutlined } from '@ant-design/icons';
import global, { localeInit, localeValue, notEmpty } from '../lib/Util';
import ModelKit from '../lib/ModelKit'

const Home = props => {
    localeInit(props.locale);

    const Icons = {
        DashboardOutlined: <DashboardOutlined />,
        SafetyCertificateOutlined: <SafetyCertificateOutlined />,
        TeamOutlined: <TeamOutlined />
    };

    const modelRef = useRef();

    const [collapsedWidth, setCollapsedWidth] = useState(48);
    const [iframeSrc, setIframeSrc] = useState();

    const menuAction = (e, item, itemKey, subKey) => {
        e.preventDefault();
        switch (item.action ?? 'iframe') {
            case 'form':
            case 'page':
                modelRef.current?.modalShow(item);
                break;
            case 'popup':
                window.open(item.url);
                break;
            case 'redirect':
                window.location.assign(item.url);
                break;
            default:
                window.history.replaceState({ itemKey: itemKey, subKey: subKey }, '', '#' + itemKey + '-' + subKey);
                setIframeSrc(item.url);
                break;
        }
    };

    let defaultSelectedKeys = [];
    let defaultOpenKeys = [];
    let defaultIframeSrc;
    const hashKey = window.location.hash ? window.location.hash.substring(1).split('-') : [];
    const iframeDefault = (item, itemKey, subKey) => {
        if ((item.action ?? 'iframe') === 'iframe') {
            if (!defaultIframeSrc || (hashKey[0] == itemKey && hashKey[1] == subKey)) {
                defaultIframeSrc = item.url;
                if (subKey) {
                    defaultSelectedKeys = ['menu-' + itemKey + '-' + subKey];
                    defaultOpenKeys = ['menu-' + itemKey];
                } else {
                    defaultSelectedKeys = ['menu-' + itemKey ];
                }
            }
        }
    };

    const items = [];
    if (notEmpty(global.config.menu)) {
        for (const [itemKey, itemValue] of Object.entries(global.config.menu)) {
            let item = {
                key: 'menu-' + itemKey,
                label: itemValue.title
            };
            if (notEmpty(itemValue.sub)) {
                item.children = [];
                for (const [subKey, subValue] of Object.entries(itemValue.sub)) {
                    if (itemKey === '1'){
                        subValue.title = localeValue(subValue.title);
                    }
                    let children = {
                        key: 'menu-' + itemKey + '-' + subKey,
                        label: subValue.title,
                        icon: subValue.icon && Icons[subValue.icon] ? Icons[subValue.icon] : undefined,
                    };
                    if (subValue.url) {
                        iframeDefault(subValue, itemKey, subKey);
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
                    iframeDefault(itemValue, itemKey);
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
        if (defaultIframeSrc) {
            setIframeSrc(defaultIframeSrc);
        }
    }, [defaultIframeSrc]);

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
                    defaultOpenKeys={defaultOpenKeys}
                    defaultSelectedKeys={defaultSelectedKeys}
                    forceSubMenuRender={true}
                    inlineIndent={16}
                    items={items}
                    mode='inline'
                    theme='dark'
                />
            </Layout.Sider>
            <Layout className='site-layout'>
                <Layout.Content style={{ display: 'flex' }}>
                    <iframe
                        src={iframeSrc}
                        style={{ border: 'none', flex: '1 1 auto', overflow: 'auto' }}
                        title='homeFrame'
                    />
                </Layout.Content>
            </Layout>
            <ModelKit ref={modelRef} />
        </Layout>
    );
};

export default Home;