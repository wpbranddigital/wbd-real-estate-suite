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
				<PanelBody title={ __( 'Map', 'wbd-real-estate-suite' ) }>
					<RangeControl label={ __( 'Height (px)', 'wbd-real-estate-suite' ) } value={ a.height } min={ 200 } max={ 800 } step={ 10 } onChange={ ( height ) => setAttributes( { height } ) } __next40pxDefaultSize __nextHasNoMarginBottom />
					<RangeControl label={ __( 'Zoom', 'wbd-real-estate-suite' ) } value={ a.zoom } min={ 5 } max={ 18 } onChange={ ( zoom ) => setAttributes( { zoom } ) } __next40pxDefaultSize __nextHasNoMarginBottom />
					<PostPicker postType="wbd_property" label={ __( 'Property', 'wbd-real-estate-suite' ) } value={ a.propertyId } onChange={ ( propertyId ) => setAttributes( { propertyId } ) } />
				</PanelBody>
			</InspectorControls>
			<Preview name={ metadata.name } attributes={ a } context={ context } />
		</>
	);
}
registerBlockType( metadata.name, { edit: Edit, save: () => null } );
