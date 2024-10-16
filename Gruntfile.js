module.exports = function(grunt) {

	const sass = require( 'node-sass' );
	require( 'load-grunt-tasks' )( grunt );

	// Project configuration.
	grunt.initConfig( {
		pkg: grunt.file.readJSON( 'package.json' ),

		// Setting folder templates.
		dirs: {
			css: 'assets/css',
			fonts: 'assets/fonts',
			js: 'assets/js',
			php: 'includes',
			scss: 'assets/scss'
		},

		// Compile all .scss files.
		sass: {
			compile: {
				options: {
					implementation: sass
				},
				files: [
				{
					expand: true,
					cwd: '<%= dirs.scss %>/admin/',
					src: ['*.scss'],
					dest: '<%= dirs.css %>/admin/',
					ext: '.css'
				},
				{
					expand: true,
					cwd: '<%= dirs.scss %>/frontend',
					src: ['*.scss'],
					dest: '<%= dirs.css %>/frontend',
					ext: '.css'
				}
				]
			}
		},

		// Generate RTL .css files.
		rtlcss: {
			dist: {
				expand: true,
				src: [
				'<%= dirs.css %>/admin/*.css',
				'!<%= dirs.css %>/admin/*-rtl.css',
				'!<%= dirs.css %>/admin/*.min.css',
				'<%= dirs.css %>/frontend/*.css',
				'!<%= dirs.css %>/frontend/*-rtl.css',
				'!<%= dirs.css %>/frontend/*.min.css'
				],
				ext: '-rtl.css'
			}
		},

		// Minify all .css files.
		cssmin: {
			dist: {
				files: [{
					expand: true,
					src: [
					'<%= dirs.css %>/admin/*.css',
					'!<%= dirs.css %>/admin/*.min.css',
					'<%= dirs.css %>/frontend/*.css',
					'!<%= dirs.css %>/frontend/*.min.css'
					],
					ext: '.min.css'
				}]
			}
		},

		// Autoprefixer.
		postcss: {
			options: {
				processors: [
				require( 'autoprefixer' )
				]
			},
			dist: {
				src: [
				'<%= dirs.css %>/admin/*.css',
				'<%= dirs.css %>/frontend/*.css'
				]
			}
		},

		// Minify .js files.
		uglify: {
			options: {
				banner: '/*! <%= pkg.title %> <%= pkg.version %> <%= grunt.template.today("yyyy-mm-dd HH:MM") %> */\n',
				ie8: true,
				parse: {
					strict: false
				},
				output: {
					comments : /@license|@preserve|^!/
				}
			},
			admin: {
				files: [{
					expand: true,
					cwd: '<%= dirs.js %>/admin/',
					src: [
					'*.js',
					'!*.min.js'
					],
					dest: '<%= dirs.js %>/admin/',
					ext: '.min.js'
				}]
			},
			frontend: {
				files: [{
					expand: true,
					cwd: '<%= dirs.js %>/frontend/',
					src: [
					'*.js',
					'!*.min.js'
					],
					dest: '<%= dirs.js %>/frontend/',
					ext: '.min.js'
				}]
			}
		},

		// JavaScript linting with JSHint.
		jshint: {
			options: {
				reporter: require( 'jshint-stylish' ),
				globals: {
					"EO_SCRIPT_DEBUG": false,
				},
				'-W099': true, //Mixed spaces and tabs
				'-W083': true,//TODO Fix functions within loop
				'-W082': true, //Todo Function declarations should not be placed in blocks
				'-W020': true, //Read only - error when assigning EO_SCRIPT_DEBUG a value.
				jshintrc: '.jshintrc'
			},
			all: [
			'<%= dirs.js %>/admin/*.js',
			'!<%= dirs.js %>/admin/*.min.js',
			'<%= dirs.js %>/frontend/*.js',
			'!<%= dirs.js %>/frontend/*.min.js'
			]
		},

		// Watch changes for assets.
		watch: {
			css: {
				files: [
				'<%= dirs.scss %>/admin/*.scss',
				'<%= dirs.scss %>/frontend/*.scss'
				],
				tasks: ['sass', 'rtlcss', 'postcss', 'cssmin']
			},
			js: {
				files: [
				'<%= dirs.js %>/admin/*js',
				'<%= dirs.js %>/frontend/*js',
				'!<%= dirs.js %>/admin/*.min.js',
				'!<%= dirs.js %>/frontend/*.min.js'
				],
				tasks: ['jshint', 'uglify']
			}
		},

		// # Build and release

		// Remove any files in zip destination and build folder
		clean: {
			main: ['build/**']
		},

		// Copy the plugin into the build directory
		copy: {
			main: {
				src: [
				'**',
				'!node_modules/**',
				'!build/**',
				'!deploy/**',
				'!svn/**',
				'!**/*.zip',
				'!**/*.bak',
				'!wp-assets/**',
				'!package-lock.json',
				'!nyp-logo.png',
				'!screenshots/**',
				'!.git/**',
				'!**.md',
				'!Gruntfile.js',
				'!package.json',
				'!gitcreds.json',
				'!.gitcreds',
				'!.gitignore',
				'!.gitmodules',
				'!sftp-config.json',
				'!**.sublime-workspace',
				'!**.sublime-project',
				'!deploy.sh',
				'!**/*~',
				'!phpcs.xml',
				'!composer.json',
				'!composer.lock',
				'!vendor/**',
				'!none',
				'!includes/compatibility/backcompatibility/**'
				],
				dest: 'build/'
			}
		},

		// Make a zipfile.
		compress: {
			main: {
				options: {
					mode: 'zip',
					archive: 'deploy/<%= pkg.name %>-<%= pkg.version %>.zip'
				},
				expand: true,
				cwd: 'build/',
				src: ['**/*'],
				dest: '/<%= pkg.name %>'
			}
		},

		// # Internationalization

		// Add text domain
		addtextdomain: {
			options: {
				textdomain: '<%= pkg.name %>'    // Project text domain.
			},
			target: {
				files: {
					src: ['*.php', '**/*.php', '**/**/*.php', '!node_modules/**', '!deploy/**']
				}
			}
		},

		// Generate .pot file
		makepot: {
			target: {
				options: {
					domainPath: '/languages', // Where to save the POT file.
					exclude: ['deploy','build','node_modules'], // List of files or directories to ignore.
					mainFile: '<%= pkg.name %>.php', // Main project file.
					potFilename: '<%= pkg.name %>.pot', // Name of the POT file.
					type: 'wp-plugin', // Type of project (wp-plugin or wp-theme).
					potHeaders: {
						'Report-Msgid-Bugs-To': '<%= pkg.bugs.issues %>'
					}
				}
			}
		},

		// bump version numbers (replace with version in package.json)
		replace: {
			Version: {
				src: [
				'readme.txt',
				'<%= pkg.name %>.php'
				],
				overwrite: true,
				replacements: [
					{
						from: /Stable tag:.*$/m,
						to: "Stable tag: <%= pkg.version %>"
					},
					{
						from: /Version:.*$/m,
						to: "Version: <%= pkg.version %>"
					},
					{
						from: /public \$version = \'.*.'/m,
						to: "public $version = '<%= pkg.version %>'"
					},
					{
						from: /public \$version = \'.*.'/m,
						to: "public $version = '<%= pkg.version %>'"
					},
					{
						from: /public static \$version = \'.*.'/m,
						to: "public static $version = '<%= pkg.version %>'"
					},
					{
						from: /const VERSION = \'.*.'/m,
						to: "const VERSION = '<%= pkg.version %>'"
					},
					{
						from: /define\( 'WC_MNM_GROUPED_VERSION', .*$/m,
						to: "define( 'WC_MNM_GROUPED_VERSION', '<%= pkg.version %>' );"
					}
				]
			}
		}

    } );

	// Register tasks.
	grunt.registerTask(
        'default',
        [
		'js',
        ]
    );

	grunt.registerTask(
        'js',
        [
		'jshint',
		'uglify:admin',
		'uglify:frontend'
        ]
    );

	grunt.registerTask(
        'css',
        [
		'sass',
		'rtlcss',
		'postcss',
		'cssmin'
        ]
    );

	grunt.registerTask(
        'assets',
        [
		'js',
		'css'
        ]
    );

	grunt.registerTask(
        'zip',
        [
		'clean',
		'copy',
		'compress'
        ]
    );

	grunt.registerTask( 'dev', [ 'jshint', 'uglify', 'sass' ] );
	grunt.registerTask( 'build', [ 'replace', 'assets', 'addtextdomain', 'makepot' ] );
	grunt.registerTask( 'release', [ 'build', 'zip', 'clean' ] );

};
