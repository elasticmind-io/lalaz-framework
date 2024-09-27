import { defineConfig } from 'vite';

export default defineConfig({
  root: 'src',
  build: {
    outDir: '../public/dist',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: {
        main: './src/assets/js/main.js',
        styles: './src/assets/css/styles.css'
      },
      output: {
        entryFileNames: 'js/[name].[hash].js',
        assetFileNames: (assetInfo) => {
          if (assetInfo.name.endsWith('.css')) {
            return 'css/[name].[hash][extname]'
          }
          return '[name].[hash][extname]'
        }
      }
    }
  },
  css: {
    devSourcemap: true
  }
})
