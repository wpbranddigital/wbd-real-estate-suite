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
				<PanelBody title={ __( 'Search form', 'wbd-real-estate-suite' ) }>
					<SelectControl label={ __( 'Layout', 'wbd-real-estate-suite' ) } value={ a.layout } options={ [ { label: __( 'Horizontal bar', 'wbd-real-estate-suite' ), value: 'bar' }, { label: __( 'Sidebar filters', 'wbd-real-estate-suite' ), value: 'sidebar' }, { label: __( 'Compact (hero)', 'wbd-real-estate-suite' ), value: 'compact' } ] } onChange={ ( layout ) => setAttributes( { layout } ) } __next40pxDefaultSize __nextHasNoMarginBottom />
					<TextControl label={ __( 'Heading (optional)', 'wbd-real-estate-suite' ) } value={ a.title } onChange={ ( title ) => setAttributes( { title } ) } __next40pxDefaultSize __nextHasNoMarginBottom />
					<TextControl label={ __( 'Button text', 'wbd-real-estate-suite' ) } value={ a.buttonText } placeholder={ __( 'Search', 'wbd-real-estate-suite' ) } onChange={ ( buttonText ) => setAttributes( { buttonText } ) } __next40pxDefaultSize __nextHasNoMarginBottom />
					<PostPicker postType="page" label={ __( 'Results page', 'wbd-real-estate-suite' ) } help={ __( 'Page containing a Property Listings block with "Use URL search filters". Empty = properties archive.', 'wbd-real-estate-suite' ) } value={ a.resultsPage } onChange={ ( resultsPage ) => setAttributes( { resultsPage } ) } />
				</PanelBody>
				<PanelBody title={ __( 'Fields', 'wbd-real-estate-suite' ) } initialOpen={ false }>
					<Toggles attributes={ a } setAttributes={ setAttributes } items={ [
						[ 'statusTabs', __( 'Status tabs (Buy / Rent…)', 'wbd-real-estate-suite' ) ],
						[ 'showKeyword', __( 'Keyword / city', 'wbd-real-estate-suite' ) ],
						[ 'showLocation', __( 'Location', 'wbd-real-estate-suite' ) ],
						[ 'showType', __( 'Property type', 'wbd-real-estate-suite' ) ],
						[ 'showStatus', __( 'Status dropdown', 'wbd-real-estate-suite' ) ],
						[ 'showPrice', __( 'Price range', 'wbd-real-estate-suite' ) ],
						[ 'showRooms', __( 'Beds & baths', 'wbd-real-estate-suite' ) ],
						[ 'showArea', __( 'Floor area (sidebar)', 'wbd-real-estate-suite' ) ],
						[ 'showFeatures', __( 'Amenities (sidebar)', 'wbd-real-estate-suite' ) ],
					] } />
				</PanelBody>
			</InspectorControls>
			<Preview name={ metadata.name } attributes={ a } context={ context } />
		</>
	);
}
registerBlockType( metadata.name, { edit: Edit, save: () => null } );
