module.exports = {
  content: [
    './templates/**/*.html.twig',
    './js/**/*.js',
    './css/src/**/*.css',
  ],
  theme: {
    extend: {
      colors: {
        brand: {
          deep: '#0f1b4c',
          accent: '#ff7a18',
          soft: '#f7f9fc',
        },
      },
      fontFamily: {
        sans: ['"Work Sans"', 'system-ui', 'sans-serif'],
      },
      boxShadow: {
        dashboard: '0 15px 30px rgba(15, 27, 76, 0.15)',
      },
    },
  },
  plugins: [],
};
