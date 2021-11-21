// More or less relevant references:
// https://laravel.com/docs/5.7/mix
// https://northcreation.agency/laravel-mix-with-silverstripe/
// https://github.com/gorriecoe/silverstripe-mix/blob/master/package.json

const mix = require('laravel-mix');

mix.options({
    // See: https://laravel-mix.com/docs/6.0/options
//    processCssUrls: false,
});

// Generate sourcemaps in dev (not in prod)
mix.sourceMaps(null, 'source-map');

// Typical Silverstripe client-dir setup
mix.setPublicPath('client/dist');

// this keeps relative image urls in js/scss intact:
// else they're converted to absolute (unless processCssUrls is set to false, but then no images will be copied to dist/images either...)
mix.setResourceRoot('../');

// SCSS -> CSS
mix.sass('client/src/scss/namedlinkfield.scss', 'css');

// mix.scripts = basic concattenation
// mix.babel = concattenation + babel (ES2015 -> vanilla)
// mix.js = components, react, vue, etc -> include { "presets": ["@babel/preset-react"] } in .babelrc for correct transpilation (or add .vue()/.react() in mix.js)
mix.js([ 'client/src/js/bundle.js', ], 'js');
// extract() results in main.js plus separate vendor.js () + manifest.js which all three have to get loaded
// mix.js([ 'client/src/js/one.js', 'client/src/js/two.js', ], 'js/main.js').extract();

mix.autoload({
    // make webpack prepend var $ = require('jquery') to every $, jQuery or window.jQuery
    // (this will result in jQuery being compiled-in, even though it may be provided externally)
//    jquery: ['$', 'jQuery', 'window.jQuery'],
//    underscore: ['_', 'underscore'],
});

mix.webpackConfig({
    externals: {
        // Externals will not be compiled-in (eg import $ from 'jQuery', combined with external 'jquery': 'jQuery' means jQuery gets provided externally)
        // For external modules provided by SilverStripe see: https://github.com/silverstripe/webpack-config/blob/master/js/externals.js
        'jquery': 'jQuery',
        'react': 'React',
        'lib/Injector': 'Injector',
        'components/FieldHolder/FieldHolder': 'FieldHolder',
        'components/SingleSelectField/SingleSelectField': 'SingleSelectField',
    }
});
