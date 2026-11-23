import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, RangeControl, TextControl, ToggleControl } from '@wordpress/components';
import metadata from './block.json';
import { Preview, Toggles, TermTokens, PostPicker, ratioOptions } from '../../shared/editor';


function Edit( { attributes: a, setAttributes, context } ) {
	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Saved properties', 'wbd-real-estate-suite' ) }>
					<SelectControl label={ __( 'Show', 'wbd-real-estate-suite' ) } value={ a.mode } options={ [ { label: __( 'Favorites', 'wbd-real-estate-suite' ), value: 'favorites' }, { label: __( 'Recently viewed', 'wbd-real-estate-suite' ), value: 'recent' }, { label: __( 'Comparison table', 'wbd-real-estate-suite' ), value: 'compare' } ] } onChange={ ( mode ) => setAttributes( { mode } ) } __next40pxDefaultSize __nextHasNoMarginBottom />
					<TextControl label={ __( 'Heading', 'wbd-real-estate-suite' ) } value={ a.title } onChange={ ( title ) => setAttributes( { title } ) } __next40pxDefaultSize __nextHasNoMarginBottom />
					{ a.mode !== 'compare' && <RangeControl label={ __( 'Columns', 'wbd-real-estate-suite' ) } value={ a.columns } min={ 1 } max={ 4 } onChange={ ( columns ) => setAttributes( { columns } ) } __next40pxDefaultSize __nextHasNoMarginBottom /> }
					<p className="components-base-control__help">{ __( 'Content loads in the visitor’s browser on the live site. Nothing is stored on your server.', 'wbd-real-estate-suite' ) }</p>
				</PanelBody>
			</InspectorControls>
			<Preview name={ metadata.name } attributes={ a } context={ context } />
		</>
	);
}
registerBlockType( metadata.name, { edit: Edit, save: () => null } );
