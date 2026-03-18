import { __ } from '@wordpress/i18n';
import { useState, useContext } from '@wordpress/element';
import {
	Box,
	Flex,
	Text,
	Button,
	Spinner,
	Textarea,
	SimpleGrid,
} from '@chakra-ui/react';
import { AppContext } from '../../provider';
import {
	regenerateBackupCodes,
	saveIpWhitelist,
	disableTwoFactor,
} from '../../services/api';
import { toaster } from '../Toaster';

/**
 * ActiveCard Component
 * Displays backup codes, IP whitelist, and disable option for active 2FA users
 */
function ActiveCard( { disabled = false } ) {
	const { twoFactorData, setTwoFactorData } = useContext( AppContext );

	const placeholderCodes = [
		'A1B2C3',
		'D4E5F6',
		'G7H8I9',
		'J0K1L2',
		'M3N4O5',
		'P6Q7R8',
	];

	const [ restoreCodes, setRestoreCodes ] = useState(
		disabled ? placeholderCodes : twoFactorData.restore_codes || []
	);
	const [ ipWhitelist, setIpWhitelist ] = useState(
		disabled
			? '192.168.1.1\n10.0.0.0/24'
			: ( twoFactorData.ip_whitelist || [] ).join( '\n' )
	);
	const [ isRegenerating, setIsRegenerating ] = useState( false );
	const [ isSavingIps, setIsSavingIps ] = useState( false );
	const [ isDisabling, setIsDisabling ] = useState( false );
	const [ showConfirmDisable, setShowConfirmDisable ] = useState( false );
	const [ copiedIndex, setCopiedIndex ] = useState( null );

	const handleRegenerateCodes = async () => {
		setIsRegenerating( true );
		try {
			const response = await regenerateBackupCodes();
			if ( response.success ) {
				setRestoreCodes( response.restore_codes );
				setTwoFactorData( {
					...twoFactorData,
					restore_codes: response.restore_codes,
				} );
				toaster.success( {
					title: __( 'Backup codes regenerated', 'anti-spam' ),
					description:
						response.message ||
						__(
							'New codes have been sent to your email.',
							'anti-spam'
						),
				} );
			}
		} catch ( error ) {
			toaster.error( {
				title: __( 'Error', 'anti-spam' ),
				description:
					error.message ||
					__( 'Failed to regenerate backup codes.', 'anti-spam' ),
			} );
		} finally {
			setIsRegenerating( false );
		}
	};

	const handleSaveIps = async () => {
		setIsSavingIps( true );
		try {
			const ips = ipWhitelist
				.split( '\n' )
				.map( ( ip ) => ip.trim() )
				.filter( Boolean );
			const response = await saveIpWhitelist( ips );
			if ( response.success ) {
				setTwoFactorData( {
					...twoFactorData,
					ip_whitelist: ips,
				} );
				toaster.success( {
					title: __( 'IP whitelist saved', 'anti-spam' ),
					description:
						response.message ||
						__(
							'Your IP whitelist has been updated.',
							'anti-spam'
						),
				} );
			}
		} catch ( error ) {
			toaster.error( {
				title: __( 'Error', 'anti-spam' ),
				description:
					error.message ||
					__( 'Failed to save IP whitelist.', 'anti-spam' ),
			} );
		} finally {
			setIsSavingIps( false );
		}
	};

	const handleDisable = async () => {
		setIsDisabling( true );
		try {
			const response = await disableTwoFactor();
			if ( response.success ) {
				setTwoFactorData( {
					...twoFactorData,
					enabled: false,
					setup_complete: false,
					restore_codes: [],
					qr_value: '',
					secret_display: '',
				} );
				toaster.success( {
					title: __( '2FA disabled', 'anti-spam' ),
					description:
						response.message ||
						__(
							'Two-factor authentication has been disabled.',
							'anti-spam'
						),
				} );
			}
		} catch ( error ) {
			toaster.error( {
				title: __( 'Error', 'anti-spam' ),
				description:
					error.message ||
					__( 'Failed to disable 2FA.', 'anti-spam' ),
			} );
		} finally {
			setIsDisabling( false );
			setShowConfirmDisable( false );
		}
	};

	const isCodeUsed = ( codeStr ) => {
		return typeof codeStr === 'string' && codeStr.startsWith( '-' );
	};

	const getCodeDisplay = ( codeStr ) => {
		if ( isCodeUsed( codeStr ) ) {
			return codeStr.slice( 1 );
		}
		return codeStr;
	};

	const handleCopyCode = ( codeStr, index ) => {
		if ( isCodeUsed( codeStr ) ) {
			return;
		}
		navigator.clipboard.writeText( getCodeDisplay( codeStr ) ).then( () => {
			setCopiedIndex( index );
			setTimeout( () => setCopiedIndex( null ), 1500 );
		} );
	};

	const handleCopyAll = () => {
		const activeCodes = restoreCodes
			.filter( ( c ) => ! isCodeUsed( c ) )
			.map( getCodeDisplay )
			.join( '\n' );
		navigator.clipboard.writeText( activeCodes ).then( () => {
			toaster.success( {
				title: __( 'Copied', 'anti-spam' ),
				description: __(
					'All active codes copied to clipboard.',
					'anti-spam'
				),
			} );
		} );
	};

	const cardStyle = {
		bg: 'white',
		borderRadius: 'lg',
		borderWidth: '1px',
		borderColor: 'gray.200',
		overflow: 'hidden',
	};

	return (
		<>
			{ /* Backup Codes */ }
			<Box { ...cardStyle }>
				<Box px={ 6 } pt={ 5 } pb={ 4 }>
					<Text
						fontSize="xs"
						fontWeight="700"
						textTransform="uppercase"
						letterSpacing="wider"
						color="purple.600"
						mb={ 1.5 }
					>
						{ __( 'Recovery', 'anti-spam' ) }
					</Text>
					<Text
						fontSize="lg"
						fontWeight="bold"
						color="gray.900"
						mb={ 0.5 }
					>
						{ __( 'Backup Codes', 'anti-spam' ) }
					</Text>
					<Text fontSize="sm" color="gray.500">
						{ __(
							'Use these one-time codes if you lose access to your device.',
							'anti-spam'
						) }
					</Text>
				</Box>

				<Box px={ 6 } pb={ 4 }>
					{ restoreCodes.length > 0 ? (
						<SimpleGrid columns={ 3 } gap={ 3 }>
							{ restoreCodes.map( ( codeStr, index ) => {
								const used = isCodeUsed( codeStr );
								return (
									<Box
										key={ index }
										px={ 4 }
										py={ 3 }
										bg="gray.50"
										borderRadius="lg"
										borderWidth="1px"
										borderColor="gray.200"
										textAlign="center"
										fontFamily="mono"
										fontSize="sm"
										fontWeight="700"
										letterSpacing="widest"
										color={ used ? 'gray.400' : 'gray.800' }
										textDecoration={
											used ? 'line-through' : 'none'
										}
										opacity={ used ? 0.5 : 1 }
										cursor={ used ? 'default' : 'pointer' }
										transition="all 0.15s"
										_hover={
											used
												? {}
												: {
														bg: 'purple.50',
														borderColor:
															'purple.200',
												  }
										}
										onClick={ () =>
											handleCopyCode( codeStr, index )
										}
										position="relative"
									>
										{ copiedIndex === index && (
											<Box
												position="absolute"
												top="-7px"
												left="50%"
												transform="translateX(-50%)"
												bg="gray.800"
												color="white"
												fontSize="10px"
												px={ 2 }
												py={ 0.5 }
												borderRadius="md"
												whiteSpace="nowrap"
											>
												{ __( 'Copied!', 'anti-spam' ) }
											</Box>
										) }
										{ getCodeDisplay( codeStr ) }
									</Box>
								);
							} ) }
						</SimpleGrid>
					) : (
						<Text
							fontSize="sm"
							color="gray.500"
							textAlign="center"
							py={ 4 }
						>
							{ __(
								'No backup codes available. Click Regenerate to create new ones.',
								'anti-spam'
							) }
						</Text>
					) }
				</Box>

				<Box mx={ 6 } borderTopWidth="1px" borderColor="gray.200" />

				<Flex px={ 6 } py={ 4 } justify="space-between" align="center">
					<Flex align="center" gap={ 1.5 } color="gray.500">
						<svg
							width="14"
							height="14"
							viewBox="0 0 24 24"
							fill="none"
							xmlns="http://www.w3.org/2000/svg"
						>
							<circle
								cx="12"
								cy="12"
								r="10"
								stroke="currentColor"
								strokeWidth="2"
							/>
							<line
								x1="12"
								y1="16"
								x2="12"
								y2="12"
								stroke="currentColor"
								strokeWidth="2"
								strokeLinecap="round"
							/>
							<line
								x1="12"
								y1="8"
								x2="12.01"
								y2="8"
								stroke="currentColor"
								strokeWidth="2"
								strokeLinecap="round"
							/>
						</svg>
						<Text fontSize="xs" color="gray.500">
							{ __( 'Click a code to copy', 'anti-spam' ) }
							{ ' · ' }
							{ __( 'Store these somewhere safe', 'anti-spam' ) }
						</Text>
					</Flex>
					<Flex gap={ 2 }>
						<Button
							onClick={ handleCopyAll }
							size="sm"
							variant="outline"
							borderColor="gray.300"
							color="gray.700"
							fontSize="xs"
							fontWeight="600"
							disabled={ restoreCodes.length === 0 || disabled }
						>
							{ __( 'Copy All', 'anti-spam' ) }
						</Button>
						<Button
							onClick={ handleRegenerateCodes }
							size="sm"
							variant="outline"
							borderColor="gray.300"
							color="gray.700"
							fontSize="xs"
							fontWeight="600"
							disabled={ isRegenerating || disabled }
						>
							{ isRegenerating && (
								<Spinner size="xs" mr={ 1.5 } />
							) }
							{ isRegenerating
								? __( 'Regenerating...', 'anti-spam' )
								: __( 'Regenerate', 'anti-spam' ) }
						</Button>
					</Flex>
				</Flex>
			</Box>

			{ /* IP Whitelist */ }
			<Box { ...cardStyle }>
				<Flex
					px={ 6 }
					pt={ 5 }
					pb={ 4 }
					justify="space-between"
					align="flex-start"
				>
					<Box>
						<Text
							fontSize="xs"
							fontWeight="700"
							textTransform="uppercase"
							letterSpacing="wider"
							color="purple.600"
							mb={ 1.5 }
						>
							{ __( 'Trusted Access', 'anti-spam' ) }
						</Text>
						<Text
							fontSize="lg"
							fontWeight="bold"
							color="gray.900"
							mb={ 0.5 }
						>
							{ __( 'IP Whitelist', 'anti-spam' ) }
						</Text>
						<Text fontSize="sm" color="gray.500">
							{ __(
								'Skip 2FA for trusted IP addresses.',
								'anti-spam'
							) }
						</Text>
					</Box>
					<Button
						onClick={ handleSaveIps }
						size="sm"
						bg="purple.600"
						color="white"
						fontSize="xs"
						fontWeight="600"
						borderRadius="md"
						_hover={ { bg: 'purple.700' } }
						disabled={ isSavingIps || disabled }
						mt={ 3 }
					>
						{ isSavingIps && <Spinner size="xs" mr={ 1.5 } /> }
						{ isSavingIps
							? __( 'Saving...', 'anti-spam' )
							: __( 'Save', 'anti-spam' ) }
					</Button>
				</Flex>
				<Box px={ 6 } pb={ 4 }>
					<Textarea
						value={ ipWhitelist }
						onChange={ ( e ) => setIpWhitelist( e.target.value ) }
						rows={ 4 }
						fontSize="sm"
						fontFamily="mono"
						borderRadius="lg"
						bg="gray.50"
						borderColor="gray.200"
						_focus={ {
							borderColor: 'purple.300',
							bg: 'white',
						} }
						placeholder={ '192.168.1.1\n10.0.0.0/24\n203.0.113.42' }
						disabled={ disabled }
					/>
				</Box>
				<Flex
					align="center"
					gap={ 1.5 }
					px={ 6 }
					pb={ 5 }
					color="gray.500"
				>
					<svg
						width="14"
						height="14"
						viewBox="0 0 24 24"
						fill="none"
						xmlns="http://www.w3.org/2000/svg"
					>
						<circle
							cx="12"
							cy="12"
							r="10"
							stroke="currentColor"
							strokeWidth="2"
						/>
						<line
							x1="12"
							y1="16"
							x2="12"
							y2="12"
							stroke="currentColor"
							strokeWidth="2"
							strokeLinecap="round"
						/>
						<line
							x1="12"
							y1="8"
							x2="12.01"
							y2="8"
							stroke="currentColor"
							strokeWidth="2"
							strokeLinecap="round"
						/>
					</svg>
					<Text fontSize="xs">
						{ __(
							'Enter one IP address or CIDR range per line',
							'anti-spam'
						) }
					</Text>
				</Flex>
			</Box>

			{ /* Disable 2FA */ }
			<Box
				bg="red.50"
				borderRadius="lg"
				borderWidth="1px"
				borderColor="red.200"
				overflow="hidden"
			>
				<Flex px={ 6 } py={ 5 } justify="space-between" align="center">
					<Box>
						<Text fontSize="lg" fontWeight="bold" color="red.600">
							{ __(
								'Disable Two-Factor Authentication',
								'anti-spam'
							) }
						</Text>
						<Text fontSize="sm" color="gray.600" mt={ 0.5 }>
							{ __(
								'This will remove 2FA from your account. You can re-enable it at any time.',
								'anti-spam'
							) }
						</Text>
					</Box>
					{ showConfirmDisable ? (
						<Flex gap={ 2 } flexShrink={ 0 }>
							<Button
								onClick={ handleDisable }
								size="sm"
								bg="red.600"
								color="white"
								fontSize="xs"
								fontWeight="600"
								_hover={ { bg: 'red.700' } }
								disabled={ isDisabling }
							>
								{ isDisabling && (
									<Spinner size="xs" mr={ 1.5 } />
								) }
								{ isDisabling
									? __( 'Disabling...', 'anti-spam' )
									: __( 'Confirm', 'anti-spam' ) }
							</Button>
							<Button
								onClick={ () => setShowConfirmDisable( false ) }
								size="sm"
								variant="outline"
								borderColor="gray.300"
								color="gray.700"
								fontSize="xs"
								fontWeight="600"
							>
								{ __( 'Cancel', 'anti-spam' ) }
							</Button>
						</Flex>
					) : (
						<Button
							onClick={ () => setShowConfirmDisable( true ) }
							size="sm"
							variant="outline"
							borderColor="red.300"
							color="red.600"
							fontSize="xs"
							fontWeight="600"
							flexShrink={ 0 }
							_hover={ {
								bg: 'red.100',
								borderColor: 'red.400',
							} }
							disabled={ disabled }
						>
							{ __( 'Disable 2FA', 'anti-spam' ) }
						</Button>
					) }
				</Flex>
			</Box>
		</>
	);
}

export default ActiveCard;
