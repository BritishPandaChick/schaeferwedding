import { __ } from '@wordpress/i18n';
import {
	useState,
	useEffect,
	useCallback,
	useContext,
	useRef,
} from '@wordpress/element';
import { Box, Flex, VStack, Text, Heading, Button } from '@chakra-ui/react';
import { AppContext } from '../../provider';
import { toaster } from '../Toaster';
import {
	getBackupList,
	getBackupProgress,
	saveSettings,
} from '../../services/api';
import PageHeader from '../common/PageHeader';
import BackupSettingsCard from '../backup/BackupSettingsCard';
import BackupStorageCard from '../backup/BackupStorageCard';
import BackupListCard from '../backup/BackupListCard';

/**
 * BackupPage Component
 * Main backup page: settings/list loading, progress polling
 * Free users see all controls disabled with an upsell banner
 */
function BackupPage() {
	const { settings, setSettings, isLoadingSettings, isLicenseActive } =
		useContext( AppContext );
	const isProActive = isLicenseActive;

	// Local state for backup settings (batched save via "Save Changes")
	const [ backupSettings, setBackupSettings ] = useState( {
		...settings,
	} );
	const backupSettingsRef = useRef( { ...settings } );

	const [ backups, setBackups ] = useState( [] );
	const [ backupStatus, setBackupStatus ] = useState(
		settings.backup_status || 'stopped'
	);
	const [ backupProgress, setBackupProgress ] = useState( 0 );
	const [ progressInfo, setProgressInfo ] = useState( {} );
	const [ isSaving, setIsSaving ] = useState( false );

	// Sync local backup settings when global settings load/change
	useEffect( () => {
		if ( ! isLoadingSettings ) {
			const nextSettings = { ...settings };
			backupSettingsRef.current = nextSettings;
			setBackupSettings( nextSettings );
			setBackupStatus( settings.backup_status || 'stopped' );
		}
	}, [ settings, isLoadingSettings ] );

	const handleBackupSettingsChange = useCallback( ( nextSettings ) => {
		backupSettingsRef.current = nextSettings;
		setBackupSettings( nextSettings );
	}, [] );

	const loadBackupList = useCallback( async () => {
		try {
			const list = await getBackupList();
			setBackups( list );
		} catch {
			// Silently fail
		}
	}, [] );

	// Load backup list
	useEffect( () => {
		if ( isProActive ) {
			loadBackupList();
		}
	}, [ isProActive, loadBackupList ] );

	// Progress polling with exponential backoff on errors
	useEffect( () => {
		if ( ! isProActive || backupStatus !== 'started' ) {
			return;
		}

		const MAX_CONSECUTIVE_ERRORS = 5;
		const BASE_INTERVAL = 3000;
		let errorCount = 0;
		let timeoutId = null;

		const poll = async () => {
			try {
				const res = await getBackupProgress();
				errorCount = 0;
				setBackupProgress( res.progress || 0 );
				setProgressInfo( res.info || {} );
				if ( res.state === 'stopped' ) {
					setBackupStatus( 'stopped' );
					setBackupProgress( 0 );
					loadBackupList();
					return;
				}
			} catch {
				errorCount++;
				if ( errorCount >= MAX_CONSECUTIVE_ERRORS ) {
					toaster.create( {
						title: __( 'Connection lost', 'anti-spam' ),
						description: __(
							'Unable to retrieve backup progress. Please check your connection and refresh.',
							'anti-spam'
						),
						type: 'error',
					} );
					return;
				}
			}
			const delay = BASE_INTERVAL * Math.pow( 2, errorCount );
			timeoutId = setTimeout( poll, delay );
		};

		timeoutId = setTimeout( poll, BASE_INTERVAL );
		return () => {
			if ( timeoutId ) {
				clearTimeout( timeoutId );
			}
		};
	}, [ isProActive, backupStatus, loadBackupList ] );

	const handleSave = async () => {
		setIsSaving( true );
		try {
			const currentBackupSettings = backupSettingsRef.current;
			const data = {
				schedule_backup: currentBackupSettings.schedule_backup,
				remove_old_data: currentBackupSettings.remove_old_data,
				backup_files_per_iteration:
					currentBackupSettings.backup_files_per_iteration,
			};

			const response = await saveSettings( data );

			if ( response.success ) {
				setSettings( { ...settings, ...data } );
				toaster.create( {
					title: __( 'Settings saved', 'anti-spam' ),
					description:
						response.message ||
						__(
							'Your backup settings have been saved successfully.',
							'anti-spam'
						),
					type: 'success',
				} );
			}
		} catch ( error ) {
			toaster.create( {
				title: __( 'Error saving settings', 'anti-spam' ),
				description:
					error.message ||
					__(
						'Failed to save settings. Please try again.',
						'anti-spam'
					),
				type: 'error',
			} );
		} finally {
			setIsSaving( false );
		}
	};

	const handleStatusChange = ( status ) => {
		setBackupStatus( status );
		if ( status === 'started' ) {
			setBackupProgress( 0 );
			setProgressInfo( {} );
		}
	};

	if ( ! isProActive ) {
		return (
			<Box>
				<PageHeader
					title={ __( 'Backup', 'anti-spam' ) }
					description={ __(
						'Create and manage backups of your WordPress site.',
						'anti-spam'
					) }
				/>

				{ /* Upsell Banner */ }
				<Box
					bg="purple.50"
					borderRadius="lg"
					borderWidth="1px"
					borderColor="purple.200"
					p={ 6 }
					mb={ 6 }
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
								{ __( 'Unlock Backup Features', 'anti-spam' ) }
							</Heading>
							<Text
								fontSize="sm"
								color="gray.600"
								lineHeight="tall"
							>
								{ __(
									'Upgrade to Pro to create backups, schedule automatic backups, and store them via FTP or Dropbox.',
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
								{ __( 'Upgrade to Pro', 'anti-spam' ) }
							</a>
						</Button>
					</Flex>
				</Box>

				{ /* Disabled Preview */ }
				<Box pointerEvents="none" opacity={ 0.5 }>
					<VStack gap={ 6 } align="stretch">
						<BackupSettingsCard
							settings={ backupSettings }
							onSettingsChange={ handleBackupSettingsChange }
							disabled
							showProBadge={ false }
						/>

						<BackupStorageCard
							settings={ settings }
							onSettingsChange={ setSettings }
							disabled
						/>

						<BackupListCard
							backups={ backups }
							onRefresh={ loadBackupList }
							backupStatus={ backupStatus }
							backupProgress={ backupProgress }
							progressInfo={ progressInfo }
							onStatusChange={ handleStatusChange }
							disabled
						/>
					</VStack>
				</Box>
			</Box>
		);
	}

	return (
		<Box>
			<PageHeader
				title={ __( 'Backup', 'anti-spam' ) }
				description={ __(
					'Create and manage backups of your WordPress site.',
					'anti-spam'
				) }
				onSave={ handleSave }
				isSaving={ isSaving }
				isDisabled={ isLoadingSettings }
			/>

			<VStack gap={ 6 } align="stretch">
				<BackupSettingsCard
					settings={ backupSettings }
					onSettingsChange={ handleBackupSettingsChange }
				/>

				<BackupStorageCard
					settings={ settings }
					onSettingsChange={ setSettings }
				/>

				<BackupListCard
					backups={ backups }
					onRefresh={ loadBackupList }
					backupStatus={ backupStatus }
					backupProgress={ backupProgress }
					progressInfo={ progressInfo }
					onStatusChange={ handleStatusChange }
				/>
			</VStack>
		</Box>
	);
}

export default BackupPage;
