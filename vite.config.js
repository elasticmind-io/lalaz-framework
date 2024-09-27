import fs from 'fs'
import path from 'path'
import copy from 'rollup-plugin-copy'
import { defineConfig } from 'vite'

function updateManifestWithImages({ outDir, imageDir }) {
  return {
    name: 'add-images-to-manifest',
    closeBundle: () => {
      const manifestPath = path.resolve(outDir, 'manifest.json')

      if (fs.existsSync(manifestPath)) {
        const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf-8'))
        const images = fs.readdirSync(imageDir)

        images.forEach((image) => {
          const imageName = path.basename(image)
          manifest[`assets/img/${imageName}`] = { file: `img/${imageName}` }
        })

        fs.writeFileSync(manifestPath, JSON.stringify(manifest, null, 2))
      } else {
        console.error(`[add-images-to-manifest] manifest.json não encontrado em ${manifestPath}`)
      }
    }
  }
}

export default defineConfig({
  root: 'src',
  build: {
    outDir: '../public/dist',
    emptyOutDir: true,
    manifest: 'manifest.json',
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
  plugins: [
    copy({
      targets: [
        {
          src: path.resolve('./src/assets/img/**/*'),
          dest: path.resolve('./public/dist/img')
        }
      ],
      hook: 'writeBundle',
    }),
    updateManifestWithImages({
      outDir: path.resolve('./public/dist'),
      imageDir: path.resolve('./public/dist/img')
    })
  ],
})
