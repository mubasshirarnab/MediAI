import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  server: {
    port: 3000,
    host: true,
    proxy: {
      '/get_admin_data.php': {
        target: 'http://localhost:80/MediAI-main/',
        changeOrigin: true
      },
      '/check_admin_auth.php': {
        target: 'http://localhost:80/MediAI-main/',
        changeOrigin: true
      },
      '/logout.php': {
        target: 'http://localhost:80/MediAI-main/',
        changeOrigin: true
      }
    }
  },
  build: {
    outDir: 'dist',
    assetsDir: 'assets',
    sourcemap: true
  }
})
