import { useCallback, useImperativeHandle, useRef, useState } from 'react';
import { Modal } from 'antd';
import Draggable from 'react-draggable';
import { localeValue } from './Util';


let modalCallback = {};

const ModelKit = ({ ref }) => {
    const [modalOpen, setModalOpen] = useState(false);
    const [modalHeight, setModalHeight] = useState(400);
    const [modalWidth, setModalWidth] = useState(816);
    const [modalConfig, setModalConfig] = useState({ title: '', url: '' });
    const [okDisabled, setOkDisabled] = useState(true);
    const [okLoading, setOkLoading] = useState(false);
    const iframeRef = useRef(undefined);
    const [lastModal, setLastModal] = useState('form');
    const [draggleDisabled, setDraggleDisabled] = useState(true);
    const [draggleBounds, setDraggleBounds] = useState({
        left: 0,
        top: 0,
        bottom: 0,
        right: 0,
    });
    const draggleRef = useRef(undefined);

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
                setModalWidth(816);
            } else {
                setModalHeight('100vh');
                setModalWidth('100vw');
            }
        }
        setLastModal(button.action);
        setOkDisabled(true);
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
                setOkLoading(false);
            } else if (event.data.button) {
                setOkDisabled(false);
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
        setOkLoading(true);
        iframeRef.current?.contentWindow.postMessage({ submit: true });
    }

    const draggleStart = (_event, uiData) => {
        const { clientWidth, clientHeight } = window.document.documentElement;
        const targetRect = draggleRef.current?.getBoundingClientRect();
        if (!targetRect) {
            return;
        }
        setDraggleBounds({
            left: -targetRect.left + uiData.x,
            right: clientWidth - (targetRect.right - uiData.x),
            top: -targetRect.top + uiData.y,
            bottom: clientHeight - (targetRect.bottom - uiData.y),
        });
    };

    const ifrmeLoad = () => {
        let iFrameHeightLast = 0;
        const iFrameTimer = setInterval(() => {
            const iFrameHeight = iframeRef.current?.contentWindow.document.body.scrollHeight;
            if (iFrameHeight > 0) {
                if (iFrameHeight === iFrameHeightLast) {
                    clearInterval(iFrameTimer);
                } else if (iFrameHeight > 400) {
                    setModalHeight(Math.ceil(iFrameHeight / 100) * 100);
                } else {
                    setModalHeight(400);
                }
                iFrameHeightLast = iFrameHeight;
            }
        }, 200);
    };

    return (
        <Modal
            title={<div
                style={{ width: '100%', cursor: 'move' }}
                onMouseOver={() => {
                    if (draggleDisabled) {
                        setDraggleDisabled(false);
                    }
                }}
                onMouseOut={() => {
                    setDraggleDisabled(true);
                }}
                onFocus={() => { }}
                onBlur={() => { }}
            >{modalConfig.title}</div>}
            centered
            destroyOnHidden={true}
            maskClosable={false}
            modalRender={(modal) => (
                <Draggable
                    disabled={draggleDisabled}
                    bounds={draggleBounds}
                    nodeRef={draggleRef}
                    onStart={(event, uiData) => draggleStart(event, uiData)}
                ><div ref={draggleRef}>{modal}</div></Draggable>
            )}
            okButtonProps={{disabled: okDisabled, loading: okLoading}}
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
                onLoad={ifrmeLoad}
            />
        </Modal>
    );
};
ModelKit.displayName = 'ModelKit';

export default ModelKit;