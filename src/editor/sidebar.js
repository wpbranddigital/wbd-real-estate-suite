/**
 * Property & agent detail panels in the block editor sidebar.
 */
import { __, sprintf } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { store as editorStore } from '@wordpress/editor';
import { useSelect } from '@wordpress/data';
import { useEntityProp, store as coreStore } from '@wordpress/core-data';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import {
	Button,
	ComboboxControl,
	TextControl,
	ToggleControl,
	__experimentalNumberControl as NumberControl,
	__experimentalGrid as Grid,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import './sidebar.scss';

const cfg = window.wbdresEditor || {};

function useMeta( postType ) {
	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );
	const get = ( key, fallback = '' ) =>
		meta && meta[ key ] !== undefined && meta[ key ] !== null ? meta[ key ] : fallback;
	const set = ( key ) => ( value ) => setMeta( { ...meta, [ key ]: value } );
	return [ get, set ];
}

const Num = ( { label, value, onChange, step = 1, min = 0, max, help } ) => (
	<NumberControl
		label={ label }
		value={ value === 0 ? '' : value }
		min={ min }
		max={ max }
		step={ step }
		help={ help }
		onChange={ ( v ) => onChange( v === '' || v === undefined ? 0 : Number( v ) ) }
		__next40pxDefaultSize
	/>
);

const Text = ( props ) => (
	<TextControl { ...props } __next40pxDefaultSize __nextHasNoMarginBottom />
);

function GalleryControl( { ids, onChange } ) {
	const images = useSelect(
		( select ) =>
			ids.length
				? select( coreStore ).getEntityRecords( 'postType', 'attachment', {
						include: ids,
						per_page: ids.length,
						orderby: 'include',
				  } )
				: [],
		[ ids.join( ',' ) ]
	);
	const move = ( index, dir ) => {
		const next = [ ...ids ];
		const target = index + dir;
		if ( target < 0 || target >= next.length ) {
			return;
		}
		[ next[ index ], next[ target ] ] = [ next[ target ], next[ index ] ];
		onChange( next );
	};
	return (
		<div className="wbdres-gallery-control">
			<p className="wbdres-gallery-control__help">
				{ __( 'The main photo (featured image) is shown first, followed by these photos.', 'wbd-real-estate-suite' ) }
			</p>
			{ !! images?.length && (
				<ul className="wbdres-gallery-control__grid">
					{ images.map( ( img, i ) => (
						<li key={ img.id }>
							<img src={ img.media_details?.sizes?.thumbnail?.source_url || img.source_url } alt={ img.alt_text || '' } />
							<div className="wbdres-gallery-control__tools">
								<Button size="small" icon="arrow-left-alt2" label={ __( 'Move earlier', 'wbd-real-estate-suite' ) } onClick={ () => move( i, -1 ) } />
								<Button size="small" icon="no-alt" isDestructive label={ __( 'Remove', 'wbd-real-estate-suite' ) } onClick={ () => onChange( ids.filter( ( id ) => id !== img.id ) ) } />
								<Button size="small" icon="arrow-right-alt2" label={ __( 'Move later', 'wbd-real-estate-suite' ) } onClick={ () => move( i, 1 ) } />
							</div>
						</li>
					) ) }
				</ul>
			) }
			<MediaUploadCheck>
				<MediaUpload
					multiple
					gallery
					allowedTypes={ [ 'image' ] }
					value={ ids }
					onSelect={ ( media ) => onChange( media.map( ( m ) => m.id ) ) }
					render={ ( { open } ) => (
						<Button variant="secondary" onClick={ open } __next40pxDefaultSize>
							{ ids.length
								? sprintf(
										/* translators: %d: number of photos. */
										__( 'Edit gallery (%d photos)', 'wbd-real-estate-suite' ),
										ids.length
								  )
								: __( 'Add gallery photos', 'wbd-real-estate-suite' ) }
						</Button>
					) }
				/>
			</MediaUploadCheck>
		</div>
	);
}

function AgentSelect( { value, onChange } ) {
	const [ search, setSearch ] = useState( '' );
	const agents = useSelect(
		( select ) =>
			select( coreStore ).getEntityRecords( 'postType', cfg.agentType, {
				per_page: 50,
				search: search || undefined,
				_fields: 'id,title',
			} ),
		[ search ]
	);
	return (
		<ComboboxControl
			label={ __( 'Listing agent', 'wbd-real-estate-suite' ) }
			help={ __( 'Inquiries for this property are sent to this agent.', 'wbd-real-estate-suite' ) }
			value={ value || null }
			options={ ( agents || [] ).map( ( a ) => ( { value: a.id, label: a.title?.rendered || `#${ a.id }` } ) ) }
			onChange={ ( v ) => onChange( v ? parseInt( v, 10 ) : 0 ) }
			onFilterValueChange={ setSearch }
			__next40pxDefaultSize
			__nextHasNoMarginBottom
		/>
	);
}

