import { useState, useEffect, useCallback } from '@wordpress/element';

const VALID_PAGES = [
	'dashboard',
	'antispam',
	'tweaks',
	'settings',
	'backup',
	'logs',
];

/**
 * Custom routing hook for WordPress admin query parameter navigation
 *
 * @param {string} defaultPage - Default page to show
 * @returns {Object} { currentPage, navigateTo }
 */
export function useRouting( defaultPage = 'dashboard' ) {
	/**
	 * Read subpage from URL, fallback to default, validate against whitelist
	 */
	const getPageFromUrl = useCallback( () => {
		const params = new URLSearchParams( window.location.search );
		const page = params.get( 'subpage' ) || defaultPage;
		return VALID_PAGES.includes( page ) ? page : defaultPage;
	}, [ defaultPage ] );

	/**
	 * Read action parameter from URL
	 */
	const getActionFromUrl = useCallback( () => {
		const params = new URLSearchParams( window.location.search );
		return params.get( 'action' ) || '';
	}, [] );

	const [ currentPage, setCurrentPage ] = useState( getPageFromUrl );
	const [ currentAction, setCurrentAction ] = useState( getActionFromUrl );

	/**
	 * Navigate to a page by updating URL and state
	 *
	 * @param {string} page - Page to navigate to
	 * @param {string|null} action - Optional action parameter
	 */
	const navigateTo = useCallback(
		( page, action = null ) => {
			const url = new URL( window.location.href );

			if ( page === defaultPage ) {
				// Cleaner URL for default page
				url.searchParams.delete( 'subpage' );
			} else {
				url.searchParams.set( 'subpage', page );
			}

			if ( action ) {
				url.searchParams.set( 'action', action );
			} else {
				url.searchParams.delete( 'action' );
			}

			window.history.pushState( {}, '', url.toString() );
			setCurrentPage( page );
			setCurrentAction( action || '' );
		},
		[ defaultPage ]
	);

	/**
	 * Handle browser back/forward buttons
	 */
	useEffect( () => {
		const handlePopState = () => {
			setCurrentPage( getPageFromUrl() );
			setCurrentAction( getActionFromUrl() );
		};

		window.addEventListener( 'popstate', handlePopState );
		return () => window.removeEventListener( 'popstate', handlePopState );
	}, [ getPageFromUrl, getActionFromUrl ] );

	return { currentPage, currentAction, navigateTo };
}
