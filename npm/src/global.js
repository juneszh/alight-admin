import React, { useState, useImperativeHandle, forwardRef, useCallback } from 'react';
import { Modal, message } from 'antd';

const $global = window.$global ?? {};

let i18n = {};

const localeInit = i18nNew => {
    i18n = i18nNew;
};

const localeValue = key => {
    return (key[0] === ':' && i18n[key]) ? i18n[key] : key;
};

const inIframe = () => {
    try {
        return window.self !== window.top;
    } catch (e) {
        return false;
    }
};

const notEmpty = obj => {
    return obj && (Object.getPrototypeOf(obj) === Object.prototype || Object.getPrototypeOf(obj) === Array.prototype) && Object.keys(obj).length !== 0;
};

const postMessage = data => {
    window.parent.postMessage(data, window.location.origin);
};

const ajax = async (url, data) => {
    const options = data ? {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        },
        method: 'POST',
        body: JSON.stringify(data)
    } : {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    };
    return await fetch(url, options)
        .then(response => {
            if (response.headers.get('Content-Type')?.indexOf('application/json') !== -1) {
                return response.json();
            } else {
                throw new Error(response.statusText);
            }
        })
        .then(result => {
            if (result.error !== undefined && result.error !== 0) {
                message.error(localeValue(result.message));
                if (result.error === 401) {
                    setTimeout(() => {
                        redirect($global.path + '/login');
                    }, 3000);
                }
            }
            return result;
        })
        .catch(error => {
            message.error(error.message);
        });
};

const redirect = url => {
    if (inIframe()) {
        window.top.location.replace(url);
    } else {
        window.location.replace(url);
    }
};

let successCallback = undefined;

const ModelKit = forwardRef((props, ref) => {
    const [modalOpen, setIsModalOpen] = useState(false);
    const [modalDestroy, setIsModalDestroy] = useState(false);
    const [modalHeight, setIsModalHeight] = useState(136);
    const [modalWidth, setIsModalWidth] = useState(800);
    const [modalConfig, setIsModalConfig] = useState({ title: '', url: '' });

    useImperativeHandle(ref, () => ({
        modalShow,
        modalHide
    }))

    const modalShow = (button, params, success) => {
        successCallback = success;
        window.addEventListener('message', modalMessage);
        setIsModalConfig({
            title: params?._title ? params?._title : localeValue(button.title),
            url: button.url + (params ? (button.url.indexOf('?') !== -1 ? '&' : '?') + new URLSearchParams(params).toString() : ''),
        });
        setIsModalDestroy(false);
        setIsModalOpen(true);
    };

    const modalMessage = useCallback(event => {
        if (event.origin === window.location.origin) {
            if (event.data.error === 0) {
                setIsModalDestroy(true);
                modalHide();
                if (successCallback !== undefined) {
                    successCallback(event.data);
                }
            } else if (event.data.size) {
                if (event.data.size.height) {
                    if (event.data.size.height === -1) {
                        event.data.size.height = '100vh';
                    }
                    setIsModalHeight(event.data.size.height);
                }
                if (event.data.size.width) {
                    if (event.data.size.width === -1) {
                        event.data.size.width = '100vw';
                    }
                    setIsModalWidth(event.data.size.width);
                } else {
                    setIsModalWidth(800);
                }
            }
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const modalHide = () => {
        window.removeEventListener('message', modalMessage);
        setIsModalOpen(false);
    };

    return (
        <Modal title={modalConfig.title}
            centered
            open={modalOpen}
            destroyOnClose={modalDestroy}
            footer={false}
            onCancel={modalHide}
            bodyStyle={{ display: 'flex', padding: 0, maxHeight: 'calc(96vh - 55px)', height: modalHeight, transition: 'height .2s ease' }}
            width={modalWidth}
            style={{ transition: 'width .2s ease' }}
        >
            <iframe
                title='modalFrame'
                src={modalConfig.url}
                style={{ border: 'none', flex: '1 1 auto' }}
            />
        </Modal>
    );
});

export default $global;
export { localeInit, localeValue, inIframe, notEmpty, postMessage, ajax, redirect, ModelKit };