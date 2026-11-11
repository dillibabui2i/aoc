const mix = require('laravel-mix')
const path = require('path')

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
    .postCss('resources/css/custom.css', 'public/css', [
        //
    ])
    .postCss('resources/css/myaccount.css', 'public/css', [
        //
    ])
    .postCss('resources/css/extra_style.css', 'public/css', [
        //
    ])
    .postCss('resources/css/map.css', 'public/css', [
        //
    ])
    .postCss('resources/css/bootstrap-editable.css', 'public/css', [
        //
    ])
    .postCss('resources/css/bootstrap-multiselect.min.css', 'public/css', [
        //
    ])
    .postCss('resources/css/bootstrap-datepicker.min.css', 'public/css', [
        //
    ])
    .postCss('resources/css/jquery.timepicker.min.css', 'public/css', [
        //
    ])
    .postCss('resources/css/tribute.css', 'public/css', [
        //
    ])
    .postCss('resources/css/messageWidget.css', 'public/css', [
        //
    ])
    .postCss('resources/css/jquery.datetimepicker.min.css', 'public/css', [
        //
    ])
    .postCss('resources/css/choices.min.css', 'public/css', [
        //
    ])
    .postCss('resources/css/nthlanding_extra_style.css', 'public/css', [
        //
    ])
    .postCss('resources/css/nthlanding.css', 'public/css', [
        //
    ])
    .postCss('resources/css/components/priorityPayersConfig/priority-payer-config.css', 'public/css/components/priorityPayersConfig', [
        //
    ])

mix.js('resources/js/map.js', 'public/js')
mix.js('resources/js/bootstrap-editable.min.js', 'public/js')
mix.js('resources/js/editable.js', 'public/js')
mix.js("resources/js/globalLocationFilter.js", "public/js");
mix.copy('resources/js/bootstrap-multiselect.min.js', 'public/js')
mix.copy('resources/js/jquery.timepicker.min.js', 'public/js')
mix.copy('resources/js/bootstrap-datepicker.min.js', 'public/js')
mix.copy('resources/js/jquery.datetimepicker.full.min.js', 'public/js')
mix.copy('resources/js/push.min.js', 'public/js')
mix.copy('resources/js/confetti.js', 'public/js')
mix.copy('resources/js/choices.min.js', 'public/js')
mix.copy('resources/js/custom.js', 'public/js')
mix.copy('resources/js/nth-schedule-select.js', 'public/js')
mix.copy('resources/js/tribute.js', 'public/js')
mix.copy('resources/js/ESSLog.js', 'public/js')
mix.copy('resources/images/LoaderIcon.gif', 'public/images')
mix.copy('resources/css/CedarvilleCursive-Regular.ttf', 'public/css')
mix.copy('resources/js/dataTables.responsive.min.js', 'public/js')
mix.js('resources/js/gp/gp-calculator.js', 'public/js/gp')
mix.copy(
    'resources/js/components/consentTracking/consentTemplateEditor.js',
    'public/js/components/consentTracking',
)
mix.copy(
    'resources/js/components/consentTracking/consentTemplateTable.js',
    'public/js/components/consentTracking',
)
mix.copy(
    'resources/js/components/priorityPayersConfig/priority-payer-config.js',
    'public/js/components/priorityPayersConfig',
)
mix.version()
mix.js('resources/js/vue/app.js', 'public/js/vue-app.js').vue()

mix.alias({
    '@': path.resolve(__dirname, 'resources/js/vue'),
    // Ziggy ships its JS helper inside the composer package, so aliasing to it
    // keeps the PHP and JS halves on one version instead of adding an npm
    // dependency that could drift from composer.lock.
    'ziggy-js': path.resolve(__dirname, 'vendor/tightenco/ziggy'),
})

mix.options({
    sass: {
        api: 'modern',
        sassOptions: {
            loadPaths: [path.resolve(__dirname, 'node_modules')],
            silenceDeprecations: ['legacy-js-api'],
        },
    },
})

mix.webpackConfig({
    module: {
        rules: [
            {
                test: /\.js$/,
                include: [
                    path.resolve(__dirname, 'node_modules/@angels-of-care/ui'),
                ],
                resolve: { fullySpecified: false },
                use: { loader: 'babel-loader' },
            },
        ],
    },
})

// Laravel Mix hardcodes sass-loader options for `<style lang="scss">` blocks
// inside .vue SFCs (see CssWebpackConfig.js) and ignores mix.options({ sass }),
// so those blocks always use Dart Sass's deprecated legacy render API and
// print this deprecation warning. It's non-fatal noise from a third-party
// package's styles, not our own sass — silence it instead of patching
// Mix's internal loader rules (that broke bootstrap's `~` imports in app.scss).
mix.webpackConfig({
    ignoreWarnings: [{ message: /legacy JS API is deprecated/ }],
})
