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
    const [modalVisible, setIsModalVisible] = useState(false);
    const [modalHeight, setIsModalHeight] = useState(136);
    const [modalConfig, setIsModalConfig] = useState({ title: '', url: '', width: 800 });

    useImperativeHandle(ref, () => ({
        modalOpen,
        modalClose
    }))

    const modalOpen = (button, params, success) => {
        successCallback = success;
        window.addEventListener('message', modalMessage);
        setIsModalConfig({
            title: localeValue(button.title),
            url: button.url + (params ? (button.url.indexOf('?') !== -1 ? '&' : '?') + new URLSearchParams(params).toString() : ''),
            width: button.width ?? 800,
        });
        setIsModalVisible(true);
    };
    
    const modalMessage = useCallback(event => {
        if (event.origin === window.location.origin) {
            if (event.data.error === 0) {
                modalClose();
                if (successCallback !== undefined) {
                    successCallback(event.data);
                }
            } else if (event.data.height) {
                setIsModalHeight(event.data.height);
            }
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const modalClose = () => {
        window.removeEventListener('message', modalMessage);
        setIsModalVisible(false);
    };

    return (
        <Modal title={modalConfig.title}
            centered
            visible={modalVisible}
            footer={false}
            onCancel={modalClose}
            bodyStyle={{ display: 'flex', padding: 0, maxHeight: 'calc(90vh - 55px)', height: modalHeight, transition: 'height .2s ease-out' }}
            width={modalConfig.width}
        >
            <iframe
                title='modalFrame'
                src={modalConfig.url}
                style={{ border: 'none', flex: '1 1 auto' }}
            ></iframe>
        </Modal>
    );
});

export default $global;
export { localeInit, localeValue, inIframe, notEmpty, postMessage, ajax, redirect, ModelKit };