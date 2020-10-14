const Encore = require( '@symfony/webpack-encore' );

if ( ! Encore.isRuntimeEnvironmentConfigured() ) {
	Encore.configureRuntimeEnvironment( process.env.NODE_ENV || 'dev' );
}

// Encore is a small wrapper around Webpack, which makes Webpack configs easy.
// If further loaders and/or plugins are needed, refer to this page:
// https://symfony.com/doc/current/frontend/encore/custom-loaders-plugins.html

Encore.setOutputPath( 'assets/dist/' )
	.setPublicPath( './' )
	.setManifestKeyPrefix( 'assets/dist/' )

	.addEntry( 'cloudinary', './assets/js/main.js' )
	.addEntry( 'block-editor', './assets/js/blocks.js' )
	.addEntry( 'block-gallery', './assets/js/gallery-block/index.js' )
	.addStyleEntry( 'video', './assets/css/video.scss' )
	// Add more entries here if needed...

	.copyFiles( {
		from: './assets/css',
		to: '[path][name].[ext]',
		pattern: /\.svg$/,
	} )

	.enableSingleRuntimeChunk()
	.cleanupOutputBeforeBuild()
	.enableBuildNotifications()
	.enableSourceMaps( ! Encore.isProduction() )

	// Enable plugins/loaders
	.enableSassLoader()
	.addExternals( [ 'lodash', '_' ] )
	.addExternals( { '@wordpress/blocks': 'wp.blocks' } )
	.addExternals( { '@wordpress/block-editor': 'wp.editor' } )
	.addExternals( { '@wordpress/element': 'wp.element' } )
	.addExternals( { react: 'React' } )
	.addExternals( { 'react-dom': 'ReactDOM' } )
	.configureBabelPresetEnv( ( config ) => {
		config.useBuiltIns = 'usage';
		config.corejs = 3;
	} )
	.enableReactPreset();

module.exports = Encore.getWebpackConfig();