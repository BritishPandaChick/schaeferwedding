import { __ } from '@wordpress/i18n';
import {
	Box,
	Flex,
	Heading,
	Badge,
	Input,
	Button,
	Text,
} from '@chakra-ui/react';
import { useEffect, useState, useContext } from '@wordpress/element';
import { AppContext } from '../../provider';
import { toaster } from '../Toaster';
import { manageLicense } from '../../services/api';

/**
 * LicenseCard Component
 * Displays and manages premium license activation or shows upsell
 */
const LicenseCard = () => {
	const [ isSaving, setIsSaving ] = useState( false );
	const { license, setLicense, isLicenseActive } = useContext( AppContext );
	const isActive = isLicenseActive;
	const hasPremium = window.titanSecurityObjects?.hasPremium || false;

	const [ inputValue, setInputValue ] = useState(
		( license.key !== 'free' ? license.key : '' ) || ''
	);

	useEffect( () => {
		setInputValue( ( license.key !== 'free' ? license.key : '' ) || '' );
	}, [ license.key ] );

	const handleInputChange = ( e ) => {
		if ( ! isActive ) {
			setInputValue( e.target.value );
		}
	};

	const onSaveLicense = async () => {
		setIsSaving( true );

		try {
			const response = await manageLicense(
				isActive ? 'deactivate' : 'activate',
				inputValue
			);

			if ( response.error || ! response.success ) {
				throw new Error( response.message || response.error );
			}

			if (
				response.success &&
				response.license &&
				'free' !== response.license.key
			) {
				setLicense( response.license );
				toaster.create( {
					title: __( 'Success', 'anti-spam' ),
					description:
						response.message ||
						__( 'License activated successfully.', 'anti-spam' ),
					type: 'success',
				} );

				// Remove the settings page notice after successful activation
				const settingsLink = document.querySelector(
					'a[href*="/wp-admin/admin.php?page=titan-security"]'
				);
				if ( settingsLink ) {
					const errorParent = settingsLink.closest( 'div.error' );
					if ( errorParent ) {
						errorParent.remove();
					}
				}
			} else if (
				'deactivate' === ( isActive ? 'deactivate' : 'activate' )
			) {
				setLicense( { key: 'free', status: 'inactive' } );
				toaster.create( {
					title: __( 'Success', 'anti-spam' ),
					description:
						response.message ||
						__( 'License deactivated successfully.', 'anti-spam' ),
					type: 'success',
				} );
			} else {
				toaster.create( {
					title: __( 'Error', 'anti-spam' ),
					description:
						response.message ||
						__( 'Failed to activate license.', 'anti-spam' ),
					type: 'error',
				} );
			}
		} catch ( error ) {
			toaster.create( {
				title: __( 'Error', 'anti-spam' ),
				description:
					error.message ||
					__(
						'An error occurred while processing your license.',
						'anti-spam'
					),
				type: 'error',
			} );
		}
		setIsSaving( false );
	};

	// If Pro is not installed, show upsell
	if ( ! hasPremium ) {
		return (
			<Box
				bg="purple.50"
				borderRadius="lg"
				borderWidth="1px"
				borderColor="purple.200"
				p={ 6 }
			>
				<Flex
					align={ { base: 'flex-start', sm: 'center' } }
					gap={ { base: 4, md: 6 } }
					direction={ { base: 'column', sm: 'row' } }
				>
					<Box flex="1">
						<Heading
							fontSize="lg"
							fontWeight="semibold"
							color="gray.900"
							mb={ 2 }
						>
							{ __( 'Upgrade to Premium', 'anti-spam' ) }
						</Heading>
						<Text fontSize="sm" color="gray.600" lineHeight="tall">
							{ __(
								'Get access to advanced security features, priority support, and regular updates.',
								'anti-spam'
							) }
						</Text>
					</Box>
					<Button
						colorScheme="purple"
						color="white"
						_hover={ { color: 'white' } }
						size="md"
						fontWeight="semibold"
						minW={ { base: 'auto', sm: '180px' } }
						w={ { base: 'full', sm: 'auto' } }
						asChild
					>
						<a
							href={ window.titanSecurityObjects?.upgradeUrl }
							target="_blank"
							rel="noopener noreferrer"
						>
							{ __( 'Get Premium', 'anti-spam' ) }
						</a>
					</Button>
				</Flex>
			</Box>
		);
	}

	return (
		<Box
			bg="white"
			borderRadius="lg"
			borderWidth="1px"
			borderColor="gray.200"
			p={ 6 }
		>
			<Flex align="center" justify="space-between" mb={ 4 }>
				<Heading fontSize="lg" fontWeight="semibold" color="gray.900">
					{ __( 'License', 'anti-spam' ) }
				</Heading>
				<Badge
					bg={ isActive ? 'green.50' : 'yellow.50' }
					color={ isActive ? 'green.800' : 'yellow.800' }
					fontSize="xs"
					fontWeight="semibold"
					px={ 3 }
					py={ 1 }
					borderRadius="full"
				>
					{ isActive
						? __( 'Active', 'anti-spam' )
						: __( 'Inactive', 'anti-spam' ) }
				</Badge>
			</Flex>

			<Flex
				gap={ 3 }
				align={ { base: 'stretch', sm: 'center' } }
				direction={ { base: 'column', sm: 'row' } }
			>
				<Input
					placeholder={ __( 'Enter license key', 'anti-spam' ) }
					size="md"
					flex="1"
					value={
						isActive
							? '******************************' +
							  ( license.key ? license.key.slice( -5 ) : '' )
							: inputValue
					}
					onChange={ handleInputChange }
					disabled={ isActive || isSaving }
					readOnly={ isActive }
					borderColor="gray.300"
				/>
				<Button
					color={ isActive ? 'black' : 'white' }
					colorScheme={ isActive ? 'red' : 'purple' }
					variant={ isActive ? 'outline' : 'solid' }
					size="md"
					fontWeight="semibold"
					minW={ { base: 'auto', sm: '180px' } }
					w={ { base: 'full', sm: 'auto' } }
					disabled={
						( ! isActive && ! inputValue.trim() ) || isSaving
					}
					onClick={ onSaveLicense }
				>
					{ isSaving
						? __( 'Processing...', 'anti-spam' )
						: isActive
						? __( 'Deactivate', 'anti-spam' )
						: __( 'Activate', 'anti-spam' ) }
				</Button>
			</Flex>

			{ ! isActive && (
				<Text mt={ 4 } fontSize="xs" color="gray.600" lineHeight="tall">
					{ __(
						'Enter your license key to activate premium features and support.',
						'anti-spam'
					) }
				</Text>
			) }
		</Box>
	);
};

export default LicenseCard;
