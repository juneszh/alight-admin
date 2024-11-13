import { message } from 'antd';

const $global = window.$global ?? {};

let i18n = {};

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

const ifResult = (ifKeyValue, values) => {
    let result = true;
    if (notEmpty(ifKeyValue)) {
        for (const [ifKey, ifValue] of Object.entries(ifKeyValue)) {
            if (Array.isArray(ifValue)) {
                if (ifValue.map(String).indexOf(numberToString(values[ifKey])) === -1) {
                    result = false;
                    continue;
                }
            } else {
                let ifSign = ifKey.slice(-3);
                if (ifSign === '>=]') {
                    if (values[ifKey.slice(0, -4)] < ifValue) {
                        result = false;
                        continue;
                    }
                } else if (ifSign === '<=]') {
                    if (values[ifKey.slice(0, -4)] > ifValue) {
                        result = false;
                        continue;
                    }
                } else if (ifSign === '[>]') {
                    if (values[ifKey.slice(0, -3)] <= ifValue) {
                        result = false;
                        continue;
                    }
                } else if (ifSign === '[<]') {
                    if (values[ifKey.slice(0, -3)] >= ifValue) {
                        result = false;
                        continue;
                    }
                } else if (ifSign === '[!]') {
                    if (numberToString(values[ifKey.slice(0, -3)]) === numberToString(ifValue)) {
                        result = false;
                        continue;
                    }
                } else {
                    if (numberToString(values[ifKey]) !== numberToString(ifValue)) {
                        result = false;
                        continue;
                    }
                }
            }
        }
    }
    return result;
}

const inIframe = () => {
    try {
        return window.self !== window.top;
    } catch {
        return false;
    }
};

const localeInit = i18nNew => {
    i18n = i18nNew;
};

const localeValue = key => {
    return (key[0] === ':' && i18n[key] !== undefined) ? i18n[key] : key;
};

const notEmpty = obj => {
    return obj && (Object.getPrototypeOf(obj) === Object.prototype || Object.getPrototypeOf(obj) === Array.prototype) && Object.keys(obj).length !== 0;
};

const numberToString = value => {
    if (typeof value === 'object' && notEmpty(value)) {
        for (const [valueKey, valueValue] of Object.entries(value)) {
            value[valueKey] = numberToString(valueValue);
        }
    } else if (typeof value === 'number') {
        value = value.toString();
    }
    return value;
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

export default $global;
export { ajax, ifResult, inIframe, localeInit, localeValue, notEmpty, numberToString, postMessage, redirect };