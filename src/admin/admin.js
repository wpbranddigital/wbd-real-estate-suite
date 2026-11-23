/**
 * Admin helpers: confirmations for destructive actions and rejection reasons.
 * Vanilla JS, no jQuery.
 */
import './admin.scss';

const strings = window.wbdresAdmin || {};

document.addEventListener( 'click', ( event ) => {
	const link = event.target.closest( 'a.wbdres-confirm' );
	if ( link && ! window.confirm( link.dataset.confirm || strings.confirmDelete ) ) {
		event.preventDefault();
		return;
	}

	const reject = event.target.closest( 'a.wbdres-reject' );
	if ( reject ) {
		event.preventDefault();
		// eslint-disable-next-line no-alert
		const reason = window.prompt( reject.dataset.prompt || '' );
		if ( reason === null ) {
			return;
		}
		const url = new URL( reject.href );
		url.searchParams.set( 'reason', reason.slice( 0, 500 ) );
		window.location.href = url.toString();
	}
} );

document.addEventListener( 'submit', ( event ) => {
	const form = event.target;
	if ( form.classList.contains( 'wbdres-confirm-form' ) ) {
		const message = strings[ form.dataset.confirmKey ] || strings.confirmDelete;
		if ( ! window.confirm( message ) ) {
			event.preventDefault();
		}
		return;
	}

	// Bulk delete on the leads list.
	if ( form.classList.contains( 'wbdres-leads-form' ) ) {
		const top = form.querySelector( 'select[name="action"]' );
		const bottom = form.querySelector( 'select[name="action2"]' );
		const chosen = [ top?.value, bottom?.value ];
		if ( chosen.includes( 'delete' ) && ! window.confirm( strings.confirmDelete ) ) {
			event.preventDefault();
		}
	}
} );
