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
				<PanelBody title={ __( 'Defaults', 'wbd-real-estate-suite' ) }>
					<TextControl label={ __( 'Heading', 'wbd-real-estate-suite' ) } value={ a.title } onChange={ ( title ) => setAttributes( { title } ) } __next40pxDefaultSize __nextHasNoMarginBottom />
					<TextControl type="number" label={ __( 'Price (0 = current property)', 'wbd-real-estate-suite' ) } value={ a.price } onChange={ ( v ) => setAttributes( { price: parseFloat( v ) || 0 } ) } __next40pxDefaultSize __nextHasNoMarginBottom />
					<TextControl type="number" label={ __( 'Interest rate % (0 = settings)', 'wbd-real-estate-suite' ) } value={ a.rate } onChange={ ( v ) => setAttributes( { rate: parseFloat( v ) || 0 } ) } __next40pxDefaultSize __nextHasNoMarginBottom />
					<TextControl type="number" label={ __( 'Term in years (0 = settings)', 'wbd-real-estate-suite' ) } value={ a.years } onChange={ ( v ) => setAttributes( { years: parseInt( v, 10 ) || 0 } ) } __next40pxDefaultSize __nextHasNoMarginBottom />
					<TextControl type="number" label={ __( 'Down payment % (-1 = settings)', 'wbd-real-estate-suite' ) } value={ a.down } onChange={ ( v ) => setAttributes( { down: v === '' ? -1 : parseFloat( v ) } ) } __next40pxDefaultSize __nextHasNoMarginBottom />
				</PanelBody>
			</InspectorControls>
			<Preview name={ metadata.name } attributes={ a } context={ context } />
		</>
	);
}
registerBlockType( metadata.name, { edit: Edit, save: () => null } );
