import { __ } from '@wordpress/i18n';
import { useState, useContext } from '@wordpress/element';
import {
	Box,
	Flex,
	Text,
	Button,
	Spinner,
	Input,
	QrCode,
} from '@chakra-ui/react';
import { AppContext } from '../../provider';
import { setupTwoFactor, verifyTwoFactor } from '../../services/api';
import { toaster } from '../Toaster';

/**
 * SetupCard Component
 * QR code display + verification for initial 2FA setup
 */
function SetupCard( { disabled = false } ) {
	const { twoFactorData, setTwoFactorData } = useContext( AppContext );

	const [ qrValue, setQrValue ] = useState( twoFactorData.qr_value || '' );
	const [ secretDisplay, setSecretDisplay ] = useState(
		twoFactorData.secret_display || ''
	);
	const [ code, setCode ] = useState( '' );
	const [ isVerifying, setIsVerifying ] = useState( false );
	const [ isRefreshing, setIsRefreshing ] = useState( false );
	const [ copied, setCopied ] = useState( false );

	const handleRefreshQR = async () => {
		setIsRefreshing( true );
		try {
			const response = await setupTwoFactor();
			if ( response.success ) {
				setQrValue( response.qr_value || '' );
				setSecretDisplay( response.secret_display );
			}
		} catch ( error ) {
			toaster.error( {
				title: __( 'Error', 'anti-spam' ),
				description:
					error.message ||
					__( 'Failed to refresh QR code.', 'anti-spam' ),
			} );
		} finally {
			setIsRefreshing( false );
		}
	};

	const handleVerify = async () => {
		if ( ! code || code.length < 6 ) {
			toaster.error( {
				title: __( 'Invalid code', 'anti-spam' ),
				description: __( 'Please enter a 6-digit code.', 'anti-spam' ),
			} );
			return;
		}

		setIsVerifying( true );
		try {
			const response = await verifyTwoFactor( code.trim() );
			if ( response.success ) {
				setTwoFactorData( {
					...twoFactorData,
					enabled: true,
					setup_complete: true,
					restore_codes: response.restore_codes || [],
					qr_value: '',
					secret_display: '',
				} );
				toaster.success( {
					title: __(
						'Two-factor authentication activated',
						'anti-spam'
					),
					description:
						response.message ||
						__(
							'Your account is now protected with 2FA.',
							'anti-spam'
						),
				} );
			}
		} catch ( error ) {
			toaster.error( {
				title: __( 'Verification failed', 'anti-spam' ),
				description:
					error.message ||
					__( 'Invalid code. Please try again.', 'anti-spam' ),
			} );
		} finally {
			setIsVerifying( false );
		}
	};

	const handleCopySecret = () => {
		const secret = secretDisplay.replace( /\s/g, '' );
		navigator.clipboard.writeText( secret ).then( () => {
			setCopied( true );
			setTimeout( () => setCopied( false ), 2000 );
		} );
	};

	const handleCodeChange = ( e ) => {
		const value = e.target.value.replace( /\D/g, '' ).slice( 0, 6 );
		setCode( value );
	};

	return (
		<Box
			bg="white"
			borderRadius="lg"
			borderWidth="1px"
			borderColor="gray.200"
			overflow="hidden"
		>
			<Box
				px={ 6 }
				py={ 4 }
				borderBottomWidth="1px"
				borderColor="gray.100"
			>
				<Text fontSize="lg" fontWeight="semibold" color="gray.900">
					{ __( 'Setup Authenticator App', 'anti-spam' ) }
				</Text>
			</Box>
			<Box px={ 6 } py={ 5 }>
				<Flex
					gap={ { base: 6, xl: 8 } }
					align="flex-start"
					flexWrap="wrap"
					direction={ { base: 'column', xl: 'row' } }
				>
					{ /* QR Code */ }
					<Box flexShrink={ 0 } w={ { base: '100%', xl: 'auto' } }>
						<Flex
							direction="column"
							align={ { base: 'center', xl: 'flex-start' } }
						>
							<Box
								borderWidth="1px"
								borderColor="gray.200"
								borderRadius="lg"
								p={ 3 }
								bg="white"
								w="fit-content"
							>
								{ disabled ? (
									<Box
										w="220px"
										h="220px"
										bg="gray.100"
										display="flex"
										alignItems="center"
										justifyContent="center"
										borderRadius="md"
									>
										<svg
											width="48"
											height="48"
											viewBox="0 0 24 24"
											fill="none"
											xmlns="http://www.w3.org/2000/svg"
										>
											<rect
												x="3"
												y="11"
												width="18"
												height="11"
												rx="2"
												stroke="#a0aec0"
												strokeWidth="2"
											/>
											<path
												d="M7 11V7a5 5 0 0 1 10 0v4"
												stroke="#a0aec0"
												strokeWidth="2"
												strokeLinecap="round"
											/>
										</svg>
									</Box>
								) : qrValue ? (
									<Box
										w="220px"
										h="220px"
										display="flex"
										alignItems="center"
										justifyContent="center"
									>
										<QrCode.Root
											value={ qrValue }
											w="220px"
											h="220px"
											encoding={ { ecc: 'M' } }
										>
											<QrCode.Frame w="220px" h="220px">
												<QrCode.Pattern />
											</QrCode.Frame>
										</QrCode.Root>
									</Box>
								) : (
									<Box
										w="220px"
										h="220px"
										bg="gray.50"
										display="flex"
										alignItems="center"
										justifyContent="center"
									>
										<Spinner size="md" />
									</Box>
								) }
							</Box>
							<Button
								onClick={ handleRefreshQR }
								variant="plain"
								fontSize="xs"
								color="purple.600"
								mt={ 2 }
								p={ 0 }
								h="auto"
								disabled={ isRefreshing || disabled }
							>
								{ isRefreshing
									? __( 'Refreshing...', 'anti-spam' )
									: __( 'Refresh QR Code', 'anti-spam' ) }
							</Button>
						</Flex>
					</Box>

					{ /* Instructions */ }
					<Box flex="1" minW={ { base: '100%', xl: '280px' } }>
						<Box mb={ 5 }>
							<Flex align="baseline" gap={ 2 } mb={ 2 }>
								<Text
									fontSize="sm"
									fontWeight="bold"
									color="purple.600"
								>
									1.
								</Text>
								<Text
									fontSize="sm"
									fontWeight="600"
									color="gray.800"
								>
									{ __(
										'Download an authenticator app',
										'anti-spam'
									) }
								</Text>
							</Flex>
							<Text fontSize="sm" color="gray.600" pl={ 5 }>
								{ __(
									'Google Authenticator, Authy, or any TOTP-compatible app.',
									'anti-spam'
								) }
							</Text>
						</Box>

						<Box mb={ 5 }>
							<Flex align="baseline" gap={ 2 } mb={ 2 }>
								<Text
									fontSize="sm"
									fontWeight="bold"
									color="purple.600"
								>
									2.
								</Text>
								<Text
									fontSize="sm"
									fontWeight="600"
									color="gray.800"
								>
									{ __(
										'Scan this QR code with your app',
										'anti-spam'
									) }
								</Text>
							</Flex>
							<Box pl={ 5 }>
								<Text fontSize="xs" color="gray.500" mb={ 1 }>
									{ __(
										'Or enter this key manually:',
										'anti-spam'
									) }
								</Text>
								<Flex align="center" gap={ 2 }>
									<Box
										bg="gray.50"
										px={ 3 }
										py={ 1.5 }
										borderRadius="md"
										fontFamily="mono"
										fontSize="sm"
										color="gray.800"
										letterSpacing="wider"
									>
										{ disabled
											? 'XXXX XXXX XXXX XXXX'
											: secretDisplay }
									</Box>
									<Button
										onClick={ handleCopySecret }
										size="xs"
										variant="outline"
										borderColor="gray.300"
										color="gray.600"
										fontSize="xs"
										disabled={ disabled }
									>
										{ copied
											? __( 'Copied!', 'anti-spam' )
											: __( 'Copy', 'anti-spam' ) }
									</Button>
								</Flex>
							</Box>
						</Box>

						<Box>
							<Flex align="baseline" gap={ 2 } mb={ 3 }>
								<Text
									fontSize="sm"
									fontWeight="bold"
									color="purple.600"
								>
									3.
								</Text>
								<Text
									fontSize="sm"
									fontWeight="600"
									color="gray.800"
								>
									{ __(
										'Enter the 6-digit code from your app',
										'anti-spam'
									) }
								</Text>
							</Flex>
							<Flex
								gap={ 3 }
								pl={ 5 }
								direction={ { base: 'column', md: 'row' } }
								align={ { base: 'stretch', md: 'center' } }
							>
								<Input
									value={ code }
									onChange={ handleCodeChange }
									placeholder="000000"
									maxLength={ 6 }
									w={ { base: '100%', md: '140px' } }
									fontFamily="mono"
									fontSize="lg"
									textAlign="center"
									letterSpacing="widest"
									borderColor="gray.300"
									disabled={ disabled }
								/>
								<Button
									onClick={ handleVerify }
									bg="purple.600"
									color="white"
									px={ 5 }
									fontSize="sm"
									fontWeight="600"
									_hover={ {
										bg: 'purple.700',
									} }
									disabled={
										isVerifying ||
										code.length < 6 ||
										disabled
									}
									w={ { base: '100%', md: 'auto' } }
								>
									{ isVerifying && (
										<Spinner size="sm" mr={ 2 } />
									) }
									{ isVerifying
										? __( 'Verifying...', 'anti-spam' )
										: __(
												'Verify & Activate',
												'anti-spam'
										  ) }
								</Button>
							</Flex>
						</Box>
					</Box>
				</Flex>
			</Box>
		</Box>
	);
}

export default SetupCard;
