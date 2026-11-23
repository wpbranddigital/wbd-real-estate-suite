import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, RangeControl, TextControl, ToggleControl } from '@wordpress/components';
import metadata from './block.json';
import { Preview, Toggles, TermTokens, PostPicker, ratioOptions } from '../../shared/editor';


const sortOptions = [
	{ label: __( 'Newest', 'wbd-real-estate-suite' ), value: 'newest' },
	{ label: __( 'Price: low to high', 'wbd-real-estate-suite' ), value: 'price_asc' },
	{ label: __( 'Price: high to low', 'wbd-real-estate-suite' ), value: 'price_desc' },
	{ label: __( 'Largest area', 'wbd-real-estate-suite' ), value: 'area_desc' },
	{ label: __( 'Featured first', 'wbd-real-estate-suite' ), value: 'featured' },
	{ label: __( 'Name (A–Z)', 'wbd-real-estate-suite' ), value: 'title' },
	{ label: __( 'Random', 'wbd-real-estate-suite' ), value: 'rand' },
];

function Edit( { attributes, setAttributes, context } ) {
	const a = attributes;
	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Layout', 'wbd-real-estate-suite' ) }>
					<SelectControl label={ __( 'Layout', 'wbd-real-estate-suite' ) } value={ a.layout } options={ [ { label: __( 'Grid', 'wbd-real-estate-suite' ), value: 'grid' }, { label: __( 'List', 'wbd-real-estate-suite' ), value: 'list' } ] } onChange={ ( layout ) => setAttributes( { layout } ) } __next40pxDefaultSize __nextHasNoMarginBottom />
					{ a.layout === 'grid' && (
						<>
							<SelectControl label={ __( 'Card style', 'wbd-real-estate-suite' ) } value={ a.cardStyle } options={ [ { label: __( 'Modern (price + heart in card)', 'wbd-real-estate-suite' ), value: 'modern' }, { label: __( 'Overlay (price on photo)', 'wbd-real-estate-suite' ), value: 'overlay' } ] } onChange={ ( cardStyle ) => setAttributes( { cardStyle } ) } __next40pxDefaultSize __nextHasNoMarginBottom />
							<RangeControl label={ __( 'Columns (desktop)', 'wbd-real-estate-suite' ) } value={ a.columns } min={ 1 } max={ 4 } onChange={ ( columns ) => setAttributes( { columns } ) } help={ __( 'Tablets show up to 2 columns, phones 1.', 'wbd-real-estate-suite' ) } __next40pxDefaultSize __nextHasNoMarginBottom />
						</>
					) }
					<SelectControl label={ __( 'Image ratio', 'wbd-real-estate-suite' ) } value={ a.imageRatio } options={ ratioOptions } onChange={ ( imageRatio ) => setAttributes( { imageRatio } ) } __next40pxDefaultSize __nextHasNoMarginBottom />
					<SelectControl label={ __( 'Title heading level', 'wbd-real-estate-suite' ) } value={ String( a.headingLevel ) } options={ [ 2, 3, 4 ].map( ( l ) => ( { label: `H${ l }`, value: String( l ) } ) ) } onChange={ ( v ) => setAttributes( { headingLevel: parseInt( v, 10 ) } ) } __next40pxDefaultSize __nextHasNoMarginBottom />
				</PanelBody>
				<PanelBody title={ __( 'Query', 'wbd-real-estate-suite' ) } initialOpen={ false }>
					<ToggleControl label={ __( 'Use URL search filters', 'wbd-real-estate-suite' ) } help={ __( 'Turn on for search results pages: reads filters from the Property Search block.', 'wbd-real-estate-suite' ) } checked={ a.inheritSearch } onChange={ ( inheritSearch ) => setAttributes( { inheritSearch } ) } __nextHasNoMarginBottom />
					<RangeControl label={ __( 'Properties per page', 'wbd-real-estate-suite' ) } value={ a.perPage } min={ 1 } max={ 48 } onChange={ ( perPage ) => setAttributes( { perPage } ) } __next40pxDefaultSize __nextHasNoMarginBottom />
					<SelectControl label={ __( 'Order by', 'wbd-real-estate-suite' ) } value={ a.orderBy } options={ sortOptions } onChange={ ( orderBy ) => setAttributes( { orderBy } ) } __next40pxDefaultSize __nextHasNoMarginBottom />
					<TermTokens taxonomy="wbd_property_type" label={ __( 'Property types', 'wbd-real-estate-suite' ) } value={ a.types } onChange={ ( types ) => setAttributes( { types } ) } />
					<TermTokens taxonomy="wbd_property_status" label={ __( 'Statuses', 'wbd-real-estate-suite' ) } value={ a.statuses } onChange={ ( statuses ) => setAttributes( { statuses } ) } />
					<TermTokens taxonomy="wbd_property_location" label={ __( 'Locations', 'wbd-real-estate-suite' ) } value={ a.locations } onChange={ ( locations ) => setAttributes( { locations } ) } />
					<ToggleControl label={ __( 'Featured only', 'wbd-real-estate-suite' ) } checked={ a.featuredOnly } onChange={ ( featuredOnly ) => setAttributes( { featuredOnly } ) } __nextHasNoMarginBottom />
					<SelectControl label={ __( 'Agent', 'wbd-real-estate-suite' ) } value={ a.agentId === -1 ? 'current' : ( a.agentId > 0 ? 'pick' : 'any' ) } options={ [ { label: __( 'Any agent', 'wbd-real-estate-suite' ), value: 'any' }, { label: __( 'Current agent page', 'wbd-real-estate-suite' ), value: 'current' }, { label: __( 'Choose an agent', 'wbd-real-estate-suite' ), value: 'pick' } ] } onChange={ ( v ) => setAttributes( { agentId: v === 'current' ? -1 : ( v === 'pick' ? Math.max( a.agentId, 0 ) || 0 : 0 ) } ) } __next40pxDefaultSize __nextHasNoMarginBottom />
					{ a.agentId >= 0 && <PostPicker postType="wbd_agent" label={ __( 'Agent profile', 'wbd-real-estate-suite' ) } value={ a.agentId } onChange={ ( agentId ) => setAttributes( { agentId } ) } /> }
					<ToggleControl label={ __( 'Similar to current property', 'wbd-real-estate-suite' ) } checked={ a.similarTo === -1 } onChange={ ( v ) => setAttributes( { similarTo: v ? -1 : 0 } ) } __nextHasNoMarginBottom />
					<ToggleControl label={ __( 'Exclude current property', 'wbd-real-estate-suite' ) } checked={ a.excludeCurrent } onChange={ ( excludeCurrent ) => setAttributes( { excludeCurrent } ) } __nextHasNoMarginBottom />
					<RangeControl label={ __( 'Offset', 'wbd-real-estate-suite' ) } value={ a.offset } min={ 0 } max={ 20 } onChange={ ( offset ) => setAttributes( { offset } ) } __next40pxDefaultSize __nextHasNoMarginBottom />
				</PanelBody>
				<PanelBody title={ __( 'Card content', 'wbd-real-estate-suite' ) } initialOpen={ false }>
					<Toggles attributes={ a } setAttributes={ setAttributes } items={ [
						[ 'showImage', __( 'Photo', 'wbd-real-estate-suite' ) ],
						[ 'showBadges', __( 'Status & featured badges', 'wbd-real-estate-suite' ) ],
						[ 'showPrice', __( 'Price', 'wbd-real-estate-suite' ) ],
						[ 'showAddress', __( 'Address', 'wbd-real-estate-suite' ) ],
						[ 'showSpecs', __( 'Beds, baths & area', 'wbd-real-estate-suite' ) ],
						[ 'showExcerpt', __( 'Excerpt', 'wbd-real-estate-suite' ) ],
						[ 'showType', __( 'Property type', 'wbd-real-estate-suite' ) ],
						[ 'showAgent', __( 'Agent', 'wbd-real-estate-suite' ) ],
						[ 'showActions', __( 'Favorite & compare buttons', 'wbd-real-estate-suite' ) ],
					] } />
				</PanelBody>
				<PanelBody title={ __( 'Toolbar & pagination', 'wbd-real-estate-suite' ) } initialOpen={ false }>
					<Toggles attributes={ a } setAttributes={ setAttributes } items={ [
						[ 'showToolbar', __( 'Results toolbar (count, sort, view)', 'wbd-real-estate-suite' ) ],
						[ 'showSort', __( 'Sort dropdown', 'wbd-real-estate-suite' ) ],
						[ 'showViewToggle', __( 'Grid / list toggle', 'wbd-real-estate-suite' ) ],
						[ 'showChips', __( 'Active filter chips', 'wbd-real-estate-suite' ) ],
						[ 'showPagination', __( 'Pagination', 'wbd-real-estate-suite' ) ],
					] } />
					<TextControl label={ __( 'No results message', 'wbd-real-estate-suite' ) } value={ a.emptyMessage } onChange={ ( emptyMessage ) => setAttributes( { emptyMessage } ) } __next40pxDefaultSize __nextHasNoMarginBottom />
				</PanelBody>
			</InspectorControls>
			<Preview name={ metadata.name } attributes={ a } context={ context } />
		</>
	);
}

registerBlockType( metadata.name, { edit: Edit, save: () => null } );
