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
				<PanelBody title={ __( 'Submission', 'wbd-real-estate-suite' ) }>
					<TextControl label={ __( 'Heading', 'wbd-real-estate-suite' ) } value={ a.title } onChange={ ( title ) => setAttributes( { title } ) } __next40pxDefaultSize __nextHasNoMarginBottom />
					<ToggleControl label={ __( 'Show "My properties" dashboard', 'wbd-real-estate-suite' ) } checked={ a.showMySubmissions } onChange={ ( showMySubmissions ) => setAttributes( { showMySubmissions } ) } __nextHasNoMarginBottom />
				</PanelBody>
			</InspectorControls>
			<Preview name={ metadata.name } attributes={ a } context={ context } />
		</>
	);
}
registerBlockType( metadata.name, { edit: Edit, save: () => null } );
