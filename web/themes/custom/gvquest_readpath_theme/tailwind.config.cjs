module.exports = {
  content: [
    './templates/**/*.html.twig',
    './js/**/*.js',
    './css/src/**/*.css',
  ],
  theme: {
    extend: {
      colors: {
        sana: {
          night: '#04060f',
          surface: '#0b1020',
          card: '#111a2f',
          accent: '#5de4c7',
          accentSoft: '#8b5cf6',
          text: '#f8fbff',
          muted: '#8a94ad',
          pill: '#181f33',
        },
      },
      fontFamily: {
        sans: ['"Poppins"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
      },
      boxShadow: {
        dashboard: '0 25px 45px rgba(4, 6, 15, 0.35)',
        glow: '0 10px 35px rgba(93, 228, 199, 0.4)',
      },
      borderRadius: {
        '3xl': '1.75rem',
      },
    },
  },
  plugins: [],
};
