import { lazy, Suspense } from 'react'
import { Spin } from 'antd';

const lazyPages = {
    Home: lazy(() => import('./page/Home')),
    Login: lazy(() => import('./page/Login')),
    Table: lazy(() => import('./page/Table')),
    Form: lazy(() => import('./page/Form')),
    Result: lazy(() => import('./page/Result')),
    Console: lazy(() => import('./page/Console')),
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