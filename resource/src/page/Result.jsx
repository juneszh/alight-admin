import { createRef, useEffect, useState } from 'react';
import { Button, Col, Result, Row } from 'antd';
import global, { inIframe, localeInit, localeValue, postMessage, redirect } from '../lib/Util';

const Error = props => {
    localeInit(props.locale);

    const rootRef = createRef();

    const [countDown, setCountDown] = useState(5);

    const loginButton = () => {
        let timer = setInterval(() => {
            const countNew = countDown - 1;
            setCountDown(countNew);
            if (countNew <= 0) {
                clearInterval(timer);
                redirect(global.path + '/login');
            }
        }, 1000);
        return (
            <Button
                onClick={() => { redirect(global.path + '/login'); }}
                type='primary'
            >{localeValue(':login') + ' (' + countDown + ')'}</Button>
        );
    };

    const closeButton = () => {
        return (
            <Button
                type='default'
                onClick={() => { window.close(); }}
            >{localeValue(':close')}</Button>
        );
    };

    useEffect(() => {
        if (inIframe()) {
            postMessage({ size: { height: rootRef?.current.clientHeight, width: 800 } });
        }
    }, [rootRef]);

    return (
        <Row
            align='middle'
            justify='center'
            style={{ height: 'auto', minHeight: '100vh' }}
        >
            <Col ref={rootRef}>
                <Result
                    extra={global.config.status === 200 ? closeButton() : (global.config.status === 401 ? loginButton() : undefined)}
                    status={global.config.status === 200 ? 'success' : (global.config.status === 401 ? 403 : global.config.status)}
                    subTitle={global.config.status === 200 || typeof global.config.status === 'string' ? undefined : localeValue(':status_' + global.config.status)}
                    title={global.config.status === 200 ? localeValue(':success') : (global.config.message ?? global.config.status)}
                />
            </Col>
        </Row>
    );
};

export default Error;