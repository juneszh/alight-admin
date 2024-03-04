import React from 'react'
import ReactDOM from 'react-dom/client'
import { ConfigProvider } from 'antd';
import 'antd/dist/reset.css';
import App from './App';
import global from './lib/Util';

// Default language
import defaultAlight from './locale/en_US';
import defaultAntd from 'antd/lib/locale/en_US';

// Locale language 
import localeAlight from './locale/zh_CN';
import localeAntd from 'antd/lib/locale/zh_CN';

ReactDOM.createRoot(document.getElementById('root')).render(
    <React.StrictMode>
        <ConfigProvider locale={global.locale ? localeAntd : defaultAntd} >
            <App locale={global.locale ? localeAlight : defaultAlight} page={global.page ?? 'Login'} />
        </ConfigProvider>
    </React.StrictMode>
)
