const path = require('path');
const mix = require('laravel-mix');
require('laravel-mix-tailwind');
// require('laravel-mix-purgecss');

const noAdmin = process.env.hasOwnProperty('NO_ADMIN');

mix.webpackConfig({
  resolve: {
    alias: {
      "@": ".."
    }
  }
});
mix.options({
  processCssUrls: false,
  terser: {
    extractComments: false,
  }
});
if (!mix.inProduction()) {
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

mix
  .sass('resources/sass/post.scss', 'public/css')
  .sass('resources/sass/search.scss', 'public/css')
  .sass('resources/sass/author.scss', 'public/css')
  .sass('resources/sass/compare.scss', 'public/css')
  .sass('resources/sass/base.scss', 'public/css');

mix.js('resources/js/review.js', 'public/js');

mix
  .js(['resources/js/author/author.js'], 'public/js')
  .js(['resources/js/poem/index.js'], 'public/js/poem.js')
  .sass('resources/sass/form-common.scss', 'public/css/form.css');

mix
  .js('resources/js/me/me.js', 'public/js/me.js')
  .js('resources/js/campaign/campaign.js', 'public/js/campaign.js').vue();


if (!noAdmin) {
  mix.js(['resources/js/calendar/calendar.js'], 'public/js');
  mix
    .js(['resources/js/admin/admin.js'], 'public/js')
    .sass('resources/sass/admin/admin.scss', 'public/css');
}

mix.tailwind();
  // .purgeCss(); // purge too many things


mix.copyDirectory('resources/js/lib', 'public/js/lib');
mix.copyDirectory('resources/sass/vendor', 'public/css/vendor');

if (mix.inProduction()) {
    mix.version();
}
