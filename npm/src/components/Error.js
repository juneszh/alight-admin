import { useState, createRef, useEffect } from 'react';
import { Row, Col, Result, Button } from 'antd';
import global, { localeInit, localeValue, postMessage, redirect } from '../global';

const Error = props => {
    localeInit(props.locale);

    const resultRef = createRef();

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

    useEffect(() => {
        postMessage({ height: resultRef?.current.clientHeight });
    }, [resultRef]);

    return (
        <Row
            justify='center'
            align='middle'
            style={{ minHeight: '100vh', height: 'auto' }}
        >
            <Col ref={resultRef}>
                <Result
                    status={global.config.status === 401 ? 403 : global.config.status}
                    title={global.config.status}
                    subTitle={localeValue(':status_' + global.config.status)}
                    extra={global.config.status === 401 ? loginButton() : undefined}
                />
            </Col>
        </Row>
    );
};

export default Error;