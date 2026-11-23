/**
 * Extends @wordpress/scripts: blocks are auto-discovered from src/blocks/**\/block.json;
 * extra entries add the editor sidebar, admin UI, frontend styles and the shared
 * Interactivity API module.
 */
const path = require( 'path' );
const defaults = require( '@wordpress/scripts/config/webpack.config' );

const [ scriptConfig, moduleConfig ] = Array.isArray( defaults ) ? defaults : [ defaults ];

const resolveEntry = ( entry ) => ( typeof entry === 'function' ? entry() : entry );

module.exports = [
	{
		...scriptConfig,
		entry: () => ( {
			...resolveEntry( scriptConfig.entry ),
			'editor/sidebar': path.resolve( __dirname, 'src/editor/sidebar.js' ),
			'admin/admin': path.resolve( __dirname, 'src/admin/admin.js' ),
			'frontend/style': path.resolve( __dirname, 'src/frontend/style.scss' ),
		} ),
	},
	...( moduleConfig
		? [
				{
					...moduleConfig,
					entry: () => ( {
						...resolveEntry( moduleConfig.entry ),
						'frontend/view': path.resolve( __dirname, 'src/frontend/view.js' ),
					} ),
				},
		  ]
		: [] ),
];
