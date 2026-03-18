import { __ } from '@wordpress/i18n';
import { useState, useEffect, useContext } from '@wordpress/element';
import {
	Button,
	Input,
	Text,
	Alert,
	VStack,
	HStack,
	Field,
	Dialog,
	List,
	Portal,
} from '@chakra-ui/react';
import { changeDatabasePrefix } from '../../services/api';
import { AppContext } from '../../provider';
import { toaster } from '../Toaster';

/**
 * Generate a random database prefix
 *
 * @returns {string} Random prefix
 */
function generateRandomPrefix() {
	const chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
	let result = '';
	for ( let i = 0; i < 4; i++ ) {
		result += chars.charAt( Math.floor( Math.random() * chars.length ) );
	}
	return result + '_';
}

/**
 * DatabasePrefixFix Modal Component
 * Handles the database prefix change form and confirmation
 *
 * @param {Object} props - Component props
 * @param {boolean} props.isOpen - Whether the modal is open
 * @param {Function} props.onClose - Function to close the modal
 * @param {number} props.issueId - The audit issue ID being fixed
 * @param {Function} props.onSuccess - Callback after successful fix
 */
function DatabasePrefixFix( { isOpen, onClose, issueId, onSuccess } ) {
	const [ newPrefix, setNewPrefix ] = useState( generateRandomPrefix() );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ showUpsell, setShowUpsell ] = useState( false );
	const [ isConfirmOpen, setIsConfirmOpen ] = useState( false );

	const { isLicenseActive } = useContext( AppContext );

	const currentPrefix = window.titanSecurityObjects?.tablePrefix || 'wp_';

	// Check premium status when modal opens
	useEffect( () => {
		if ( isOpen && ! isLicenseActive ) {
			setShowUpsell( true );
		} else {
			setShowUpsell( false );
		}
	}, [ isOpen, isLicenseActive ] );

	const handleSubmit = ( e ) => {
		e.preventDefault();

		if ( ! newPrefix || newPrefix.trim() === '' ) {
			toaster.create( {
				title: __( 'Error', 'anti-spam' ),
				description: __( 'Prefix cannot be empty.', 'anti-spam' ),
				type: 'error',
			} );
			return;
		}

		setIsConfirmOpen( true );
	};

	const handleConfirm = async () => {
		setIsLoading( true );

		try {
			await changeDatabasePrefix( newPrefix, issueId );

			setIsConfirmOpen( false );
			onClose();

			// Trigger success callback to refresh audit data
			if ( onSuccess ) {
				onSuccess();
			}

			// Reload immediately to pick up new prefix
			window.location.reload();
		} catch ( error ) {
			// Extract error message and ensure it's a plain string
			let errorMessage = __(
				'Failed to change database prefix.',
				'anti-spam'
			);

			if ( error?.message && typeof error.message === 'string' ) {
				errorMessage = error.message;
			} else if (
				error?.data?.message &&
				typeof error.data.message === 'string'
			) {
				errorMessage = error.data.message;
			}

			toaster.create( {
				title: __( 'Error', 'anti-spam' ),
				description: errorMessage,
				type: 'error',
			} );
			setIsConfirmOpen( false );
		} finally {
			setIsLoading( false );
		}
	};

	// Render upsell modal for non-premium users
	if ( showUpsell ) {
		return (
			<Portal>
				<Dialog.Root
					open={ isOpen }
					onOpenChange={ ( e ) => ( e.open ? null : onClose() ) }
					placement="center"
					size="md"
					lazyMount
					unmountOnExit
				>
					<Dialog.Backdrop />
					<Dialog.Positioner>
						<Dialog.Content>
							<Dialog.Header>
								{ __( 'Premium Feature', 'anti-spam' ) }
							</Dialog.Header>
							<Dialog.Body>
								<Text>
									{ __(
										'Changing the database prefix is a premium feature that helps protect your site from SQL injection attacks and automated bots.',
										'anti-spam'
									) }
								</Text>
							</Dialog.Body>
							<Dialog.Footer>
								<Button
									colorScheme="blue"
									onClick={ () =>
										window.open(
											window.titanSecurityObjects
												?.upgradeUrl,
											'_blank'
										)
									}
								>
									{ __( 'Upgrade to Pro', 'anti-spam' ) }
								</Button>
								<Button variant="outline" onClick={ onClose }>
									{ __( 'Cancel', 'anti-spam' ) }
								</Button>
							</Dialog.Footer>
						</Dialog.Content>
					</Dialog.Positioner>
				</Dialog.Root>
			</Portal>
		);
	}

	// Render fix modal for premium users
	return (
		<>
			{ /* Main fix modal */ }
			<Portal>
				<Dialog.Root
					open={ isOpen && ! isConfirmOpen }
					onOpenChange={ ( e ) => ( e.open ? null : onClose() ) }
					placement="center"
					size="lg"
					lazyMount
					unmountOnExit
				>
					<Dialog.Backdrop />
					<Dialog.Positioner>
						<Dialog.Content>
							<Dialog.Header>
								{ __( 'Change Database Prefix', 'anti-spam' ) }
							</Dialog.Header>
							<Dialog.Body>
								<VStack align="stretch" gap={ 4 }>
									<Text fontSize="sm" color="gray.600">
										{ __( 'Current prefix:', 'anti-spam' ) }{ ' ' }
										{ currentPrefix }
									</Text>

									<form
										onSubmit={ handleSubmit }
										id="database-prefix-form"
									>
										<VStack align="stretch" gap={ 4 }>
											<Field.Root required>
												<Field.Label
													fontSize="sm"
													fontWeight="medium"
												>
													{ __(
														'Please enter new prefix',
														'anti-spam'
													) }
												</Field.Label>
												<Input
													type="text"
													value={ newPrefix }
													onChange={ ( e ) =>
														setNewPrefix(
															e.target.value
														)
													}
													placeholder={ __(
														'Enter new prefix',
														'anti-spam'
													) }
													size="md"
													fontFamily="mono"
												/>
												<Field.HelperText
													fontSize="xs"
													color="gray.500"
												>
													{ __(
														'Only alphanumeric characters and underscores are allowed',
														'anti-spam'
													) }
												</Field.HelperText>
											</Field.Root>
										</VStack>
									</form>
								</VStack>
							</Dialog.Body>
							<Dialog.Footer gap={ 3 }>
								<HStack gap={ 3 }>
									<Button
										type="submit"
										form="database-prefix-form"
										colorScheme="blue"
									>
										{ __( 'Change Prefix', 'anti-spam' ) }
									</Button>
									<Button
										variant="outline"
										onClick={ onClose }
									>
										{ __( 'Cancel', 'anti-spam' ) }
									</Button>
								</HStack>
							</Dialog.Footer>
						</Dialog.Content>
					</Dialog.Positioner>
				</Dialog.Root>
			</Portal>

			{ /* Confirmation modal - DEBUGGING: Step 1 - Add translations */ }
			<Portal>
				<Dialog.Root
					open={ isConfirmOpen }
					onOpenChange={ ( e ) =>
						e.open ? null : setIsConfirmOpen( false )
					}
					placement="center"
					lazyMount
					unmountOnExit
				>
					<Dialog.Backdrop />
					<Dialog.Positioner>
						<Dialog.Content>
							<Dialog.Header>
								{ __(
									'Are you sure you want to change the database prefix?',
									'anti-spam'
								) }
							</Dialog.Header>
							<Dialog.Body>
								<Alert.Root status="warning" mb={ 4 }>
									<Alert.Indicator />
									<Alert.Content>
										<Alert.Description fontSize="sm">
											{ __(
												'Attention! The prefix for the names of all tables in your database will be changed. Please backup the database and wp-config.php file. If an error occurs when changing the database prefix, you can restore data from backup by replacing the wp-config.php file and restoring the database backup manually.',
												'anti-spam'
											) }
										</Alert.Description>
									</Alert.Content>
								</Alert.Root>
								<VStack align="stretch" gap={ 2 }>
									<Text fontSize="sm">
										<strong>
											{ __( 'Old prefix:', 'anti-spam' ) }
										</strong>{ ' ' }
										<Text
											as="span"
											fontFamily="mono"
											color="red.600"
										>
											{ currentPrefix }
										</Text>
									</Text>
									<Text fontSize="sm">
										<strong>
											{ __( 'New prefix:', 'anti-spam' ) }
										</strong>{ ' ' }
										<Text
											as="span"
											fontFamily="mono"
											color="green.600"
										>
											{ newPrefix }
										</Text>
									</Text>
								</VStack>
							</Dialog.Body>
							<Dialog.Footer gap={ 3 }>
								<Button
									colorScheme="red"
									onClick={ handleConfirm }
									loading={ isLoading }
								>
									{ __( 'Yes, confirm', 'anti-spam' ) }
								</Button>
								<Button
									variant="outline"
									onClick={ () => setIsConfirmOpen( false ) }
									disabled={ isLoading }
								>
									{ __( 'Cancel', 'anti-spam' ) }
								</Button>
							</Dialog.Footer>
						</Dialog.Content>
					</Dialog.Positioner>
				</Dialog.Root>
			</Portal>
		</>
	);
}

export default DatabasePrefixFix;
