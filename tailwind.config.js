/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './src/**/*.{html,js,php,twig}'
  ],
  darkMode: 'selector',
  theme: {
    extend: {
      colors: {
        'purple-500': '#5e17eb'
      }
    },
  },
  plugins: [],
}

