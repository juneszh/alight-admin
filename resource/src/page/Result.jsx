import { useEffect, useRef, useState } from 'react';
import { Button, Col, Result, Row, theme } from 'antd';
import global, { localeInit, localeValue, redirect } from '../lib/Util.js';

const { useToken } = theme;

const Error = props => {
    const localeInitedRef = useRef(false);
    if (!localeInitedRef.current) {
        localeInit(props.locale);
        localeInitedRef.current = true;
    }
    const { token } = useToken();

    const [countDown, setCountDown] = useState(5);

    useEffect(() => {
        if (global.config.status !== 401) return;
        const timer = setInterval(() => {
            setCountDown(prev => {
                const next = prev - 1;
                if (next <= 0) {
                    clearInterval(timer);
                    redirect(global.path + '/login');
                    return 0;
                }
                return next;
            });
        }, 1000);
        return () => {
            clearInterval(timer);
        };
    }, []);

    const extraNode = global.config.status === 200 ? (
        <Button
            type='default'
            onClick={() => { window.close(); }}
        >{localeValue(':close')}</Button>
    ) : (global.config.status === 401 ? (
        <Button
            onClick={() => { redirect(global.path + '/login'); }}
            type='primary'
        >{localeValue(':login') + ' (' + countDown + ')'}</Button>
    ) : undefined);

    return (
        <Row
            align='middle'
            justify='center'
            style={{
                backgroundColor: token.colorBgLayout,
                height: 'auto',
                minHeight: '100vh'
            }}
        >
            <Col>
                <Result
                    extra={extraNode}
                    status={global.config.status === 200 ? 'success' : (global.config.status === 401 ? 403 : global.config.status)}
                    subTitle={global.config.status === 200 || typeof global.config.status === 'string' ? undefined : localeValue(':status_' + global.config.status)}
                    title={global.config.status === 200 ? localeValue(':success') : global.config.status}
                />
            </Col>
        </Row>
    );
};

export default Error;