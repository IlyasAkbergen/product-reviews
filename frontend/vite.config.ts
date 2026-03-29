import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  server: {
    host: '0.0.0.0',
    port: 5173,
  },
  preview: {
    allowedHosts: [
      'frontend-production-e31e.up.railway.app',
      '.akbergen.info',
    ],
  },
})
