import React from 'react'
import ReactDOM from 'react-dom/client'
import { ConfigProvider } from 'antd';
import dayjs from 'dayjs';
import 'antd/dist/reset.css';
import App from './App';
import global from './lib/Util';

// Default language
import defaultAlight from './locale/en_US';
import defaultAntd from 'antd/lib/locale/en_US';

// Locale language 
import localeAlight from './locale/zh_CN';
import localeAntd from 'antd/lib/locale/zh_CN';
import 'dayjs/locale/zh-cn';
dayjs.locale('zh-cn');

ReactDOM.createRoot(document.getElementById('root')).render(
    <React.StrictMode>
        <ConfigProvider locale={global.locale ? localeAntd : defaultAntd} >
            <App locale={global.locale ? localeAlight : defaultAlight} page={global.page ?? 'Login'} />
        </ConfigProvider>
    </React.StrictMode>
)
