import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { ConfigProvider } from 'antd';
import './index.css'
import App from './App.jsx'
import global from './lib/Util.js';

// Default language
import defaultAlight from './locale/en_US';
import defaultAntd from 'antd/lib/locale/en_US';

// Locale language 
import localeAlight from './locale/zh_CN';
import localeAntd from 'antd/lib/locale/zh_CN';

createRoot(document.getElementById('root')).render(
    <StrictMode>
        <ConfigProvider locale={global.locale ? localeAntd : defaultAntd} >
            <App locale={global.locale ? localeAlight : defaultAlight} page={global.page ?? 'Login'} />
        </ConfigProvider>
    </StrictMode>
)
