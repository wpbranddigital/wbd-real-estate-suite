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
				<PanelBody title={ __( 'Agent', 'wbd-real-estate-suite' ) }>
					<PostPicker postType="wbd_agent" label={ __( 'Agent', 'wbd-real-estate-suite' ) } help={ __( 'Empty = current agent page or the agent of the current property.', 'wbd-real-estate-suite' ) } value={ a.agentId } onChange={ ( agentId ) => setAttributes( { agentId } ) } />
					<SelectControl label={ __( 'Layout', 'wbd-real-estate-suite' ) } value={ a.layout } options={ [ { label: __( 'Card', 'wbd-real-estate-suite' ), value: 'card' }, { label: __( 'Horizontal', 'wbd-real-estate-suite' ), value: 'horizontal' }, { label: __( 'Compact', 'wbd-real-estate-suite' ), value: 'compact' } ] } onChange={ ( layout ) => setAttributes( { layout } ) } __next40pxDefaultSize __nextHasNoMarginBottom />
					<Toggles attributes={ a } setAttributes={ setAttributes } items={ [
						[ 'showBio', __( 'Short bio', 'wbd-real-estate-suite' ) ],
						[ 'showContact', __( 'Phone & WhatsApp', 'wbd-real-estate-suite' ) ],
						[ 'showSocial', __( 'Social links', 'wbd-real-estate-suite' ) ],
						[ 'showCount', __( 'Listing count', 'wbd-real-estate-suite' ) ],
					] } />
				</PanelBody>
			</InspectorControls>
			<Preview name={ metadata.name } attributes={ a } context={ context } />
		</>
	);
}
registerBlockType( metadata.name, { edit: Edit, save: () => null } );
