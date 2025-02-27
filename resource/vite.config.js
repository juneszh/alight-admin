import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react-swc'

// https://vite.dev/config/
export default defineConfig({
    base: '/alight-admin/',
    build: {
        manifest: true,
    },
    plugins: [react()],
    server: {
        cors: true,
    },
})
