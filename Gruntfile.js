module.exports = function(grunt) {

  // Project configuration.
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    
    
    copy: {
      componentsFontAwesome: {
        expand: true,
        cwd: 'bower_components/components-font-awesome/fonts/',
        src: ['*'],
        dest: 'web/fonts/font-awesome/',
      },
    },


  });

  grunt.loadNpmTasks('grunt-contrib-concat');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-contrib-sass');
  grunt.loadNpmTasks('grunt-contrib-copy');

};
