import { useState } from 'react';
import { Row, Col, Form, Input, Button, Checkbox } from 'antd';
import { UserOutlined, LockOutlined, SafetyOutlined } from '@ant-design/icons';
import global, { localeInit, localeValue, ajax } from '../global';

const Login = props => {
    localeInit(props.locale);

    const [form] = Form.useForm();

    const captchaPath = process.env.NODE_ENV === 'development' ? process.env.PUBLIC_URL + '/captcha.jpg?=' : global.path + '/captcha?t=';
    const [captcha, setCaptcha] = useState(captchaPath + (new Date()).getTime());
    const buildCaptcha = () => {
        setCaptcha(captchaPath + (new Date()).getTime());
    };

    const onFinish = (values) => {
        ajax(global.path + '/login', values).then(result => {
            if (result.error === 0) {
                window.location.replace(global.path);
            } else {
                buildCaptcha();
                form.setFieldsValue({
                    captcha: ''
                });
            }
        }).catch(error => {
            buildCaptcha();
            form.setFieldsValue({
                captcha: ''
            });
        });
    };

    return (
        <Row
            justify='center'
            align='middle'
            style={{ minHeight: '100vh', height: 'auto', backgroundColor: '#f0f2f5' }}
        >
            <Col style={{ width: '320px' }}>
                <h1 style={{ textAlign: 'center', fontWeight: 'bold', marginBottom: '40px' }} >
                    {global.title}
                </h1>
                <Form
                    form={form}
                    initialValues={{
                        remember: true,
                    }}
                    onFinish={onFinish}
                    autoComplete='off'
                    size='large'
                    scrollToFirstError={true}
                    layout='vertical'
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
                            type='password'
                            placeholder={localeValue(':password')}
                            prefix={<LockOutlined />}
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
                                    placeholder={localeValue(':captcha')}
                                    prefix={<SafetyOutlined />}
                                    maxLength={5}
                                />
                            </Form.Item>
                            <img
                                alt='captcha'
                                src={captcha}
                                onClick={buildCaptcha}
                                style={{ cursor: 'pointer', height: 'min-content' }}
                            ></img>
                        </div>
                    </Form.Item>
                    <Form.Item style={{ marginBottom: '12px' }}>
                        <Button
                            type='primary'
                            htmlType='submit'
                            block
                        >
                            {localeValue(':login')}
                        </Button>
                    </Form.Item>
                    <Form.Item
                        name='remember'
                        valuePropName='checked'
                        style={{ textAlign: 'right' }}
                    >
                        <Checkbox>
                            {localeValue(':remember')}
                        </Checkbox>
                    </Form.Item>
                </Form>
            </Col>
        </Row>
    );
};

export default Login;