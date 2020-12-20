module.exports = {
  purge: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
  ],
  darkMode: false, // or 'media' or 'class'
  colors: {
    primary: '#004afe',
    // ...
  },
  theme: {
    fontSize: {
      xs: '12px',
      sm: '14px',
      tiny: ['1.4rem', '2rem'],
      base: ['1.6rem', '2.4rem'],
      lg: ['1.8rem', '3.6rem'],
      xl: ['2.2rem', '5.2rem'],
      '2xl': ['3.1rem', '6rem'],
      '3xl': ['3.84rem', '7.6rem'],
      h2: ['1.71rem', '3.4rem'],
    },
    screens: {
      'xs': {'min': '320px'},   // Mobile devices.
      'sm': {'min': '480px'},   // iPads, Tablets.
      'md': {'min': '768px'},  // Small screens, laptops.
      'lg': {'min': '1024px'}, // Desktops, large screens.
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
