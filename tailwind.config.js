module.exports = {
  purge: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
  ],
  darkMode: false, // or 'media' or 'class'
  theme: {
    fontSize: {
      'xs': '12px',
      'sm': '14px',
      'tiny': '14px',
      'base': '16px',
      'lg': '1.125 * 16px',
      'xl': '1.25 * 16px',
      '2xl': '1.32 * 16px',
      '3xl': '1.5 * 16px',
      '4xl': '2.25 * 16px',
      '5xl': '3 * 16px',
      '6xl': '4 * 16px',
      '7xl': '5 * 16px',
    },
    extend: {},
  },
  variants: {
    extend: {
      backgroundColor: ['odd', 'even'],
    },
  },
  plugins: [],
}
