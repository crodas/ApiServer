var gulp = require('gulp');
var bower = require('gulp-bower');
var concat = require('gulp-concat');
var uglify = require('gulp-uglify');

gulp.task('deps', function() {
    return bower('lib/')
        .pipe(gulp.dest('lib/'))
});

gulp.task('dist-concat', function() {
    return gulp.src(['lib/bluebird/js/browser/bluebird.js', 'client.js'])
        .pipe(concat('api.client.js'))
        .pipe(gulp.dest('dist'));
});

gulp.task('dist', ['dist-concat'], function() {
    return gulp.src("dist/*.js")
        .pipe(uglify({mangle: true}))
        .pipe(gulp.dest('dist.min'));
        
});
