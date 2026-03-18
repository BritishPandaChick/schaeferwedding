import { __ } from '@wordpress/i18n';
import { useEffect, useRef, useState } from '@wordpress/element';
import { Box, Button, CloseButton, Flex, Text } from '@chakra-ui/react';
import { dismissCacheTip, installOrActivatePlugin } from '../services/api';
import { toaster } from './Toaster';

/**
 * Caching Performance Tip Banner Component
 * Recommends installing Super Page Cache when no caching plugin is detected.
 *
 * @param {Object}   props              Component props.
 * @param {string}   props.pluginPath   Plugin path (e.g., 'plugin-name/plugin-name.php').
 * @param {string}   props.pluginSlug   Plugin slug for WordPress.org (e.g., 'plugin-name').
 * @param {string}   props.learnMoreUrl URL to the plugin's WordPress.org page.
 * @param {boolean}  props.isInstalled  Whether Super Page Cache is already installed.
 * @param {Function} props.onDismiss    Callback fired after dismissal.
 */
function CachingTipBanner( {
	pluginPath,
	pluginSlug,
	learnMoreUrl,
	isInstalled,
	onDismiss,
} ) {
	const [ isDismissing, setIsDismissing ] = useState( false );
	const [ isInstalling, setIsInstalling ] = useState( false );
	const reloadTimeoutRef = useRef( null );

	useEffect( () => {
		return () => {
			if ( reloadTimeoutRef.current ) {
				clearTimeout( reloadTimeoutRef.current );
			}
		};
	}, [] );

	const handleInstall = async ( e ) => {
		e.preventDefault();
		setIsInstalling( true );

		try {
			await installOrActivatePlugin( pluginPath, pluginSlug );
			toaster.success( {
				title: __( 'Success', 'anti-spam' ),
				description: isInstalled
					? __(
							'Super Page Cache has been activated successfully.',
							'anti-spam'
					  )
					: __(
							'Super Page Cache has been installed and activated successfully.',
							'anti-spam'
					  ),
				duration: 3000,
			} );
			reloadTimeoutRef.current = setTimeout(
				() => window.location.reload(),
				3000
			);
		} catch ( error ) {
			toaster.error( {
				title: __( 'Operation failed', 'anti-spam' ),
				description:
					error?.message ||
					__(
						'Failed to install or activate the plugin.',
						'anti-spam'
					),
				duration: 5000,
			} );
			setIsInstalling( false );
		}
	};

	const handleDismiss = async () => {
		setIsDismissing( true );
		try {
			await dismissCacheTip();
		} catch ( e ) {}
		onDismiss();
	};

	return (
		<Box
			bg="orange.50"
			border="1px solid"
			borderColor="orange.200"
			borderRadius="md"
			p={ 4 }
			position="relative"
		>
			<CloseButton
				position="absolute"
				top={ 3 }
				right={ 3 }
				size="sm"
				color="gray.500"
				onClick={ handleDismiss }
				disabled={ isDismissing }
			/>

			<Flex gap={ 2 } align="center" mb={ 2 }>
				{ /* Warning circle icon */ }
				<svg
					width="18"
					height="18"
					viewBox="0 0 24 24"
					fill="none"
					xmlns="http://www.w3.org/2000/svg"
					style={ { flexShrink: 0 } }
					aria-hidden="true"
					focusable="false"
				>
					<circle
						cx="12"
						cy="12"
						r="10"
						stroke="#c05621"
						strokeWidth="2"
					/>
					<path
						d="M12 8v4"
						stroke="#c05621"
						strokeWidth="2"
						strokeLinecap="round"
					/>
					<circle cx="12" cy="16" r="1" fill="#c05621" />
				</svg>
				<Text fontWeight="bold" color="orange.700">
					{ __( 'Performance tip:', 'anti-spam' ) }
				</Text>
				<Text color="gray.800">
					{ __( 'No page caching detected', 'anti-spam' ) }
				</Text>
			</Flex>

			<Text fontSize="sm" color="gray.700" mb={ 4 }>
				{ __(
					'According to Google, pages that load in under 3 seconds retain significantly more visitors. Install Super Page Cache to speed up your site. It is free and works alongside Titan without any conflicts.',
					'anti-spam'
				) }
			</Text>

			<Flex gap={ 4 } align="center" mt={ 2 }>
				<Button
					bg="orange.700"
					color="white"
					_hover={ { bg: 'orange.800', color: 'white' } }
					size="sm"
					loading={ isInstalling }
					loadingText={
						isInstalled
							? __( 'Activating…', 'anti-spam' )
							: __( 'Installing…', 'anti-spam' )
					}
					disabled={ isInstalling || isDismissing }
					onClick={ handleInstall }
				>
					{ isInstalled
						? __( 'Activate', 'anti-spam' )
						: __( 'Install for Free', 'anti-spam' ) }
				</Button>
				<Text
					as="a"
					href={ learnMoreUrl }
					target="_blank"
					rel="noopener noreferrer"
					fontSize="sm"
					color="orange.700"
					textDecoration="underline"
				>
					{ __( 'Learn more', 'anti-spam' ) }
				</Text>
			</Flex>
		</Box>
	);
}

export default CachingTipBanner;
