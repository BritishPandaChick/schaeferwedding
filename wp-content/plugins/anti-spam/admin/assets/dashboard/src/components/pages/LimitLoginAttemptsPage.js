import { __ } from '@wordpress/i18n';
import { useState, useContext, useEffect } from '@wordpress/element';
import { Box, VStack, Text } from '@chakra-ui/react';
import {
	SettingToggle,
	TextboxControl,
	TextareaControl,
} from '../common/SettingsControls';
import PageHeader from '../common/PageHeader';
import { AppContext } from '../../provider';
import { saveSettings } from '../../services/api';
import { toaster } from '../Toaster';

/**
 * LimitLoginAttemptsPage Component
 * Configuration for limiting login attempts and brute force protection
 */
function LimitLoginAttemptsPage() {
	const { settings, setSettings, isLoadingSettings } =
		useContext( AppContext );

	// Lockout settings
	const [ bruteforceEnabled, setBruteforceEnabled ] = useState(
		Boolean( settings.bruteforce_enabled )
	);
	const [ bruteforceGdpr, setBruteforceGdpr ] = useState(
		Boolean( settings.bruteforce_gdpr )
	);
	const [ allowedRetries, setAllowedRetries ] = useState(
		settings.bruteforce_allowed_retries ?? 4
	);
	const [ minutesLockout, setMinutesLockout ] = useState(
		Math.round( ( settings.bruteforce_minutes_lockout ?? 1200 ) / 60 )
	);
	const [ validDuration, setValidDuration ] = useState(
		Math.round( ( settings.bruteforce_valid_duration ?? 43200 ) / 3600 )
	);

	// Whitelist settings - convert arrays to strings
	const [ whitelistIps, setWhitelistIps ] = useState(
		Array.isArray( settings.bruteforce_whitelist_ips )
			? settings.bruteforce_whitelist_ips.join( '\n' )
			: ''
	);
	const [ whitelistUsernames, setWhitelistUsernames ] = useState(
		Array.isArray( settings.bruteforce_whitelist_usernames )
			? settings.bruteforce_whitelist_usernames.join( '\n' )
			: ''
	);

	// Blacklist settings - convert arrays to strings
	const [ blacklistIps, setBlacklistIps ] = useState(
		Array.isArray( settings.bruteforce_blacklist_ips )
			? settings.bruteforce_blacklist_ips.join( '\n' )
			: ''
	);
	const [ blacklistUsernames, setBlacklistUsernames ] = useState(
		Array.isArray( settings.bruteforce_blacklist_usernames )
			? settings.bruteforce_blacklist_usernames.join( '\n' )
			: ''
	);

	const [ isSaving, setIsSaving ] = useState( false );

	// Update local state when settings change
	useEffect( () => {
		if ( ! isLoadingSettings ) {
			setBruteforceEnabled( Boolean( settings.bruteforce_enabled ) );
			setBruteforceGdpr( Boolean( settings.bruteforce_gdpr ) );
			setAllowedRetries( settings.bruteforce_allowed_retries ?? 4 );
			setMinutesLockout(
				Math.round(
					( settings.bruteforce_minutes_lockout ?? 1200 ) / 60
				)
			);
			setValidDuration(
				Math.round(
					( settings.bruteforce_valid_duration ?? 43200 ) / 3600
				)
			);
			setWhitelistIps(
				Array.isArray( settings.bruteforce_whitelist_ips )
					? settings.bruteforce_whitelist_ips.join( '\n' )
					: ''
			);
			setWhitelistUsernames(
				Array.isArray( settings.bruteforce_whitelist_usernames )
					? settings.bruteforce_whitelist_usernames.join( '\n' )
					: ''
			);
			setBlacklistIps(
				Array.isArray( settings.bruteforce_blacklist_ips )
					? settings.bruteforce_blacklist_ips.join( '\n' )
					: ''
			);
			setBlacklistUsernames(
				Array.isArray( settings.bruteforce_blacklist_usernames )
					? settings.bruteforce_blacklist_usernames.join( '\n' )
					: ''
			);
		}
	}, [ settings, isLoadingSettings ] );

	const handleSave = async () => {
		setIsSaving( true );
		try {
			// Helper to convert textarea string to array
			const textareaToArray = ( text ) => {
				if ( ! text || text.trim() === '' ) {
					return [];
				}
				return text
					.split( '\n' )
					.map( ( line ) => line.trim() )
					.filter( ( line ) => line.length > 0 );
			};

			const data = {
				bruteforce_enabled: bruteforceEnabled,
				bruteforce_gdpr: bruteforceGdpr,
				bruteforce_allowed_retries: parseInt( allowedRetries, 10 ) || 4,
				bruteforce_minutes_lockout:
					( parseInt( minutesLockout, 10 ) || 20 ) * 60,
				bruteforce_valid_duration:
					( parseInt( validDuration, 10 ) || 12 ) * 3600,
				bruteforce_whitelist_ips: textareaToArray( whitelistIps ),
				bruteforce_whitelist_usernames:
					textareaToArray( whitelistUsernames ),
				bruteforce_blacklist_ips: textareaToArray( blacklistIps ),
				bruteforce_blacklist_usernames:
					textareaToArray( blacklistUsernames ),
			};

			const response = await saveSettings( data );

			if ( response.success ) {
				setSettings( { ...settings, ...data } );
				toaster.success( {
					title: __( 'Settings saved', 'anti-spam' ),
					description:
						response.message ||
						__(
							'Your limit login attempts settings have been saved successfully.',
							'anti-spam'
						),
				} );
			}
		} catch ( error ) {
			toaster.error( {
				title: __( 'Error saving settings', 'anti-spam' ),
				description:
					error.message ||
					__(
						'Failed to save settings. Please try again.',
						'anti-spam'
					),
			} );
		} finally {
			setIsSaving( false );
		}
	};

	return (
		<VStack gap={ 6 } align="stretch">
			<PageHeader
				title={ __( 'Limit Login Attempts', 'anti-spam' ) }
				description={ __(
					'Protect your site from brute force attacks by limiting login attempts.',
					'anti-spam'
				) }
				onSave={ handleSave }
				isSaving={ isSaving }
				isDisabled={ isLoadingSettings }
			/>

			{ /* Master Toggle */ }
			<Box
				bg="white"
				borderRadius="lg"
				borderWidth="1px"
				borderColor="gray.200"
				overflow="hidden"
			>
				<Box px={ 6 }>
					<SettingToggle
						label={ __(
							'Enable Brute Force Protection',
							'anti-spam'
						) }
						description={ __(
							'Click to enable or disable protection against brute force attacks.',
							'anti-spam'
						) }
						enabled={ bruteforceEnabled }
						onChange={ setBruteforceEnabled }
					/>
				</Box>
			</Box>

			{ bruteforceEnabled && (
				<>
					{ /* Lockout Settings Section */ }
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
							<Text
								fontSize="lg"
								fontWeight="semibold"
								color="gray.900"
							>
								{ __( 'Lockout Settings', 'anti-spam' ) }
							</Text>
							<Text fontSize="sm" color="gray.600" mt={ 1 }>
								{ __(
									'Configure how failed login attempts are handled.',
									'anti-spam'
								) }
							</Text>
						</Box>
						<Box px={ 6 }>
							<SettingToggle
								label={ __( 'GDPR Compliance', 'anti-spam' ) }
								description={ __(
									'This makes the plugin GDPR compliant by not storing IP addresses.',
									'anti-spam'
								) }
								enabled={ bruteforceGdpr }
								onChange={ setBruteforceGdpr }
							/>
							<Box borderTopWidth="1px" borderColor="gray.100" />
							<TextboxControl
								label={ __( 'Allowed Retries', 'anti-spam' ) }
								description={ __(
									'Number of failed login attempts before lockout.',
									'anti-spam'
								) }
								type="number"
								value={ allowedRetries }
								onChange={ setAllowedRetries }
								min={ 1 }
							/>
							<Box borderTopWidth="1px" borderColor="gray.100" />
							<TextboxControl
								label={ __( 'Minutes Lockout', 'anti-spam' ) }
								description={ __(
									'How many minutes to lock out users after exceeding allowed retries.',
									'anti-spam'
								) }
								type="number"
								value={ minutesLockout }
								onChange={ setMinutesLockout }
								min={ 1 }
							/>
							<Box borderTopWidth="1px" borderColor="gray.100" />
							<Box>
								<TextboxControl
									label={ __(
										'Hours Until Retries Are Reset',
										'anti-spam'
									) }
									description={ __(
										'Number of hours before failed login attempts are reset.',
										'anti-spam'
									) }
									type="number"
									value={ validDuration }
									onChange={ setValidDuration }
									min={ 1 }
								/>
							</Box>
						</Box>
					</Box>

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
							<Text
								fontSize="lg"
								fontWeight="semibold"
								color="gray.900"
							>
								{ __( 'Whitelist', 'anti-spam' ) }
							</Text>
							<Text fontSize="sm" color="gray.600" mt={ 1 }>
								{ __(
									'IP addresses and usernames in the whitelist will never be locked out.',
									'anti-spam'
								) }
							</Text>
						</Box>
						<Box px={ 6 }>
							<TextareaControl
								label={ __(
									'Whitelist IP Addresses',
									'anti-spam'
								) }
								description={ __(
									'One IP or IP range (1.2.3.4-5.6.7.8) per line.',
									'anti-spam'
								) }
								value={ whitelistIps }
								onChange={ setWhitelistIps }
								placeholder={ __(
									'192.168.1.1\n10.0.0.1-10.0.0.255',
									'anti-spam'
								) }
								rows={ 4 }
							/>
							<Box borderTopWidth="1px" borderColor="gray.100" />
							<Box>
								<TextareaControl
									label={ __(
										'Whitelist Usernames',
										'anti-spam'
									) }
									description={ __(
										'One username per line.',
										'anti-spam'
									) }
									value={ whitelistUsernames }
									onChange={ setWhitelistUsernames }
									placeholder={ __(
										'admin\nusername',
										'anti-spam'
									) }
									rows={ 4 }
								/>
							</Box>
						</Box>
					</Box>

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
							<Text
								fontSize="lg"
								fontWeight="semibold"
								color="gray.900"
							>
								{ __( 'Blacklist', 'anti-spam' ) }
							</Text>
							<Text fontSize="sm" color="gray.600" mt={ 1 }>
								{ __(
									'IP addresses and usernames in the blacklist will always be blocked from logging in.',
									'anti-spam'
								) }
							</Text>
						</Box>
						<Box px={ 6 }>
							<TextareaControl
								label={ __(
									'Blacklist IP Addresses',
									'anti-spam'
								) }
								description={ __(
									'One IP or IP range (1.2.3.4-5.6.7.8) per line.',
									'anti-spam'
								) }
								value={ blacklistIps }
								onChange={ setBlacklistIps }
								placeholder={ __(
									'123.45.67.89\n98.76.54.0-98.76.54.255',
									'anti-spam'
								) }
								rows={ 4 }
							/>
							<Box borderTopWidth="1px" borderColor="gray.100" />
							<Box>
								<TextareaControl
									label={ __(
										'Blacklist Usernames',
										'anti-spam'
									) }
									description={ __(
										'One username per line.',
										'anti-spam'
									) }
									value={ blacklistUsernames }
									onChange={ setBlacklistUsernames }
									placeholder={ __(
										'baduser\nspammer',
										'anti-spam'
									) }
									rows={ 4 }
								/>
							</Box>
						</Box>
					</Box>
				</>
			) }
		</VStack>
	);
}

export default LimitLoginAttemptsPage;
