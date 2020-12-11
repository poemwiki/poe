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
    screens: {
      'xxs': {'min': '0px', 'max': '319px'},
      'xs': {'min': '320px', 'max': '479px'},   // Mobile devices.
      'sm': {'min': '480px', 'max': '767px'},   // iPads, Tablets.
      'md': {'min': '768px', 'max': '1023px'},  // Small screens, laptops.
      'lg': {'min': '1024px', 'max': '1280px'}, // Desktops, large screens.
      'xl': {'min': '1281px'}                   // Extra large screens, TV.
    },
    // colors: colors,
    // textColors: colors,
    extend: {},
  },
  variants: {
    extend: {
      backgroundColor: ['odd', 'even'],
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
  ],
}
