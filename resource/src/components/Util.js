import React, { useState, useImperativeHandle, forwardRef, useCallback, useRef } from 'react';
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

let modalCallback = {};

const ModelKit = forwardRef((props, ref) => {
    const [modalOpen, setIsModalOpen] = useState(false);
    const [modalHeight, setIsModalHeight] = useState(400);
    const [modalWidth, setIsModalWidth] = useState(800);
    const [modalConfig, setIsModalConfig] = useState({ title: '', url: '', footer: null });
    const iframeRef = useRef();
    const [lastModal, setLastModal] = useState();

    useImperativeHandle(ref, () => ({
        modalShow,
        modalHide
    }))

    const modalShow = (button, params, callbackObj) => {
        modalCallback = callbackObj;
        window.addEventListener('message', getMessage);
        setIsModalConfig({
            title: params?._title ? params?._title : localeValue(button.title),
            url: button.url + (params ? (button.url.indexOf('?') !== -1 ? '&' : '?') + new URLSearchParams(params).toString() : ''),
            footer: button.action === 'form' ? undefined : null,
        });
        if (lastModal !== button.action) {
            if (button.action === 'form') {
                setIsModalHeight(400);
                setIsModalWidth(800);
            } else {
                setIsModalHeight('100vh');
                setIsModalWidth('100vw');
            }
        }
        setLastModal(button.action);
        setIsModalOpen(true);
    };

    const getMessage = useCallback(event => {
        if (event.origin === window.location.origin) {
            if (event.data.error !== undefined) {
                modalHide();
                if (event.data.error === 0) {
                    if (modalCallback?.done !== undefined) {
                        modalCallback.done(event.data);
                    }
                } else {
                    if (modalCallback?.fail !== undefined) {
                        modalCallback.fail(event.data);
                    }
                }
                if (modalCallback?.always !== undefined) {
                    modalCallback.always(event.data);
                }
            } else if (event.data.size) {
                if (event.data.size.height) {
                    if (event.data.size.height > 400) {
                        setIsModalHeight(Math.ceil(event.data.size.height / 100) * 100);
                    } else {
                        setIsModalHeight(400);
                    }
                }
                if (event.data.size.width) {
                    setIsModalWidth(event.data.size.width);
                }
            }
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const modalHide = () => {
        window.removeEventListener('message', getMessage);
        setIsModalOpen(false);
        if (modalCallback?.close !== undefined) {
            modalCallback.close();
        }
    };

    const modalSubmit = () => {
        iframeRef.current?.contentWindow.postMessage({ submit: true });
    }

    return (
        <Modal title={modalConfig.title}
            centered
            open={modalOpen}
            destroyOnClose={true}
            onCancel={modalHide}
            onOk={modalSubmit}
            bodyStyle={{ display: 'flex', padding: 0, maxHeight: 'calc(96vh - 116px)', height: modalHeight, transition: 'height .6s ease' }}
            width={modalWidth}
            style={{ transition: 'width .6s ease' }}
            footer={modalConfig.footer}
        >
            <iframe
                ref={iframeRef}
                title='modalFrame'
                src={modalConfig.url}
                style={{ border: 'none', borderRadius: 8, flex: '1 1 auto', overflow: 'auto' }}
            />
        </Modal>
    );
});

export default $global;
export { localeInit, localeValue, inIframe, notEmpty, postMessage, ajax, redirect, ModelKit };