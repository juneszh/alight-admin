import React, { lazy, Suspense } from 'react';
import { createRoot } from 'react-dom/client';
import global from './components/Util';
import { ConfigProvider, Spin } from 'antd';
import 'antd/dist/antd.css';
import defaultAlight from './locale/en_US';
import defaultAntd from 'antd/lib/locale/en_US';

// i18n 
import localeAlight from './locale/zh_CN';
import localeAntd from 'antd/lib/locale/zh_CN';
import 'moment/locale/zh-cn';

const components = {
    Home: lazy(() => import(/* webpackChunkName: 'home' */ './components/Home')),
    Login: lazy(() => import(/* webpackChunkName: 'login' */ './components/Login')),
    Table: lazy(() => import(/* webpackChunkName: 'table' */ './components/Table')),
    Form: lazy(() => import(/* webpackChunkName: 'form' */ './components/Form')),
    Error: lazy(() => import(/* webpackChunkName: 'error' */ './components/Error')),
    Console: lazy(() => import(/* webpackChunkName: 'console' */ './components/Console')),
};
const App = global.component ? (components[global.component] ?? null) : null;

createRoot(document.getElementById('root')).render(
    <React.StrictMode>
        <ConfigProvider locale={global.locale ? localeAntd : defaultAntd} >
            <Suspense fallback={<Spin delay={1000} style={{ position: 'absolute', top: '50%', left: '50%', transform: 'translate(-50%, -50%)' }} />}>
                <App locale={global.locale ? localeAlight : defaultAlight} />
            </Suspense>
        </ConfigProvider>
    </React.StrictMode>
);
