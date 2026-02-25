import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vite.dev/config/
export default defineConfig({
    plugins: [
        react({
            babel: {
                plugins: [['babel-plugin-react-compiler']],
            },
        }),
    ],
    base: '/alight-admin/',
    build: {
        manifest: true,
    },
    server: {
        cors: true,
    },
})
