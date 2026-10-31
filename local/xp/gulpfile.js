/* eslint-disable */

const gulp = require('gulp');
const replace = require('gulp-replace');
const exec = require('child_process').exec;

const jsAmdPaths = ['./amd/src/*.js'];

/** Moodle. */

function moodleGruntAmd(cb) {
  exec('grunt amd', function (err, stdout, stderr) {
    cb(err);
  });
}

const moodleAmd = gulp.series(moodleGruntAmd);

/** Watch. */

function watchAmd(cb) {
  return gulp.watch(jsAmdPaths, gulp.series(moodleAmd));
}

const watchJs = gulp.parallel(watchAmd);

exports['dist'] = gulp.series(moodleAmd);
exports['watch'] = gulp.parallel(watchJs);
