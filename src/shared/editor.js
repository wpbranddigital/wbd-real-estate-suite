/**
 * Shared editor components for WBD Real Estate blocks.
 */
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { useBlockProps } from '@wordpress/block-editor';
import {
	ComboboxControl,
	FormTokenField,
	Spinner,
	ToggleControl,
} from '@wordpress/components';
import { useState } from '@wordpress/element';

function Loading() {
	return (
		<div className="wbdres-editor-loading">
			<Spinner />
		</div>
	);
}

function Empty() {
	return (
		<div className="wbdres-placeholder">
			{ __( 'Nothing to show yet.', 'wbd-real-estate-suite' ) }
		</div>
	);
}

/**
 * Live server-rendered preview so the editor matches the frontend exactly.
 *
 * @param {Object} props
 * @param {string} props.name       Block name.
 * @param {Object} props.attributes Attributes.
 * @param {Object} props.context    Block context.
 */
export function Preview( { name, attributes, context } ) {
	const blockProps = useBlockProps( { className: 'wbdres-ssr' } );
	const postId = context?.postId;
	return (
		<div { ...blockProps }>
			<ServerSideRender
				block={ name }
				attributes={ attributes }
				urlQueryArgs={ postId ? { post_id: postId } : {} }
				skipBlockSupportAttributes
				LoadingResponsePlaceholder={ Loading }
				EmptyResponsePlaceholder={ Empty }
			/>
		</div>
	);
}

/**
 * A list of toggles bound to boolean attributes.
 *
 * @param {Object}  props
 * @param {Object}  props.attributes    Attributes.
 * @param {Function} props.setAttributes Setter.
 * @param {Array}   props.items         [ key, label ] pairs.
 */
export function Toggles( { attributes, setAttributes, items } ) {
	return items.map( ( [ key, label ] ) => (
		<ToggleControl
			key={ key }
			label={ label }
			checked={ !! attributes[ key ] }
			onChange={ ( value ) => setAttributes( { [ key ]: value } ) }
			__nextHasNoMarginBottom
		/>
	) );
}

/**
 * Multi-select terms by ID.
 *
 * @param {Object}   props
 * @param {string}   props.taxonomy Taxonomy.
 * @param {string}   props.label    Label.
 * @param {number[]} props.value    Selected IDs.
 * @param {Function} props.onChange Change handler.
 */
export function TermTokens( { taxonomy, label, value = [], onChange } ) {
	const terms = useSelect(
		( select ) =>
			select( coreStore ).getEntityRecords( 'taxonomy', taxonomy, {
				per_page: 100,
				hide_empty: false,
				_fields: 'id,name',
				context: 'view',
			} ),
		[ taxonomy ]
	);
	if ( ! terms ) {
		return <Spinner />;
	}
	const names = value
		.map( ( id ) => terms.find( ( t ) => t.id === id )?.name )
		.filter( Boolean );
	return (
		<FormTokenField
			label={ label }
			value={ names }
			suggestions={ terms.map( ( t ) => t.name ) }
			onChange={ ( tokens ) =>
				onChange(
					tokens
						.map(
							( token ) =>
								terms.find(
									( t ) =>
										t.name ===
										( typeof token === 'string'
											? token
											: token.value )
								)?.id
						)
						.filter( Boolean )
				)
			}
			__experimentalExpandOnFocus
			__next40pxDefaultSize
			__nextHasNoMarginBottom
		/>
	);
}

/**
 * Searchable post picker.
 *
 * @param {Object}   props
 * @param {string}   props.postType Post type.
 * @param {string}   props.label    Label.
 * @param {number}   props.value    Selected ID.
 * @param {Function} props.onChange Change handler.
 * @param {string}   props.help     Help text.
 */
export function PostPicker( { postType, label, value, onChange, help } ) {
	const [ search, setSearch ] = useState( '' );
	const posts = useSelect(
		( select ) =>
			select( coreStore ).getEntityRecords( 'postType', postType, {
				per_page: 50,
				search: search || undefined,
				_fields: 'id,title',
				status: 'publish',
			} ),
		[ postType, search ]
	);
	const selected = useSelect(
		( select ) =>
			value
				? select( coreStore ).getEntityRecord(
						'postType',
						postType,
						value
				  )
				: null,
		[ postType, value ]
	);
	const options = ( posts || [] ).map( ( p ) => ( {
		value: p.id,
		label: p.title?.rendered || `#${ p.id }`,
	} ) );
	if ( selected && ! options.find( ( o ) => o.value === selected.id ) ) {
		options.unshift( {
			value: selected.id,
			label: selected.title?.rendered || `#${ selected.id }`,
		} );
	}
	return (
		<ComboboxControl
			label={ label }
			help={ help }
			value={ value || null }
			options={ options }
			onChange={ ( v ) => onChange( v ? parseInt( v, 10 ) : 0 ) }
			onFilterValueChange={ setSearch }
			__next40pxDefaultSize
			__nextHasNoMarginBottom
		/>
	);
}

export const ratioOptions = [
	{ label: '4:3', value: '4/3' },
	{ label: '3:2', value: '3/2' },
	{ label: '16:9', value: '16/9' },
	{ label: '1:1', value: '1/1' },
	{ label: '3:4', value: '3/4' },
];
