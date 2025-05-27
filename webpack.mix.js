const mix = require("laravel-mix")

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 */

mix
  .js("resources/js/app.js", "public/js")
  .js("resources/js/virtual-try-on.js", "public/js")
  .postCss("resources/css/app.css", "public/css", [require("tailwindcss"), require("autoprefixer")])
  .options({
    processCssUrls: false,
  })

// Configuración para desarrollo
if (!mix.inProduction()) {
  mix.sourceMaps()
}

// Configuración para producción
if (mix.inProduction()) {
  mix.version()
}

// Configuración de webpack
mix.webpackConfig({
  stats: {
    children: true,
  },
})
