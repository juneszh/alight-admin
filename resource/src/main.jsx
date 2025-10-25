import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { ConfigProvider, theme } from 'antd';
import { HappyProvider } from '@ant-design/happy-work-theme';
import '@ant-design/v5-patch-for-react-19';
import './index.css'
import App from './App'
import global from './lib/Util';
import locale from './locale';

const antdLocale = locale.antd[global.locale] ?? locale.antd['en_US'];
const appLocale = locale.app[global.locale] ?? locale.app['en_US'];
const isLight = localStorage.getItem('alight-dark') ? false : true;
const dayOfWeek = new Date().getDay();

createRoot(document.getElementById('root')).render(
    <StrictMode>
        <ConfigProvider locale={antdLocale} theme={{ algorithm: isLight ? theme.defaultAlgorithm : theme.darkAlgorithm }} >
            <HappyProvider disabled={(dayOfWeek === 0 || dayOfWeek === 6) ? false : true}>
                <App locale={appLocale} page={global.page ?? 'Login'} />
            </HappyProvider>
        </ConfigProvider>
    </StrictMode>
)
