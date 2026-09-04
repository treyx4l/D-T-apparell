/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './*.php',
    './auth/**/*.php',
    './dt-admin/**/*.php',
    './includes/**/*.php',
    './js/**/*.js',
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        accent: {
          DEFAULT: '#ffde59',
          dark: '#e6c84f',
        },
        ink: '#111111',
        muted: '#6b7280',
        surface: '#fafafa',
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
      },
    },
  },
  plugins: [],
};
