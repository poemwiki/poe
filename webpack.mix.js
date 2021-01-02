const path = require('path');
const mix = require('laravel-mix');
require('laravel-mix-tailwind');
require('laravel-mix-purgecss');


mix.webpackConfig({
  resolve: {
    alias: {
      "@": ".."
    }
  },
});
mix.options({
  processCssUrls: false
});
if ( !mix.inProduction()) {
  mix.webpackConfig({
    devtool: 'inline-source-map'
  })
}


/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/js/app.js', 'public/js')
  .sass('resources/sass/app.scss', 'public/css')
  .sass('resources/sass/post.scss', 'public/css')
  .sass('resources/sass/search.scss', 'public/css');


mix.js('resources/js/review.js', 'public/js');

mix.js(['resources/js/admin/admin.js'], 'public/js')
  .sass('resources/sass/admin/admin.scss', 'public/css');

mix.js(['resources/js/author/author.js'], 'public/js')
  .js(['resources/js/poem/index.js'], 'public/js/poem.js')
  .sass('resources/sass/form.scss', 'public/css');

mix.js(['resources/js/calendar/calendar.js'], 'public/js')
  .sass('resources/sass/calendar.scss', 'public/css')
  .tailwind()
  .purgeCss();


mix.copyDirectory('resources/js/lib', 'public/js/lib');
mix.copyDirectory('resources/sass/vendor', 'public/css/vendor');

if (mix.inProduction()) {
    mix.version();
}
