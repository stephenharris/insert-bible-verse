module.exports = function( grunt ) {

require('load-grunt-tasks')(grunt);

// Project configuration
grunt.initConfig( {
	pkg:    grunt.file.readJSON( 'package.json' ),
	jshint: {
		options: {
			reporter: require('jshint-stylish'),
			globals: {
				"BIBLE_VERSE_SCRIPT_DEBUG": false,
			},
			 '-W020': true, //Read only - error when assigning EO_SCRIPT_DEBUG a value.
		},
		all: [ 'assets/js/*.js', '!assets/js/*.min.js', '!assets/js/vendor/**.js' ]
  	},

	uglify: {
		all: {
			files: [{
				expand: true,     // Enable dynamic expansion.
				src: ['assets/js/*.js', '!assets/js/*.min.js', '!assets/vendor/*.js'],
				ext: '.min.js',   // Dest filepaths will have this extension.
			}]
		},
		options: {
			compress: {
				global_defs: {
					"BIBLE_VERSE_SCRIPT_DEBUG": false,
				},
				dead_code: true
		     },
			banner: '/*! <%= pkg.title %> - v<%= pkg.version %>\n' +
				' * <%= pkg.homepage %>\n' +
				' */\n',
			mangle: {
				except: ['jQuery']
			}
		},
	},

		
	cssmin: {
		options: {
			banner: '/*! <%= pkg.title %> - v<%= pkg.version %>\n' +
				' * <%= pkg.homepage %>\n' +
				' */\n'
		},
		minify: {
			expand: true,	
			src: ['assets/css/*.css', '!assets/css/*.min.css'],
			ext: '.min.css'
		}
	},
		
	clean: {
		build: ['build/<%= pkg.name %>'],//Clean up build folder
		css: [ 'assets/css/*.min.css', 'assets/css/*-rtl.css' ],
		js: [ 'assets/js/*.min.js' ],
		i18n: [ 'languages/*.mo', 'languages/*.pot' ] 
	},
		
	copy: {
		// Copy the plugin to a versioned release directory
		build: {
			src:  [
				'**',
				'!node_modules/**',
				'!build/**',
				'!wp-org-assets/**',
				'!.git/**',
				'!assets/scripture/src/**',
				'!Gruntfile.js',
				'!package.json',
				'!.gitignore',
				'!.gitmodules',
				'!tests/**',
				'!vendor/**',
				'readme.md',
				'!*~'
			],
			dest: 'build/<%= pkg.name %>/'
		}	
	},
		
	compress: {
		build: {
			options: {
				mode: 'zip',
				archive: './build/<%= pkg.name %>.<%= pkg.version %>.zip'
			},
			expand: true,
			cwd: 'build/<%= pkg.name %>/',
			src: ['**/*'],
			dest: '<%= pkg.name %>/'
		},	
	},

	po2mo: {
		files: {
        	src: 'languages/*.po',
			expand: true,
		},
	},

	pot: {
		options:{
	       	text_domain: '<%= pkg.name %>',
	       	dest: 'languages/',
	       	keywords: [
				'__:1',
				'_e:1',
				'_x:1,2c',
				'esc_html__:1',
				'esc_html_e:1',
				'esc_html_x:1,2c',
				'esc_attr__:1', 
				'esc_attr_e:1', 
				'esc_attr_x:1,2c', 
				'_ex:1,2c',
				'_n:1,2', 
				'_nx:1,2,4c',
				'_n_noop:1,2',
				'_nx_noop:1,2,3c'
			],
    	},
	    files:{
	    	src:  [
	    	    '**/*.php',
	    	    '!node_modules/**',
	    	    '!build/**',
	    	    '!tests/**',
	    	    '!vendor/**',
	    	    '!*~',
	    	],
	    	expand: true,
	    }
	},

	checktextdomain: {
		options:{
			correct_domain: true,
			text_domain: '<%= pkg.name %>',
			keywords: [
				'__:1,2d',
				'_e:1,2d',
				'_x:1,2c,3d',
				'esc_html__:1,2d',
				'esc_html_e:1,2d',
				'esc_html_x:1,2c,3d',
				'esc_attr__:1,2d', 
				'esc_attr_e:1,2d', 
				'esc_attr_x:1,2c,3d', 
				'_ex:1,2c,3d',
				'_n:1,2,4d', 
				'_nx:1,2,4c,5d',
				'_n_noop:1,2,3d',
				'_nx_noop:1,2,3c,4d'
			],
		},
		files: {
			src:  [
				'**/*.php',
				'!node_modules/**',
				'!build/**',
				'!tests/**',
				'!vendor/**',
				'!*~',
			],
			expand: true,
		},
	},

	wp_readme_to_markdown: {
		convert:{
			files: {
				'readme.md': 'readme.txt'
			},
		},
	},

	checkrepo: {
		deploy: {
			tag: {
				eq: '<%= pkg.version %>',    // Check if highest repo tag is equal to pkg.version
			},
			tagged: true, // Check if last repo commit (HEAD) is not tagged
			clean: true,   // Check if the repo working directory is clean
        }
	},
	
    wp_deploy: {
    	deploy:{
            options: {
        		svn_user: 'stephenharris',
        		plugin_slug: 'insert-bible-verse',
        		build_dir: 'build/<%= pkg.name %>/',
        		assets_dir: 'wp-org-assets/',
        		max_buffer: 1024*1024
            },
    	}
    },
    

} );
	
	// Default task.
	grunt.registerTask( 'default', ['jshint', 'uglify', 'cssmin'] );
	
	grunt.registerTask( 'test', [ 'jshint' ] );

	grunt.registerTask( 'build', [ 'test', 'clean', 'uglify', 'cssmin', 'pot', 'po2mo', 'wp_readme_to_markdown', 'copy' ] );

	grunt.registerTask( 'deploy', [ 'checkbranch:master', 'checkrepo:deploy', 'build', 'wp_depoy', 'compress' ] );

	grunt.util.linefeed = '\n';
};
