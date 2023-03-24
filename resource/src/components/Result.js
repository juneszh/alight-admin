import { createRef, useState, useEffect } from 'react';
import { Row, Col, Result, Button } from 'antd';
import global, { localeInit, localeValue, inIframe, postMessage, redirect } from './Util';

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
                type='primary'
                onClick={() => { redirect(global.path + '/login'); }}
                children={localeValue(':login') + ' (' + countDown + ')'}
            />
        );
    };

    const closeButton = () => {
        return (
            <Button
                type='default'
                onClick={() => { window.close(); }}
                children={'关闭'}
            />
        );
    };

    useEffect(() => {
        if (inIframe()) {
            postMessage({ size: { height: rootRef?.current.clientHeight, width: 800 } });
        }
    }, [rootRef]);

    return (
        <Row
            justify='center'
            align='middle'
            style={{ minHeight: '100vh', height: 'auto' }}
        >
            <Col ref={rootRef}>
                <Result
                    status={global.config.status === 200 ? 'success' : (global.config.status === 401 ? 403 : global.config.status)}
                    title={global.config.status === 200 ? localeValue(':success') : global.config.status}
                    subTitle={global.config.status === 200 ? undefined : localeValue(':status_' + global.config.status)}
                    extra={global.config.status === 200 ? closeButton() : (global.config.status === 401 ? loginButton() : undefined)}
                />
            </Col>
        </Row>
    );
};

export default Error;