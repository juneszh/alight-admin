import { lazy, Suspense } from 'react'
import { Spin } from 'antd';

const lazyPages = {
    Home: lazy(() => import('./page/Home.jsx')),
    Login: lazy(() => import('./page/Login.jsx')),
    Table: lazy(() => import('./page/Table.jsx')),
    Form: lazy(() => import('./page/Form.jsx')),
    Result: lazy(() => import('./page/Result.jsx')),
    Console: lazy(() => import('./page/Console.jsx')),
};

const App = props => {
    const Page = lazyPages[props.page];
    return (
        <Suspense fallback={<Spin delay={1000} style={{ left: '50%', position: 'absolute', top: '50%', transform: 'translate(-50%, -50%)' }} />}>
            <Page locale={props.locale} />
        </Suspense>
    );
};

export default App;