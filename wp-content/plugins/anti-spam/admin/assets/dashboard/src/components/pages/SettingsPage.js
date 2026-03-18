import { __ } from '@wordpress/i18n';
import { useState, useContext, useEffect } from '@wordpress/element';
import { Box, VStack, Text, Button } from '@chakra-ui/react';
import { SettingToggle, TextareaControl } from '../common/SettingsControls';
import PageHeader from '../common/PageHeader';
import LicenseCard from '../common/LicenseCard';
import { AppContext } from '../../provider';
import { saveSettings } from '../../services/api';
import { toaster } from '../Toaster';

/**
 * SettingsPage Component
 * Global plugin settings and configuration
 */
function SettingsPage() {
	const { settings, setSettings, isLoadingSettings } =
		useContext( AppContext );

	const [ completeUninstall, setCompleteUninstall ] = useState(
		Boolean( settings.complete_uninstall )
	);

	const [ sendAnalytics, setSendAnalytics ] = useState(
		Boolean( settings.send_analytics )
	);

	// Settings excluded from import/export (site-specific or sensitive).
	const nonExportableKeys = [
		'backup_store',
		'backup_store_data',
		'backup_status',
		'privacy_policy_url',
		'has_privacy_policy_page',
	];

	// Import/Export
	const [ settingsJson, setSettingsJson ] = useState( '' );
	const [ isImporting, setIsImporting ] = useState( false );

	const [ isSaving, setIsSaving ] = useState( false );

	// Update local state when settings change
	useEffect( () => {
		if ( ! isLoadingSettings ) {
			setCompleteUninstall( Boolean( settings.complete_uninstall ) );
			setSendAnalytics( Boolean( settings.send_analytics ) );
		}
	}, [ settings, isLoadingSettings ] );

	// Export settings to JSON when settings change
	useEffect( () => {
		if ( ! isLoadingSettings && Object.keys( settings ).length > 0 ) {
			// Add titan_ prefix to all option names for export, excluding site-specific keys.
			const prefixedSettings = {};
			Object.keys( settings ).forEach( ( key ) => {
				if ( ! nonExportableKeys.includes( key ) ) {
					prefixedSettings[ `titan_${ key }` ] = settings[ key ];
				}
			} );
			setSettingsJson( JSON.stringify( prefixedSettings, null, 2 ) );
		}
	}, [ settings, isLoadingSettings ] );

	const handleImport = async () => {
		if ( ! settingsJson || settingsJson.trim() === '' ) {
			toaster.error( {
				title: __( 'Import failed', 'anti-spam' ),
				description: __(
					'Please paste settings JSON to import.',
					'anti-spam'
				),
			} );
			return;
		}

		setIsImporting( true );
		try {
			// Parse JSON
			const importedSettings = JSON.parse( settingsJson );

			// Validate that it's an object
			if (
				typeof importedSettings !== 'object' ||
				importedSettings === null
			) {
				throw new Error( __( 'Invalid settings format', 'anti-spam' ) );
			}

			// Remove titan_ prefix and filter out site-specific keys.
			const unprefixedSettings = {};
			Object.keys( importedSettings ).forEach( ( key ) => {
				const unprefixedKey = key.startsWith( 'titan_' )
					? key.substring( 6 )
					: key;
				if ( ! nonExportableKeys.includes( unprefixedKey ) ) {
					unprefixedSettings[ unprefixedKey ] =
						importedSettings[ key ];
				}
			} );

			// Save the imported settings
			const response = await saveSettings( unprefixedSettings );

			if ( response.success ) {
				setSettings( { ...settings, ...unprefixedSettings } );
				toaster.success( {
					title: __( 'Settings imported', 'anti-spam' ),
					description:
						response.message ||
						__(
							'Settings have been imported and saved successfully.',
							'anti-spam'
						),
				} );
			}
		} catch ( error ) {
			toaster.error( {
				title: __( 'Import failed', 'anti-spam' ),
				description:
					error.message ||
					__(
						'Invalid JSON format. Please check the settings and try again.',
						'anti-spam'
					),
			} );
		} finally {
			setIsImporting( false );
		}
	};

	const handleSave = async () => {
		setIsSaving( true );
		try {
			const data = {
				complete_uninstall: completeUninstall,
				send_analytics: sendAnalytics,
			};

			const response = await saveSettings( data );

			if ( response.success ) {
				setSettings( { ...settings, ...data } );
				toaster.success( {
					title: __( 'Settings saved', 'anti-spam' ),
					description:
						response.message ||
						__(
							'Your settings have been saved successfully.',
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
		<Box>
			<PageHeader
				title={ __( 'Settings', 'anti-spam' ) }
				description={ __(
					'Global plugin settings and configuration',
					'anti-spam'
				) }
				onSave={ handleSave }
				isSaving={ isSaving }
				isDisabled={ isLoadingSettings }
			/>

			<VStack gap={ 6 } align="stretch">
				{ /* License Section */ }
				<LicenseCard />

				{ /* Advanced Settings Section */ }
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
							{ __( 'Advanced settings', 'anti-spam' ) }
						</Text>
						<Text fontSize="sm" color="gray.600" mt={ 1 }>
							{ __(
								'This group of settings allows you to configure the work of the plugin.',
								'anti-spam'
							) }
						</Text>
					</Box>
					<Box px={ 6 }>
						<SettingToggle
							label={ __( 'Complete Uninstall', 'anti-spam' ) }
							description={ __(
								'When the plugin is deleted from the Plugins menu, also delete all plugin settings.',
								'anti-spam'
							) }
							enabled={ completeUninstall }
							onChange={ setCompleteUninstall }
						/>
						<Box borderTopWidth="1px" borderColor="gray.100" />
						<SettingToggle
							label={ __(
								'Send anonymous usage data',
								'anti-spam'
							) }
							description={ __(
								'Help improve the plugin by sharing anonymous usage statistics. No personal data is collected and data is never shared with third parties.',
								'anti-spam'
							) }
							enabled={ sendAnalytics }
							onChange={ setSendAnalytics }
						/>
					</Box>
				</Box>

				{ /* Import/Export Section */ }
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
							{ __( 'Import/Export', 'anti-spam' ) }
						</Text>
					</Box>
					<Box px={ 6 } py={ 5 }>
						<TextareaControl
							label={ __(
								'Import/Export settings',
								'anti-spam'
							) }
							description={ __(
								'Copy the content below to export your settings. Paste settings JSON from another site and click Import to restore.',
								'anti-spam'
							) }
							value={ settingsJson }
							onChange={ setSettingsJson }
							rows={ 10 }
							placeholder={ __(
								'Paste settings JSON here to import...',
								'anti-spam'
							) }
						/>
						<Text fontSize="sm" color="gray.600" mt={ 1 }>
							{ __(
								'Export your settings or import settings from another site.',
								'anti-spam'
							) }
						</Text>
						<Box display="flex" gap={ 3 } px={ 0 } pt={ 3 }>
							<Button
								onClick={ handleImport }
								px={ 4 }
								py={ 2 }
								bg="purple.600"
								color="white"
								borderRadius="md"
								fontWeight="medium"
								fontSize="sm"
								_hover={ { bg: 'purple.700' } }
								_active={ { bg: 'purple.800' } }
								transition="all 0.2s"
								disabled={ isImporting || isLoadingSettings }
								opacity={
									isImporting || isLoadingSettings ? 0.6 : 1
								}
							>
								{ isImporting
									? __( 'Importing...', 'anti-spam' )
									: __( 'Import options', 'anti-spam' ) }
							</Button>
						</Box>
					</Box>
				</Box>
			</VStack>
		</Box>
	);
}

export default SettingsPage;
