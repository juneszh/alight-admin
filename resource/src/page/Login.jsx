import { useEffect, useRef, useState } from 'react';
import { App, Button, Checkbox, Col, Form, Input, Row, theme } from 'antd';
import { LockOutlined, SafetyOutlined, UserOutlined } from '@ant-design/icons';
import global, { ajax, localeInit, localeValue } from '../lib/Util';
import { Turnstile } from '@marsidev/react-turnstile'

const { useToken } = theme;

const SubmitButton = props => {
    const [submittable, setSubmittable] = useState(false);
    // Watch all values
    const values = Form.useWatch([], props.form);
    useEffect(() => {
        props.form
            .validateFields({ validateOnly: true })
            .then(() => setSubmittable(true))
            .catch(() => setSubmittable(false));
    }, [props.form, values]);
    return (
        <Button
            block
            disabled={!submittable}
            htmlType="submit"
            type="primary"
            loading={props.loading}
        >
            {props.children}
        </Button>
    );
};

const Login = props => {
    localeInit(props.locale);
    const { token } = useToken();
    const { message } = App.useApp();

    const [form] = Form.useForm();

    const [load, setLoad] = useState(false);

    const captchaPath = global.path + '/captcha?t=';
    const [captcha, setCaptcha] = useState(captchaPath + (new Date()).getTime());
    const buildCaptcha = () => {
        setCaptcha(captchaPath + (new Date()).getTime());
    };

    const ref = useRef()
    const [turnstile, setTurnstile] = useState(global.config.sitekey ? true : false);
    const setToken = (value) => {
        form.setFieldsValue({
            token: value
        });
    }

    const onFinish = (values) => {
        setLoad(true);
        ajax(message, global.path + '/login', values).then(result => {
            if (result.error === 0) {
                window.location.replace(global.path + '/');
            } else {
                buildCaptcha();
                if (turnstile) {
                    ref.current?.reset();
                    form.setFieldsValue({
                        token: ''
                    });
                } else {
                    form.setFieldsValue({
                        captcha: ''
                    });
                }
                setLoad(false);
            }
        }).catch(() => {
            buildCaptcha();
            if (turnstile) {
                ref.current?.reset();
                form.setFieldsValue({
                    token: ''
                });
            } else {
                form.setFieldsValue({
                    captcha: ''
                });
            }
            setLoad(false);
        });
    };

    return (
        <Row
            align='middle'
            justify='center'
            style={{ backgroundColor: token.colorBgLayout, height: 'auto', minHeight: '100vh' }}
        >
            <Col style={{ width: '320px' }}>
                <h1 style={{ color: token.colorTextBase, fontWeight: 'bold', marginBottom: '40px', textAlign: 'center' }} >{global.title}</h1>
                <Form
                    autoComplete='off'
                    form={form}
                    initialValues={{
                        remember: true,
                    }}
                    layout='vertical'
                    onFinish={onFinish}
                    scrollToFirstError={true}
                    size='large'
                >
                    <Form.Item
                        name='account'
                        rules={[
                            {
                                required: true
                            }
                        ]}
                    >
                        <Input
                            placeholder={localeValue(':account')}
                            prefix={<UserOutlined />}
                        />
                    </Form.Item>
                    <Form.Item
                        name='password'
                        rules={[
                            {
                                required: true
                            }
                        ]}
                    >
                        <Input
                            placeholder={localeValue(':password')}
                            prefix={<LockOutlined />}
                            type='password'
                        />
                    </Form.Item>

                    {turnstile ?
                        <Form.Item
                            name='token'
                            rules={[
                                {
                                    required: true,
                                }
                            ]}
                        >
                            <Turnstile
                                ref={ref}
                                siteKey={global.config.sitekey}
                                onError={() => setTurnstile(false)}
                                onExpire={() => setToken('')}
                                onSuccess={setToken}
                                options={{ size: 'flexible' }}
                            />
                        </Form.Item>
                        :
                        <div style={{ display: 'flex' }}>
                            <Form.Item
                                name='captcha'
                                rules={[
                                    {
                                        required: true,
                                        len: 5
                                    }
                                ]}
                                style={{ flex: '1 1 0%', marginRight: 8 }}
                            >
                                <Input
                                    inputMode='numeric'
                                    maxLength={5}
                                    placeholder={localeValue(':captcha')}
                                    prefix={<SafetyOutlined />}
                                />
                            </Form.Item>
                            <img
                                alt='captcha'
                                onClick={buildCaptcha}
                                src={captcha}
                                style={{ cursor: 'pointer', height: 'min-content', borderRadius: 8 }}
                            ></img>
                        </div>
                    }
                    <Form.Item style={{ marginBottom: '12px' }}>
                        <SubmitButton
                            form={form}
                            loading={load}
                        >{localeValue(':login')}</SubmitButton>
                    </Form.Item>
                    <Form.Item
                        name='remember'
                        style={{ textAlign: 'right' }}
                        valuePropName='checked'
                    >
                        <Checkbox>{localeValue(':remember')}</Checkbox>
                    </Form.Item>
                </Form>
            </Col>
        </Row>
    );
};

const MyApp = props => (
    <App>
        <Login locale={props.locale} />
    </App>
);

export default MyApp;