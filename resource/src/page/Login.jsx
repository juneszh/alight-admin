import { useState } from 'react';
import { Button, Checkbox, Col, Form, Input, Row } from 'antd';
import { LockOutlined, SafetyOutlined, UserOutlined } from '@ant-design/icons';
import global, { ajax, localeInit, localeValue } from '../lib/Util';

const Login = props => {
    localeInit(props.locale);

    const [form] = Form.useForm();

    const captchaPath = global.path + '/captcha?t=';
    const [captcha, setCaptcha] = useState(captchaPath + (new Date()).getTime());
    const buildCaptcha = () => {
        setCaptcha(captchaPath + (new Date()).getTime());
    };

    const onFinish = (values) => {
        ajax(global.path + '/login', values).then(result => {
            if (result.error === 0) {
                window.location.replace(global.path + '/');
            } else {
                buildCaptcha();
                form.setFieldsValue({
                    captcha: ''
                });
            }
        }).catch(() => {
            buildCaptcha();
            form.setFieldsValue({
                captcha: ''
            });
        });
    };

    return (
        <Row
            align='middle'
            justify='center'
            style={{ backgroundColor: '#f0f2f5', height: 'auto', minHeight: '100vh' }}
        >
            <Col style={{ width: '320px' }}>
                <h1 style={{ fontWeight: 'bold', marginBottom: '40px', textAlign: 'center' }} >{global.title}</h1>
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
                    <Form.Item style={{ marginBottom: '0' }}>
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
                    </Form.Item>
                    <Form.Item style={{ marginBottom: '12px' }}>
                        <Button
                            block
                            htmlType='submit'
                            type='primary'
                        >{localeValue(':login')}</Button>
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

export default Login;