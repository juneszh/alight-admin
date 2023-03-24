import React, { lazy, Suspense } from 'react';
import { createRoot } from 'react-dom/client';
import global from './components/Util';
import { ConfigProvider, Spin } from 'antd';
import dayjs from 'dayjs';
import 'antd/dist/reset.css';
import defaultAlight from './locale/en_US';
import defaultAntd from 'antd/lib/locale/en_US';

// i18n 
import localeAlight from './locale/zh_CN';
import localeAntd from 'antd/lib/locale/zh_CN';
import 'dayjs/locale/zh-cn';
dayjs.locale('zh-cn');

const components = {
    Home: lazy(() => import(/* webpackChunkName: 'home' */ './components/Home')),
    Login: lazy(() => import(/* webpackChunkName: 'login' */ './components/Login')),
    Table: lazy(() => import(/* webpackChunkName: 'table' */ './components/Table')),
    Form: lazy(() => import(/* webpackChunkName: 'form' */ './components/Form')),
    Result: lazy(() => import(/* webpackChunkName: 'result' */ './components/Result')),
    Console: lazy(() => import(/* webpackChunkName: 'console' */ './components/Console')),
};
const App = global.component ? (components[global.component] ?? null) : null;

const root = createRoot(document.getElementById('root'));
root.render(
    <React.StrictMode>
        <ConfigProvider locale={global.locale ? localeAntd : defaultAntd} >
            <Suspense fallback={<Spin delay={1000} style={{ position: 'absolute', top: '50%', left: '50%', transform: 'translate(-50%, -50%)' }} />}>
                <App locale={global.locale ? localeAlight : defaultAlight} />
            </Suspense>
        </ConfigProvider>
    </React.StrictMode>
);