function PropertyPanels() {
	const [ get, set ] = useMeta( cfg.propertyType );
	const gallery = Array.isArray( get( '_wbdres_gallery', [] ) ) ? get( '_wbdres_gallery', [] ) : [];
	return (
		<>
			<PluginDocumentSettingPanel name="wbdres-price" title={ __( 'Price & listing', 'wbd-real-estate-suite' ) } className="wbdres-panel-editor">
				<Num label={ sprintf( /* translators: %s: currency code. */ __( 'Price (%s)', 'wbd-real-estate-suite' ), cfg.currencyCode ) } value={ get( '_wbdres_price', 0 ) } step="any" onChange={ set( '_wbdres_price' ) } />
				<Text label={ __( 'Price suffix', 'wbd-real-estate-suite' ) } help={ __( 'e.g. /month, /week, negotiable', 'wbd-real-estate-suite' ) } value={ get( '_wbdres_price_suffix' ) } onChange={ set( '_wbdres_price_suffix' ) } />
				<ToggleControl label={ __( 'Show "Price on request"', 'wbd-real-estate-suite' ) } checked={ !! get( '_wbdres_price_on_request', false ) } onChange={ set( '_wbdres_price_on_request' ) } __nextHasNoMarginBottom />
				<ToggleControl label={ __( 'Featured property', 'wbd-real-estate-suite' ) } help={ __( 'Highlighted with a badge and in Featured Properties blocks.', 'wbd-real-estate-suite' ) } checked={ !! get( '_wbdres_featured', false ) } onChange={ set( '_wbdres_featured' ) } __nextHasNoMarginBottom />
				<Text label={ __( 'Reference / MLS ID', 'wbd-real-estate-suite' ) } value={ get( '_wbdres_reference' ) } onChange={ set( '_wbdres_reference' ) } />
				<AgentSelect value={ get( '_wbdres_agent_id', 0 ) } onChange={ set( '_wbdres_agent_id' ) } />
			</PluginDocumentSettingPanel>

			<PluginDocumentSettingPanel name="wbdres-specs" title={ __( 'Property details', 'wbd-real-estate-suite' ) } className="wbdres-panel-editor">
				<Grid columns={ 2 } gap={ 3 }>
					<Num label={ __( 'Bedrooms', 'wbd-real-estate-suite' ) } value={ get( '_wbdres_bedrooms', 0 ) } max={ 99 } onChange={ set( '_wbdres_bedrooms' ) } />
					<Num label={ __( 'Bathrooms', 'wbd-real-estate-suite' ) } value={ get( '_wbdres_bathrooms', 0 ) } step={ 0.5 } max={ 99 } onChange={ set( '_wbdres_bathrooms' ) } />
					<Num label={ sprintf( /* translators: %s: area unit. */ __( 'Area (%s)', 'wbd-real-estate-suite' ), cfg.areaUnit ) } value={ get( '_wbdres_area', 0 ) } step="any" onChange={ set( '_wbdres_area' ) } />
					<Num label={ sprintf( /* translators: %s: area unit. */ __( 'Land (%s)', 'wbd-real-estate-suite' ), cfg.areaUnit ) } value={ get( '_wbdres_land_area', 0 ) } step="any" onChange={ set( '_wbdres_land_area' ) } />
					<Num label={ __( 'Garages', 'wbd-real-estate-suite' ) } value={ get( '_wbdres_garages', 0 ) } max={ 50 } onChange={ set( '_wbdres_garages' ) } />
					<Num label={ __( 'Year built', 'wbd-real-estate-suite' ) } value={ get( '_wbdres_year_built', 0 ) } min={ 0 } max={ 2200 } onChange={ set( '_wbdres_year_built' ) } />
				</Grid>
			</PluginDocumentSettingPanel>

			<PluginDocumentSettingPanel name="wbdres-location" title={ __( 'Address & map', 'wbd-real-estate-suite' ) } className="wbdres-panel-editor">
				<Text label={ __( 'Street address', 'wbd-real-estate-suite' ) } value={ get( '_wbdres_address' ) } onChange={ set( '_wbdres_address' ) } />
				<Grid columns={ 2 } gap={ 3 }>
					<Text label={ __( 'Postal code', 'wbd-real-estate-suite' ) } value={ get( '_wbdres_postal_code' ) } onChange={ set( '_wbdres_postal_code' ) } />
					<Text label={ __( 'Country', 'wbd-real-estate-suite' ) } value={ get( '_wbdres_country' ) } onChange={ set( '_wbdres_country' ) } />
					<Num label={ __( 'Latitude', 'wbd-real-estate-suite' ) } value={ get( '_wbdres_lat', 0 ) } min={ -90 } max={ 90 } step="any" onChange={ set( '_wbdres_lat' ) } />
					<Num label={ __( 'Longitude', 'wbd-real-estate-suite' ) } value={ get( '_wbdres_lng', 0 ) } min={ -180 } max={ 180 } step="any" onChange={ set( '_wbdres_lng' ) } />
				</Grid>
				<p className="wbdres-help">{ __( 'Tip: right-click a spot on openstreetmap.org → "Show address" to copy coordinates. City/area goes in Locations.', 'wbd-real-estate-suite' ) }</p>
				<ToggleControl label={ __( 'Hide map and exact coordinates', 'wbd-real-estate-suite' ) } checked={ !! get( '_wbdres_hide_map', false ) } onChange={ set( '_wbdres_hide_map' ) } __nextHasNoMarginBottom />
			</PluginDocumentSettingPanel>

			<PluginDocumentSettingPanel name="wbdres-media" title={ __( 'Gallery, video & tour', 'wbd-real-estate-suite' ) } className="wbdres-panel-editor">
				<GalleryControl ids={ gallery } onChange={ set( '_wbdres_gallery' ) } />
				<Text type="url" label={ __( 'Video URL', 'wbd-real-estate-suite' ) } help={ __( 'YouTube, Vimeo or any oEmbed provider.', 'wbd-real-estate-suite' ) } value={ get( '_wbdres_video_url' ) } onChange={ set( '_wbdres_video_url' ) } />
				<Text type="url" label={ __( 'Virtual tour URL', 'wbd-real-estate-suite' ) } value={ get( '_wbdres_tour_url' ) } onChange={ set( '_wbdres_tour_url' ) } />
			</PluginDocumentSettingPanel>
		</>
	);
}

