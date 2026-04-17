import { useCallback, useEffect, useImperativeHandle, useRef, useState } from 'react';
import { Modal } from 'antd';
import Draggable from 'react-draggable';
import { localeValue } from './Util.js';


const ModelKit = ({ ref }) => {
    const [modalOpen, setModalOpen] = useState(false);
    const [modalHeight, setModalHeight] = useState(400);
    const [animateHeight, setAnimateHeight] = useState(false);
    const [modalWidth, setModalWidth] = useState(816);
    const [modalConfig, setModalConfig] = useState({ title: '', url: '' });
    const [okDisabled, setOkDisabled] = useState(true);
    const [okLoading, setOkLoading] = useState(false);
    const iframeRef = useRef(undefined);
    const iframeTimerRef = useRef(undefined);
    const currentHeightRef = useRef(400);
    const modalCallbackRef = useRef({});
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
        modalCallbackRef.current = callbackObj ?? {};
        setModalConfig({
            title: params?._title ? params?._title : localeValue(button.title),
            url: button.url + (params ? (button.url.indexOf('?') !== -1 ? '&' : '?') + new URLSearchParams(params).toString() : ''),
        });
        if (lastModal !== button.action) {
            if (button.action === 'form') {
                setAnimateHeight(false);
                setModalHeight(400);
                currentHeightRef.current = 400;
                setModalWidth(816);
            } else {
                setAnimateHeight(false);
                setModalHeight('100vh');
                setModalWidth('100vw');
            }
        }
        setLastModal(button.action);
        setOkDisabled(true);
        setOkLoading(false);
        setModalOpen(true);
    };

    const getMessage = useCallback(event => {
        if (event.origin === window.location.origin) {
            if (event.data.error !== undefined) {
                modalHide();
                if (event.data.error === 0) {
                    if (modalCallbackRef.current?.done !== undefined) {
                        modalCallbackRef.current.done(event.data);
                    }
                } else {
                    if (modalCallbackRef.current?.fail !== undefined) {
                        modalCallbackRef.current.fail(event.data);
                    }
                }
                if (modalCallbackRef.current?.always !== undefined) {
                    modalCallbackRef.current.always(event.data);
                }
            } else if (event.data.button !== undefined) {
                if (event.data.button) {
                    setOkDisabled(false);
                    setOkLoading(false);
                } else {
                    setOkDisabled(true);
                }
            }
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const modalHide = () => {
        if (iframeTimerRef.current) {
            clearInterval(iframeTimerRef.current);
            iframeTimerRef.current = undefined;
        }
        setModalOpen(false);
        if (modalCallbackRef.current?.close !== undefined) {
            modalCallbackRef.current.close();
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

    const iframeLoad = () => {
        let iFrameHeightLast = 0;
        if (iframeTimerRef.current) {
            clearInterval(iframeTimerRef.current);
        }
        iframeTimerRef.current = setInterval(() => {
            const iFrameHeight = iframeRef.current?.contentWindow.document.body.scrollHeight;
            if (iFrameHeight > 0) {
                if (iFrameHeight === iFrameHeightLast) {
                    clearInterval(iframeTimerRef.current);
                    iframeTimerRef.current = undefined;
                } else {
                    const nextHeight = iFrameHeight > 400 ? Math.ceil(iFrameHeight / 100) * 100 : 400;
                    const needAnimate = Math.abs(nextHeight - currentHeightRef.current) >= 24;
                    setAnimateHeight(needAnimate);
                    setModalHeight(nextHeight);
                    currentHeightRef.current = nextHeight;
                }
                iFrameHeightLast = iFrameHeight;
            }
        }, 200);
    };

    useEffect(() => {
        return () => {
            if (iframeTimerRef.current) {
                clearInterval(iframeTimerRef.current);
            }
            window.removeEventListener('message', getMessage);
        };
    }, [getMessage]);

    useEffect(() => {
        if (modalOpen) {
            window.addEventListener('message', getMessage);
        } else {
            window.removeEventListener('message', getMessage);
        }
        return () => {
            window.removeEventListener('message', getMessage);
        };
    }, [getMessage, modalOpen]);

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
            mask={{closable: false}}
            modalRender={(modal) => (
                <Draggable
                    disabled={draggleDisabled}
                    bounds={draggleBounds}
                    nodeRef={draggleRef}
                    onStart={(event, uiData) => draggleStart(event, uiData)}
                ><div ref={draggleRef}>{modal}</div></Draggable>
            )}
            okButtonProps={{ disabled: okDisabled, loading: okLoading }}
            onCancel={modalHide}
            onOk={modalSubmit}
            open={modalOpen}
            style={{ transition: 'width .6s ease' }}
            styles={{ body: { display: 'flex', height: modalHeight, maxHeight: 'calc(96vh - 116px)', padding: 0, transition: animateHeight ? 'height .6s ease' : 'none' } }}
            width={modalWidth}
        >
            <iframe
                ref={iframeRef}
                src={modalConfig.url}
                style={{ border: 'none', borderRadius: 8, flex: '1 1 auto', overflow: 'auto' }}
                title='modalFrame'
                onLoad={iframeLoad}
            />
        </Modal>
    );
};
ModelKit.displayName = 'ModelKit';

export default ModelKit;