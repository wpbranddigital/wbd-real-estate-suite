/**
 * WBD Real Estate Suite — frontend interactivity (WordPress Interactivity API).
 *
 * Favorites, comparison and recently viewed lists live in localStorage only.
 */
import {
	store,
	getContext,
	getElement,
	getConfig,
	withSyncEvent,
} from '@wordpress/interactivity';

const KEYS = {
	favorites: 'wbdres_favorites',
	compare: 'wbdres_compare',
	recent: 'wbdres_recent',
};

const read = ( key ) => {
	try {
		const parsed = JSON.parse(
			window.localStorage.getItem( KEYS[ key ] ) || '[]'
		);
		return Array.isArray( parsed )
			? parsed.map( Number ).filter( Boolean )
			: [];
	} catch ( e ) {
		return [];
	}
};

const write = ( key, ids ) => {
	try {
		window.localStorage.setItem( KEYS[ key ], JSON.stringify( ids ) );
	} catch ( e ) {
		// Storage unavailable (private mode / quota): keep in memory only.
	}
};

const config = () => getConfig( 'wbdres' ) || {};
const i18n = ( key ) => ( config().i18n || {} )[ key ] || '';

const formatMoney = ( value, ctx ) => {
	const rounded = Math.round( value );
	let num;
	try {
		num = new Intl.NumberFormat( ctx.locale || undefined, {
			maximumFractionDigits: 0,
		} ).format( rounded );
	} catch ( e ) {
		num = String( rounded );
	}
	switch ( ctx.pos ) {
		case 'after':
			return num + ctx.symbol;
		case 'after_space':
			return `${ num } ${ ctx.symbol }`;
		case 'before_space':
			return `${ ctx.symbol } ${ num }`;
		default:
			return ctx.symbol + num;
	}
};

