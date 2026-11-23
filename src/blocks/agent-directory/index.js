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
				<PanelBody title={ __( 'Directory', 'wbd-real-estate-suite' ) }>
					<RangeControl label={ __( 'Number of agents', 'wbd-real-estate-suite' ) } value={ a.perPage } min={ 1 } max={ 48 } onChange={ ( perPage ) => setAttributes( { perPage } ) } __next40pxDefaultSize __nextHasNoMarginBottom />
					<RangeControl label={ __( 'Columns', 'wbd-real-estate-suite' ) } value={ a.columns } min={ 1 } max={ 4 } onChange={ ( columns ) => setAttributes( { columns } ) } __next40pxDefaultSize __nextHasNoMarginBottom />
					<SelectControl label={ __( 'Order', 'wbd-real-estate-suite' ) } value={ a.orderBy } options={ [ { label: __( 'Name', 'wbd-real-estate-suite' ), value: 'title' }, { label: __( 'Newest', 'wbd-real-estate-suite' ), value: 'date' }, { label: __( 'Custom order', 'wbd-real-estate-suite' ), value: 'menu_order' } ] } onChange={ ( orderBy ) => setAttributes( { orderBy } ) } __next40pxDefaultSize __nextHasNoMarginBottom />
					<Toggles attributes={ a } setAttributes={ setAttributes } items={ [
						[ 'showBio', __( 'Short bio', 'wbd-real-estate-suite' ) ],
						[ 'showContact', __( 'Phone & WhatsApp', 'wbd-real-estate-suite' ) ],
						[ 'showCount', __( 'Listing count', 'wbd-real-estate-suite' ) ],
					] } />
				</PanelBody>
			</InspectorControls>
			<Preview name={ metadata.name } attributes={ a } context={ context } />
		</>
	);
}
registerBlockType( metadata.name, { edit: Edit, save: () => null } );
