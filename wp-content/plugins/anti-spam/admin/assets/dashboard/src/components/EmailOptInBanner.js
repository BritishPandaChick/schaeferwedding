import { __ } from '@wordpress/i18n';
import { Box, Button, CloseButton, Flex, Input, Text } from '@chakra-ui/react';
import { useState } from '@wordpress/element';
import { toaster } from './Toaster';

/**
 * Email Opt-in Banner Component
 * Displays a banner for users to opt-in to product updates and news
 */
function EmailOptInBanner( { onDismiss } ) {
	const userEmail = window.titanSecurityObjects?.userEmail || '';
	const [ email, setEmail ] = useState( userEmail );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ error, setError ] = useState( '' );

	// Email validation
	const isValidEmail = ( email ) => {
		const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
		return emailRegex.test( email );
	};

	// Handle subscription
	const handleSubscribe = () => {
		// Prevent multiple submissions
		if ( isLoading ) {
			return;
		}

		setError( '' );

		// Validate email
		if ( ! email || ! isValidEmail( email ) ) {
			setError(
				__( 'Please enter a valid email address.', 'anti-spam' )
			);
			return;
		}

		setIsLoading( true );

		fetch( 'https://api.themeisle.com/tracking/subscribe', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				Accept: 'application/json, */*;q=0.1',
				'Cache-Control': 'no-cache',
			},
			body: JSON.stringify( {
				slug: 'titan-security',
				site:
					window.titanSecurityObjects?.siteUrl ||
					window.location.origin,
				email,
			} ),
		} )
			.then( ( r ) => {
				setIsLoading( false );
				localStorage.setItem( 'titan_hide_optin', 'yes' );
				toaster.success( {
					title: __( 'Subscribed successfully!', 'anti-spam' ),
				} );
				onDismiss();
			} )
			.catch( () => {
				setIsLoading( false );
				localStorage.setItem( 'titan_hide_optin', 'yes' );
				onDismiss();
			} );
	};

	// Handle dismiss
	const handleDismiss = () => {
		localStorage.setItem( 'titan_hide_optin', 'yes' );
		onDismiss();
	};

	return (
		<Box
			bg="purple.50"
			borderBottom="1px solid"
			borderBottomColor="purple.200"
			p={ 6 }
			position="relative"
		>
			<Flex
				align={ { base: 'flex-start', lg: 'center' } }
				justify="space-between"
				gap={ 4 }
				direction={ { base: 'column', lg: 'row' } }
			>
				{ /* Bell Icon */ }
				<Flex
					bg="purple.500"
					borderRadius="full"
					w={ 12 }
					h={ 12 }
					align="center"
					justify="center"
					flexShrink={ 0 }
					opacity={ isLoading ? 0.6 : 1 }
					transition="opacity 0.2s"
				>
					<svg
						width="24"
						height="24"
						viewBox="0 0 24 24"
						fill="none"
						xmlns="http://www.w3.org/2000/svg"
						style={ { width: '24px', height: '24px' } }
					>
						<path
							d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"
							fill="white"
						/>
					</svg>
				</Flex>

				{ /* Text Content */ }
				<Box
					flex={ 1 }
					opacity={ isLoading ? 0.6 : 1 }
					transition="opacity 0.2s"
				>
					<Text
						fontSize="md"
						fontWeight="bold"
						color="gray.900"
						mb={ 1 }
					>
						{ __( 'Stay in the loop', 'anti-spam' ) }
					</Text>
					<Text fontSize="sm" color="gray.600">
						{ __(
							'Get product updates and news straight to your inbox.',
							'anti-spam'
						) }
					</Text>
				</Box>

				{ /* Email Input and Button Group */ }
				<Box flexShrink={ 0 }>
					<Flex
						borderWidth="1px"
						borderColor={ error ? 'red.500' : 'gray.300' }
						borderRadius="md"
						overflow="hidden"
						opacity={ isLoading ? 0.6 : 1 }
						transition="opacity 0.2s"
						_focusWithin={ {
							borderColor: error ? 'red.500' : 'purple.500',
							boxShadow: error
								? '0 0 0 1px var(--chakra-colors-red-500)'
								: '0 0 0 1px var(--chakra-colors-purple-500)',
						} }
					>
						<Input
							type="email"
							placeholder="you@example.com"
							value={ email }
							onChange={ ( e ) => {
								setEmail( e.target.value );
								setError( '' );
							} }
							onKeyDown={ ( e ) => {
								if ( e.key === 'Enter' && ! isLoading ) {
									handleSubscribe();
								}
							} }
							bg="white"
							border="0 !important"
							_focus={ {
								border: '0 !important',
								boxShadow: 'none',
							} }
							w={ { base: '100%', md: '300px' } }
							borderRadius={ 0 }
							isDisabled={ isLoading }
							cursor={ isLoading ? 'not-allowed' : 'text' }
						/>
						<Button
							colorScheme="purple"
							color="white"
							onClick={ handleSubscribe }
							_hover={ { color: 'white' } }
							borderRadius={ 0 }
							px={ 6 }
							border="none"
							isLoading={ isLoading }
							isDisabled={ isLoading }
							loadingText={ __( 'Subscribing...', 'anti-spam' ) }
						>
							{ __( 'Subscribe', 'anti-spam' ) }
						</Button>
					</Flex>
					{ error && (
						<Text fontSize="xs" color="red.500" mt={ 1 }>
							{ error }
						</Text>
					) }
				</Box>

				{ /* Close Button */ }
				<CloseButton
					onClick={ handleDismiss }
					size="md"
					color="gray.500"
					_hover={ { color: 'gray.700' } }
					flexShrink={ 0 }
					position={ { base: 'absolute', lg: 'static' } }
					top={ { base: 3, lg: 'auto' } }
					right={ { base: 3, lg: 'auto' } }
					isDisabled={ isLoading }
					opacity={ isLoading ? 0.4 : 1 }
					cursor={ isLoading ? 'not-allowed' : 'pointer' }
					transition="opacity 0.2s"
				/>
			</Flex>
		</Box>
	);
}

export default EmailOptInBanner;
