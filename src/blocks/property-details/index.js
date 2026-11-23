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
				<PanelBody title={ __( 'Property', 'wbd-real-estate-suite' ) }>
					<PostPicker postType="wbd_property" label={ __( 'Property', 'wbd-real-estate-suite' ) } help={ __( 'Leave empty to use the current property.', 'wbd-real-estate-suite' ) } value={ a.propertyId } onChange={ ( propertyId ) => setAttributes( { propertyId } ) } />
				</PanelBody>
				<PanelBody title={ __( 'Sections', 'wbd-real-estate-suite' ) }>
					<Toggles attributes={ a } setAttributes={ setAttributes } items={ [
						[ 'showGallery', __( 'Photo gallery', 'wbd-real-estate-suite' ) ],
						[ 'showHeader', __( 'Price, status & key facts', 'wbd-real-estate-suite' ) ],
						[ 'showTitle', __( 'Title (H1)', 'wbd-real-estate-suite' ) ],
						[ 'showFeatures', __( "What's special (features)", 'wbd-real-estate-suite' ) ],
						[ 'showDescription', __( 'Description', 'wbd-real-estate-suite' ) ],
						[ 'showSpecs', __( 'Facts & features', 'wbd-real-estate-suite' ) ],
						[ 'showVideo', __( 'Video & virtual tour', 'wbd-real-estate-suite' ) ],
						[ 'showMap', __( 'Map', 'wbd-real-estate-suite' ) ],
						[ 'showMortgage', __( 'Payment calculator', 'wbd-real-estate-suite' ) ],
						[ 'showAgent', __( 'Agent card', 'wbd-real-estate-suite' ) ],
						[ 'showInquiry', __( 'Inquiry form', 'wbd-real-estate-suite' ) ],
						[ 'showViewing', __( 'Schedule a viewing', 'wbd-real-estate-suite' ) ],
						[ 'showSimilar', __( 'Recommended properties', 'wbd-real-estate-suite' ) ],
						[ 'showCta', __( 'Talk to an agent band', 'wbd-real-estate-suite' ) ],
					] } />
				</PanelBody>
			</InspectorControls>
			<Preview name={ metadata.name } attributes={ a } context={ context } />
		</>
	);
}
registerBlockType( metadata.name, { edit: Edit, save: () => null } );