const { state, actions } = store( 'wbdres', {
	state: {
		favorites: [],
		compare: [],
		sending: false,
		get isFavorite() {
			const { id } = getContext();
			return state.favorites.includes( Number( id ) );
		},
		get isCompared() {
			const { id } = getContext();
			return state.compare.includes( Number( id ) );
		},
		get isSending() {
			return getContext().status === 'sending';
		},
		get isError() {
			return getContext().status === 'error';
		},
		get isSuccess() {
			return getContext().status === 'success';
		},
		get currentSrc() {
			const ctx = getContext();
			return ctx.images?.[ ctx.index ]?.src || '';
		},
		get currentAlt() {
			const ctx = getContext();
			return ctx.images?.[ ctx.index ]?.alt || '';
		},
		get counter() {
			const ctx = getContext();
			return ctx.images ? `${ ctx.index + 1 } / ${ ctx.images.length }` : '';
		},
		get mapSrc() {
			const ctx = getContext();
			return ctx.loaded ? ctx.src : null;
		},
		get loanAmount() {
			const ctx = getContext();
			const loan =
				Math.max( 0, Number( ctx.price ) || 0 ) *
				( 1 - Math.min( 100, Math.max( 0, Number( ctx.down ) || 0 ) ) / 100 );
			return formatMoney( loan, ctx );
		},
		get monthlyPayment() {
			const ctx = getContext();
			const price = Math.max( 0, Number( ctx.price ) || 0 );
			const loan =
				price *
				( 1 - Math.min( 100, Math.max( 0, Number( ctx.down ) || 0 ) ) / 100 );
			const n = Math.max( 1, Math.round( Number( ctx.years ) || 1 ) ) * 12;
			const r = Math.max( 0, Number( ctx.rate ) || 0 ) / 100 / 12;
			const monthly = r > 0 ? ( loan * r ) / ( 1 - Math.pow( 1 + r, -n ) ) : loan / n;
			return Number.isFinite( monthly ) ? formatMoney( monthly, ctx ) : '—';
		},
		get isSavedEmpty() {
			const ctx = getContext();
			return ctx.loaded && ctx.items.length === 0;
		},
	},
	actions: {
		toggleFavorite() {
			const { id } = getContext();
			const num = Number( id );
			state.favorites = state.favorites.includes( num )
				? state.favorites.filter( ( x ) => x !== num )
				: [ num, ...state.favorites ].slice( 0, 100 );
			write( 'favorites', state.favorites );
		},
		toggleCompare() {
			const { id } = getContext();
			const num = Number( id );
			const max = config().maxCompare || 4;
			if ( state.compare.includes( num ) ) {
				state.compare = state.compare.filter( ( x ) => x !== num );
			} else {
				if ( state.compare.length >= max ) {
					state.compare = state.compare.slice( 1 );
					// eslint-disable-next-line no-console
					console.info( i18n( 'compareLimit' ) );
				}
				state.compare = [ ...state.compare, num ];
			}
			write( 'compare', state.compare );
		},
		openLightbox: withSyncEvent( ( event ) => {
			event.preventDefault();
			const ctx = getContext();
			const { ref } = getElement();
			ctx.index = parseInt( ref.dataset.index || '0', 10 );
			ctx.open = true;
		} ),
		closeLightbox() {
			getContext().open = false;
		},
		nextImage() {
			const ctx = getContext();
			ctx.index = ( ctx.index + 1 ) % ctx.images.length;
		},
		prevImage() {
			const ctx = getContext();
			ctx.index = ( ctx.index - 1 + ctx.images.length ) % ctx.images.length;
		},
		lightboxKeys( event ) {
			if ( event.key === 'ArrowRight' ) {
				actions.nextImage();
			} else if ( event.key === 'ArrowLeft' ) {
				actions.prevImage();
			}
		},
		loadMap() {
			getContext().loaded = true;
		},
		updateMortgage( event ) {
			const ctx = getContext();
			const field = event.target.dataset.field;
			if ( field ) {
				ctx[ field ] = parseFloat( event.target.value ) || 0;
			}
		},
		submitParent: withSyncEvent( ( event ) => {
			event.target.form?.submit();
		} ),
		*share() {
			const ctx = getContext();
			const { ref } = getElement();
			const url = ref.dataset.url || window.location.href;
			const title = ref.dataset.title || document.title;
			try {
				if ( navigator.share ) {
					yield navigator.share( { title, url } );
				} else if ( navigator.clipboard ) {
					yield navigator.clipboard.writeText( url );
					ctx.shareMsg = '✓';
				}
			} catch ( e ) {
				// User cancelled sharing.
			}
		},
		submitInquiry: withSyncEvent( function* ( event ) {
			const ctx = getContext();
			const form = event.target;
			if ( ! form.checkValidity() ) {
				return; // Let the browser show native validation messages.
			}
			event.preventDefault();
			ctx.status = 'sending';
			ctx.message = i18n( 'sending' );
			const data = Object.fromEntries( new FormData( form ).entries() );
			try {
				const res = yield fetch( `${ config().restUrl }inquiries`, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					credentials: 'same-origin',
					body: JSON.stringify( data ),
				} );
				const json = yield res.json();
				if ( ! res.ok ) {
					throw new Error( json?.message || i18n( 'error' ) );
				}
				ctx.status = 'success';
				ctx.message = json.message || i18n( 'sent' );
				form.reset();
			} catch ( e ) {
				ctx.status = 'error';
				ctx.message = e.message || i18n( 'error' );
			}
		} ),
		removeSaved() {
			const ctx = getContext();
			const id = Number( ctx.item.id );
			const mode = ctx.mode;
			const key = mode === 'compare' ? 'compare' : mode;
			const next = read( key ).filter( ( x ) => x !== id );
			write( key, next );
			if ( key === 'favorites' ) {
				state.favorites = next;
			}
			if ( key === 'compare' ) {
				state.compare = next;
			}
			ctx.items = ctx.items.filter( ( item ) => item.id !== id );
		},
		clearSaved() {
			const ctx = getContext();
			write( ctx.mode, [] );
			if ( ctx.mode === 'favorites' ) {
				state.favorites = [];
			}
			ctx.items = [];
		},
	},
	callbacks: {
		init() {
			state.favorites = read( 'favorites' );
			state.compare = read( 'compare' );
		},
		syncDialog() {
			const ctx = getContext();
			const { ref } = getElement();
			if ( ! ref || typeof ref.showModal !== 'function' ) {
				return;
			}
			if ( ctx.open && ! ref.open ) {
				ref.showModal();
			} else if ( ! ctx.open && ref.open ) {
				ref.close();
			}
		},
		trackView() {
			const { id } = getContext();
			const num = Number( id );
			if ( ! num ) {
				return;
			}
			const recent = [ num, ...read( 'recent' ).filter( ( x ) => x !== num ) ].slice( 0, 12 );
			write( 'recent', recent );
			if ( config().trackViews && config().restUrl ) {
				fetch( `${ config().restUrl }properties/${ num }/view`, {
					method: 'POST',
					credentials: 'omit',
					keepalive: true,
				} ).catch( () => {} );
			}
		},
		*loadSaved() {
			const ctx = getContext();
			const key = ctx.mode === 'compare' ? 'compare' : ctx.mode;
			const ids = read( key ).slice( 0, key === 'compare' ? 4 : 50 );
			if ( ! ids.length ) {
				ctx.items = [];
				ctx.loaded = true;
				ctx.loading = false;
				return;
			}
			try {
				const res = yield fetch(
					`${ config().restUrl }properties?include=${ ids.join( ',' ) }`,
					{ credentials: 'omit' }
				);
				const items = res.ok ? yield res.json() : [];
				ctx.items = items.map( ( item ) => ( {
					...item,
					bedsText: item.bedrooms ? `${ item.bedrooms } bd` : '',
					featuresText: ( item.features || [] ).join( ', ' ) || '—',
					landArea: item.landArea || '—',
					yearBuilt: item.yearBuilt || '—',
					garages: item.garages || '—',
				} ) );
			} catch ( e ) {
				ctx.items = [];
			}
			ctx.loaded = true;
			ctx.loading = false;
		},
	},
} );

// Hydrate saved lists from storage as soon as the module runs.
state.favorites = read( 'favorites' );
state.compare = read( 'compare' );
