/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,js,ts,jsx,tsx}'],
  theme: {
    extend: {
      colors: {
        cf: {
          red: '#CF2E2E',
          dark: '#0A0A0A'
        }
      }
    }
  },
  plugins: []
};
