import { useState, useImperativeHandle, forwardRef, useCallback, useRef } from 'react';
import { Modal } from 'antd';
import { localeValue } from './Util';


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
    }));

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
            width={modalWidth}
            style={{ transition: 'width .6s ease' }}
            styles={{ body: { display: 'flex', padding: 0, maxHeight: 'calc(96vh - 116px)', height: modalHeight, transition: 'height .6s ease' } }}
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
ModelKit.displayName = 'ModelKit';

export default ModelKit;