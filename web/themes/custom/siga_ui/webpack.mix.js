let mix = require('laravel-mix');

mix
  .sass('scss/styles.scss', 'css')
  .setPublicPath('')
  .options({
    processCssUrls: false,
    postCss: [require('autoprefixer')],
  });
