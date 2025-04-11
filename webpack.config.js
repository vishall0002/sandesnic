var Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .cleanupOutputBeforeBuild()
    .autoProvidejQuery()
    .autoProvideVariables({
        "window.Bloodhound": require.resolve('bloodhound-js'),
        "jQuery.tagsinput": "bootstrap-tagsinput",
        "Popper": ['popper.js', 'default']
    })
    .enableSassLoader()
    .enableVersioning(true)
    .createSharedEntry('js/common', './assets/js/global.js')
    .addEntry('js/app', './assets/js/app.js')
    .addEntry('js/sjcl', './assets/js/sjcl.js')
    .addEntry('js/login', './assets/js/login.js')
    .addEntry('js/generic', './assets/js/portal/generic.js')
    .addEntry('js/session', './assets/js/portal/session.js')
    .addEntry('js/ou', './assets/js/portal/ou.js')
    .addEntry('js/employee', './assets/js/portal/employee.js')
    .addEntry('js/upload', './assets/js/portal/upload.js')
    .addEntry('js/group', './assets/js/portal/group.js')
    .addEntry('js/list', './assets/js/portal/list.js')
    .addEntry('js/application', './assets/js/application/application.js')
    .addEntry('js/dashboard', './assets/js/dashboard/dashboard.js')
    .addEntry('js/dashboard-memberised', './assets/js/dashboard/dashboard-memberised.js')
    .addEntry('js/codeFinder', './assets/js/codeFinder.js')
    .addStyleEntry('css/application', './assets/scss/application.scss')
    .addStyleEntry('css/app', './assets/scss/app.scss')
    .addStyleEntry('css/dashboard', './assets/scss/dashboard.scss')
    .addLoader({
        use: 'imports-loader?define=>false',
        test: require.resolve('pace-js')
    })
    ;

module.exports = Encore.getWebpackConfig();
