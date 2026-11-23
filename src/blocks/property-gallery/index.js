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
				<PanelBody title={ __( 'Gallery', 'wbd-real-estate-suite' ) }>
					<SelectControl label={ __( 'Layout', 'wbd-real-estate-suite' ) } value={ a.layout } options={ [ { label: __( 'Hero (1 large + 4)', 'wbd-real-estate-suite' ), value: 'hero' }, { label: __( 'Grid', 'wbd-real-estate-suite' ), value: 'grid' }, { label: __( 'Stacked', 'wbd-real-estate-suite' ), value: 'stack' } ] } onChange={ ( layout ) => setAttributes( { layout } ) } __next40pxDefaultSize __nextHasNoMarginBottom />
					<ToggleControl label={ __( 'Open photos in viewer', 'wbd-real-estate-suite' ) } checked={ a.lightbox } onChange={ ( lightbox ) => setAttributes( { lightbox } ) } __nextHasNoMarginBottom />
					<RangeControl label={ __( 'Maximum photos', 'wbd-real-estate-suite' ) } value={ a.max } min={ 1 } max={ 60 } onChange={ ( max ) => setAttributes( { max } ) } __next40pxDefaultSize __nextHasNoMarginBottom />
					<PostPicker postType="wbd_property" label={ __( 'Property', 'wbd-real-estate-suite' ) } value={ a.propertyId } onChange={ ( propertyId ) => setAttributes( { propertyId } ) } />
				</PanelBody>
			</InspectorControls>
			<Preview name={ metadata.name } attributes={ a } context={ context } />
		</>
	);
}
registerBlockType( metadata.name, { edit: Edit, save: () => null } );
