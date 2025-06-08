const gulp = require('gulp');
const exec = require('child_process').exec;
const postcss = require('gulp-postcss');
const webpack = require('webpack');

const webpackDevConfig = require('./webpack.dev.js');
const webpackProdConfig = require('./webpack.prod.js');

const cssPaths = ['./css/styles.css'];
const jsAmdPaths = [
    './amd/src/*.js',
];
const jsTsPaths = [
    './ts/src/**/*.{js,ts,tsx}',
];
const tailwindConfigPath = './tailwind.config.js';
const tailwindContentPaths = require('./tailwind.config').content;

/** JS. */

const jsBuildDev = gulp.series(webpackDev, moodleJsBuild);

const jsBuild = gulp.series(webpackProd, moodleJsBuild, moodlePurgeCaches);

function moodlePurgeCaches(cb) { // eslint-disable-line
    if (!process.env.PURGE_CACHES) {
        return cb();
    }
    exec('php ../../admin/cli/purge_caches.php', function(err, stdout, stderr) {
        cb(err);
    });
}

function moodleJsBuild(cb) { // eslint-disable-line
    exec('grunt amd', function(err, stdout, stderr) {
        cb(err);
    });
}

/** Webpack. */

const webpackBuildFromConfig = function(config) {
    return new Promise((resolve, reject) => { // eslint-disable-line
        webpack(config, (err, stats) => {
            if (err) {
                return reject(err);
            } else if (stats.hasErrors()) {
                return reject(new Error(stats.compilation.errors.map((e) => e.message).join('\n')));
            }
            return resolve();
        });
    });
};

function webpackDev(cb) { // eslint-disable-line
    return webpackBuildFromConfig(webpackDevConfig);
}

function webpackProd(cb) { // eslint-disable-line
    return webpackBuildFromConfig(webpackProdConfig);
}

/** CSS. */

const cssBuild = gulp.series(tailwindBuild, moodlePurgeCaches);

function tailwindBuild(cb) { // eslint-disable-line
    // Build Tailwind. This behaves differently depending on NODE_ENV.
    return gulp.src(cssPaths).pipe(postcss()).pipe(gulp.dest('.'));
}

/** Watch. */

function watchJs(cb) { // eslint-disable-line
    return gulp.watch([].concat(jsAmdPaths, jsTsPaths), gulp.series(jsBuildDev));
}

function watchCss(cb) { // eslint-disable-line
    return gulp.watch([tailwindConfigPath].concat(tailwindContentPaths, cssPaths), cssBuild);
}

exports.dist = gulp.series(cssBuild, jsBuild);
exports['dist:dev'] = gulp.series(cssBuild, jsBuildDev);
exports.watch = gulp.parallel(cssBuild, watchCss, jsBuildDev, watchJs);
exports['watch:css'] = gulp.series(watchCss);
