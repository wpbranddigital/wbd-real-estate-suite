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
				<PanelBody title={ __( 'Form', 'wbd-real-estate-suite' ) }>
					<SelectControl label={ __( 'Form type', 'wbd-real-estate-suite' ) } value={ a.formType } options={ [ { label: __( 'Property inquiry', 'wbd-real-estate-suite' ), value: 'inquiry' }, { label: __( 'Schedule a viewing', 'wbd-real-estate-suite' ), value: 'viewing' }, { label: __( 'Contact agent / general', 'wbd-real-estate-suite' ), value: 'contact' } ] } onChange={ ( formType ) => setAttributes( { formType } ) } __next40pxDefaultSize __nextHasNoMarginBottom />
					<TextControl label={ __( 'Heading', 'wbd-real-estate-suite' ) } value={ a.title } onChange={ ( title ) => setAttributes( { title } ) } __next40pxDefaultSize __nextHasNoMarginBottom />
					<TextControl label={ __( 'Button text', 'wbd-real-estate-suite' ) } value={ a.buttonText } onChange={ ( buttonText ) => setAttributes( { buttonText } ) } __next40pxDefaultSize __nextHasNoMarginBottom />
					<Toggles attributes={ a } setAttributes={ setAttributes } items={ [
						[ 'showPhone', __( 'Phone field', 'wbd-real-estate-suite' ) ],
						[ 'phoneRequired', __( 'Phone required', 'wbd-real-estate-suite' ) ],
					] } />
				</PanelBody>
				<PanelBody title={ __( 'Routing', 'wbd-real-estate-suite' ) } initialOpen={ false }>
					<PostPicker postType="wbd_property" label={ __( 'Property', 'wbd-real-estate-suite' ) } help={ __( 'Empty = current property (if any).', 'wbd-real-estate-suite' ) } value={ a.propertyId } onChange={ ( propertyId ) => setAttributes( { propertyId } ) } />
					<PostPicker postType="wbd_agent" label={ __( 'Send to agent', 'wbd-real-estate-suite' ) } help={ __( "Empty = the property's agent or the current agent page.", 'wbd-real-estate-suite' ) } value={ a.agentId } onChange={ ( agentId ) => setAttributes( { agentId } ) } />
				</PanelBody>
			</InspectorControls>
			<Preview name={ metadata.name } attributes={ a } context={ context } />
		</>
	);
}
registerBlockType( metadata.name, { edit: Edit, save: () => null } );