function UserSelect( { value, onChange } ) {
	const users = useSelect(
		( select ) =>
			cfg.canUsers
				? select( coreStore ).getUsers( { per_page: 100, _fields: 'id,name' } )
				: [],
		[]
	);
	if ( ! cfg.canUsers ) {
		return null;
	}
	return (
		<ComboboxControl
			label={ __( 'Linked WordPress user', 'wbd-real-estate-suite' ) }
			help={ __( 'Lets this user see their own leads and auto-assigns their frontend submissions.', 'wbd-real-estate-suite' ) }
			value={ value || null }
			options={ ( users || [] ).map( ( u ) => ( { value: u.id, label: u.name } ) ) }
			onChange={ ( v ) => onChange( v ? parseInt( v, 10 ) : 0 ) }
			__next40pxDefaultSize
			__nextHasNoMarginBottom
		/>
	);
}

function AgentPanels() {
	const [ get, set ] = useMeta( cfg.agentType );
	return (
		<>
			<PluginDocumentSettingPanel name="wbdres-agent" title={ __( 'Agent details', 'wbd-real-estate-suite' ) } className="wbdres-panel-editor">
				<Text label={ __( 'Job title', 'wbd-real-estate-suite' ) } value={ get( '_wbdres_agent_title' ) } onChange={ set( '_wbdres_agent_title' ) } />
				<Text label={ __( 'Agency', 'wbd-real-estate-suite' ) } value={ get( '_wbdres_agent_agency' ) } onChange={ set( '_wbdres_agent_agency' ) } />
				<Text type="email" label={ __( 'Email (private — receives leads)', 'wbd-real-estate-suite' ) } value={ get( '_wbdres_agent_email' ) } onChange={ set( '_wbdres_agent_email' ) } />
				<Text type="tel" label={ __( 'Phone', 'wbd-real-estate-suite' ) } value={ get( '_wbdres_agent_phone' ) } onChange={ set( '_wbdres_agent_phone' ) } />
				<Text type="tel" label={ __( 'WhatsApp number', 'wbd-real-estate-suite' ) } help={ __( 'International format, e.g. +971501234567', 'wbd-real-estate-suite' ) } value={ get( '_wbdres_agent_whatsapp' ) } onChange={ set( '_wbdres_agent_whatsapp' ) } />
				<Text label={ __( 'License number', 'wbd-real-estate-suite' ) } value={ get( '_wbdres_agent_license' ) } onChange={ set( '_wbdres_agent_license' ) } />
				<Text label={ __( 'Languages', 'wbd-real-estate-suite' ) } value={ get( '_wbdres_agent_languages' ) } onChange={ set( '_wbdres_agent_languages' ) } />
				<UserSelect value={ get( '_wbdres_agent_user_id', 0 ) } onChange={ set( '_wbdres_agent_user_id' ) } />
			</PluginDocumentSettingPanel>
			<PluginDocumentSettingPanel name="wbdres-agent-links" title={ __( 'Website & social', 'wbd-real-estate-suite' ) } className="wbdres-panel-editor">
				{ [
					[ '_wbdres_agent_website', __( 'Website', 'wbd-real-estate-suite' ) ],
					[ '_wbdres_agent_linkedin', 'LinkedIn' ],
					[ '_wbdres_agent_facebook', 'Facebook' ],
					[ '_wbdres_agent_instagram', 'Instagram' ],
					[ '_wbdres_agent_x', 'X' ],
				].map( ( [ key, label ] ) => (
					<Text key={ key } type="url" label={ label } value={ get( key ) } onChange={ set( key ) } />
				) ) }
			</PluginDocumentSettingPanel>
		</>
	);
}

function Panels() {
	const postType = useSelect( ( select ) => select( editorStore ).getCurrentPostType(), [] );
	if ( postType === cfg.propertyType ) {
		return <PropertyPanels />;
	}
	if ( postType === cfg.agentType ) {
		return <AgentPanels />;
	}
	return null;
}

registerPlugin( 'wbdres-details', { render: Panels } );
