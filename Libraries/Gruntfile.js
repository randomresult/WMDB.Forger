module.exports = function(grunt) {

	// Project configuration.
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		paths: {
			root    : "Resources/",
			css  : "<%= paths.root %>Private/Stylesheets/",
			js    : "<%= paths.root %>Private/Javascripts/"
		},
		less: {
			forger: {
				options: {
					outputSourceFiles: true,
					compress: true
				},
				src : '<%= paths.css %>WMDB.Forger.less',
				dest: '<%= paths.root %>Public/css/WMDB.Forger.css'
			}
		},
		watch: {
			less: {
				files: '<%= paths.css %>*.less',
				tasks: 'less'
			}
		},
		concat: {
			options: {
				separator: ';'
			},
			dist: {
				src: [
					'bower_components/jquery/dist/jquery.js',
					'bower_components/bootstrap/dist/js/bootstrap.js',
					'<%= paths.js %>WMDB.Forger.js'
				],
				dest: '<%= paths.root %>Public/js/WMDB.Forger.js'
			}
		},
		uglify: {
			options: {
				mangle: {
					//except: ['jQuery', 'Backbone']
				}
			},
			forger: {
				files: {
					'<%= paths.root %>Public/js/WMDB.Forger.min.js': ['<%= paths.root %>Public/js/WMDB.Forger.js']
				}
			}
		}
	});

	// Register tasks
	grunt.loadNpmTasks('grunt-contrib-less');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-npm-install');
	grunt.loadNpmTasks('grunt-bower-just-install');

	/**
	 * grunt default task
	 *
	 * call "$ grunt"
	 *
	 * this will trigger the less build
	 */
	grunt.registerTask('default', ['less', 'concat', 'uglify']);
};
