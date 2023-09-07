import { message } from 'antd';

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

const redirect = url => {
    if (inIframe()) {
        window.top.location.replace(url);
    } else {
        window.location.replace(url);
    }
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

export default $global;
export { localeInit, localeValue, inIframe, notEmpty, postMessage, redirect, ajax };