import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { ConfigProvider } from 'antd';
import './index.css'
import App from './App'
import global from './lib/Util';
import locale from './locale';

const antdLocale = locale.antd[global.locale] ?? locale.antd['en_US'];
const appLocale = locale.app[global.locale] ?? locale.app['en_US'];

createRoot(document.getElementById('root')).render(
    <StrictMode>
        <ConfigProvider locale={antdLocale} >
            <App locale={appLocale} page={global.page ?? 'Login'} />
        </ConfigProvider>
    </StrictMode>
)
