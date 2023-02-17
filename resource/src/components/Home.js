import { useState, useRef, useEffect } from 'react';
import { Layout, Menu } from 'antd';
import { DashboardOutlined, SafetyCertificateOutlined, TeamOutlined } from '@ant-design/icons';
import global, { localeInit, localeValue, notEmpty, ModelKit } from './Util';

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

    const menuAction = (e, item) => {
        e.preventDefault();
        switch (item.action ?? 'iframe') {
            case 'modal':
                modelRef.current?.modalShow(item);
                break;
            case 'location':
                window.location.assign(item.url);
                break;
            case 'popup':
                window.open(item.url);
                break;
            default:
                setIframeSrc(item.url);
                break;
        }
    };

    const defaultSelectedKeys = [];
    const defaultOpenKeys = [];
    let defaultIframeSrc;
    const iframeDefault = (item, itemKey, subKey) => {
        if (!defaultIframeSrc && (item.action ?? 'iframe') === 'iframe') {
            defaultIframeSrc = item.url;
            if (subKey) {
                defaultSelectedKeys.push('menu-' + itemKey + '-' + subKey);
                defaultOpenKeys.push('menu-' + itemKey);
            } else {
                defaultSelectedKeys.push('menu-' + itemKey);
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
                    let children = {
                        key: 'menu-' + itemKey + '-' + subKey,
                        label: itemKey === '1' ? localeValue(subValue.title) : subValue.title,
                        icon: subValue.icon && Icons[subValue.icon] ? Icons[subValue.icon] : undefined,
                    };
                    if (subValue.url) {
                        iframeDefault(subValue, itemKey, subKey);
                        children.label = (
                            <a
                                href={subValue.url + (subValue.url.indexOf('?') !== -1 ? '&' : '?') + '_title=' + (itemKey === '1' ? localeValue(subValue.title) : subValue.title)}
                                rel='noopener noreferrer'
                                onClick={e => menuAction(e, subValue)}
                                children={itemKey === '1' ? localeValue(subValue.title) : subValue.title}
                            />
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
                            onClick={e => menuAction(e, itemValue)}
                            children={itemValue.title}
                        />
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
        <Layout style={{ minHeight: '100vh', height: 'auto' }}>
            <Layout.Sider
                collapsible
                breakpoint='lg'
                collapsedWidth={collapsedWidth}
                onBreakpoint={broken => {
                    setCollapsedWidth(broken ? 0 : 48);
                }}
            >
                <Menu
                    mode='inline'
                    theme='dark'
                    inlineIndent={16}
                    defaultSelectedKeys={defaultSelectedKeys}
                    defaultOpenKeys={defaultOpenKeys}
                    items={items}
                    forceSubMenuRender={true}
                />
            </Layout.Sider>
            <Layout className='site-layout'>
                <Layout.Content style={{ display: 'flex' }}>
                    <iframe
                        title='homeFrame'
                        src={iframeSrc}
                        style={{ border: 'none', flex: '1 1 auto' }}
                    />
                </Layout.Content>
            </Layout>
            <ModelKit ref={modelRef} />
        </Layout>
    );
};

export default Home;