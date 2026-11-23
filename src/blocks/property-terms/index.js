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
				<PanelBody title={ __( 'Terms', 'wbd-real-estate-suite' ) }>
					<SelectControl label={ __( 'Taxonomy', 'wbd-real-estate-suite' ) } value={ a.taxonomy } options={ [ { label: __( 'Property types', 'wbd-real-estate-suite' ), value: 'wbd_property_type' }, { label: __( 'Locations', 'wbd-real-estate-suite' ), value: 'wbd_property_location' }, { label: __( 'Statuses', 'wbd-real-estate-suite' ), value: 'wbd_property_status' }, { label: __( 'Amenities', 'wbd-real-estate-suite' ), value: 'wbd_property_feature' } ] } onChange={ ( taxonomy ) => setAttributes( { taxonomy } ) } __next40pxDefaultSize __nextHasNoMarginBottom />
					<SelectControl label={ __( 'Style', 'wbd-real-estate-suite' ) } value={ a.layout } options={ [ { label: __( 'Cards', 'wbd-real-estate-suite' ), value: 'cards' }, { label: __( 'Chips', 'wbd-real-estate-suite' ), value: 'chips' } ] } onChange={ ( layout ) => setAttributes( { layout } ) } __next40pxDefaultSize __nextHasNoMarginBottom />
					<RangeControl label={ __( 'Maximum items', 'wbd-real-estate-suite' ) } value={ a.number } min={ 1 } max={ 50 } onChange={ ( number ) => setAttributes( { number } ) } __next40pxDefaultSize __nextHasNoMarginBottom />
					<Toggles attributes={ a } setAttributes={ setAttributes } items={ [
						[ 'showCount', __( 'Show counts', 'wbd-real-estate-suite' ) ],
						[ 'hideEmpty', __( 'Hide empty', 'wbd-real-estate-suite' ) ],
						[ 'topLevel', __( 'Top level only', 'wbd-real-estate-suite' ) ],
					] } />
				</PanelBody>
			</InspectorControls>
			<Preview name={ metadata.name } attributes={ a } context={ context } />
		</>
	);
}
registerBlockType( metadata.name, { edit: Edit, save: () => null } );
