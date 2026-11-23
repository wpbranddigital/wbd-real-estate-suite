import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, RangeControl, TextControl, ToggleControl } from '@wordpress/components';
import metadata from './block.json';
import { Preview, Toggles, TermTokens, PostPicker, ratioOptions } from '../../shared/editor';


const FIELDS = [
	[ 'title', __( 'Title', 'wbd-real-estate-suite' ) ],
	[ 'price', __( 'Price', 'wbd-real-estate-suite' ) ],
	[ 'address', __( 'Address', 'wbd-real-estate-suite' ) ],
	[ 'status', __( 'Status & featured badges', 'wbd-real-estate-suite' ) ],
	[ 'specs', __( 'All specifications', 'wbd-real-estate-suite' ) ],
	[ 'keyfacts', __( 'Beds, baths & area', 'wbd-real-estate-suite' ) ],
	[ 'features', __( 'Features & amenities', 'wbd-real-estate-suite' ) ],
	[ 'type', __( 'Property type', 'wbd-real-estate-suite' ) ],
	[ 'reference', __( 'Reference', 'wbd-real-estate-suite' ) ],
];
function Edit( { attributes: a, setAttributes, context } ) {
	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Field', 'wbd-real-estate-suite' ) }>
					<SelectControl label={ __( 'Show', 'wbd-real-estate-suite' ) } value={ a.field } options={ FIELDS.map( ( [ value, label ] ) => ( { value, label } ) ) } onChange={ ( field ) => setAttributes( { field } ) } __next40pxDefaultSize __nextHasNoMarginBottom />
					{ a.field === 'title' && <SelectControl label={ __( 'Heading level', 'wbd-real-estate-suite' ) } value={ String( a.headingLevel ) } options={ [ 1, 2, 3 ].map( ( l ) => ( { label: `H${ l }`, value: String( l ) } ) ) } onChange={ ( v ) => setAttributes( { headingLevel: parseInt( v, 10 ) } ) } __next40pxDefaultSize __nextHasNoMarginBottom /> }
					{ a.field === 'specs' && <ToggleControl label={ __( 'Show labels', 'wbd-real-estate-suite' ) } checked={ a.showLabels } onChange={ ( showLabels ) => setAttributes( { showLabels } ) } __nextHasNoMarginBottom /> }
					<PostPicker postType="wbd_property" label={ __( 'Property', 'wbd-real-estate-suite' ) } help={ __( 'Leave empty to use the current property.', 'wbd-real-estate-suite' ) } value={ a.propertyId } onChange={ ( propertyId ) => setAttributes( { propertyId } ) } />
				</PanelBody>
			</InspectorControls>
			<Preview name={ metadata.name } attributes={ a } context={ context } />
		</>
	);
}
registerBlockType( metadata.name, { edit: Edit, save: () => null } );
