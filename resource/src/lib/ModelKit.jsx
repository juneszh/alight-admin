import { forwardRef, useCallback, useImperativeHandle, useRef, useState } from 'react';
import { Modal } from 'antd';
import { localeValue } from './Util';


let modalCallback = {};

const ModelKit = forwardRef((props, ref) => {
    const [modalOpen, setModalOpen] = useState(false);
    const [modalHeight, setModalHeight] = useState(400);
    const [modalWidth, setModalWidth] = useState(800);
    const [modalConfig, setModalConfig] = useState({ title: '', url: '' });
    const [modalFooter, setModalFooter] = useState(null);
    const iframeRef = useRef();
    const [lastModal, setLastModal] = useState('form');

    useImperativeHandle(ref, () => ({
        modalShow,
        modalHide
    }));

    const modalShow = (button, params, callbackObj) => {
        modalCallback = callbackObj;
        window.addEventListener('message', getMessage);
        setModalConfig({
            title: params?._title ? params?._title : localeValue(button.title),
            url: button.url + (params ? (button.url.indexOf('?') !== -1 ? '&' : '?') + new URLSearchParams(params).toString() : ''),
        });
        if (lastModal !== button.action) {
            if (button.action === 'form') {
                setModalHeight(400);
                setModalWidth(800);
            } else {
                setModalHeight('100vh');
                setModalWidth('100vw');
            }
        }
        setLastModal(button.action);
        setModalFooter(null);
        setModalOpen(true);
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
                        setModalHeight(Math.ceil(event.data.size.height / 100) * 100);
                    } else {
                        setModalHeight(400);
                    }
                }
                if (event.data.size.width) {
                    setModalWidth((lastWidth) => {
                        return lastWidth === '100vw' ? lastWidth : event.data.size.width;
                    });
                }
            } else if (event.data.button) {
                setModalFooter(undefined);
            }
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const modalHide = () => {
        window.removeEventListener('message', getMessage);
        setModalOpen(false);
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
            destroyOnClose={true}
            footer={modalFooter}
            onCancel={modalHide}
            onOk={modalSubmit}
            open={modalOpen}
            style={{ transition: 'width .6s ease' }}
            styles={{ body: { display: 'flex', height: modalHeight, maxHeight: 'calc(96vh - 116px)', padding: 0, transition: 'height .6s ease' } }}
            width={modalWidth}
        >
            <iframe
                ref={iframeRef}
                src={modalConfig.url}
                style={{ border: 'none', borderRadius: 8, flex: '1 1 auto', overflow: 'auto' }}
                title='modalFrame'
            />
        </Modal>
    );
});
ModelKit.displayName = 'ModelKit';

export default ModelKit;