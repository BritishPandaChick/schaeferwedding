import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { Box, Flex, Text, Button, Input, Spinner } from '@chakra-ui/react';
import { toaster } from '../Toaster';
import { SegmentedButton } from '../common/SettingsControls';
import {
	saveSettings,
	saveStorageConfig,
	deleteStorageConfig,
	getDropboxOAuthUrl,
} from '../../services/api';

const STORAGE_OPTIONS = [
	{ value: 'local', label: __( 'Local', 'anti-spam' ) },
	{ value: 'ftp', label: __( 'FTP', 'anti-spam' ) },
	{ value: 'dropbox', label: __( 'Dropbox', 'anti-spam' ) },
];

/**
 * BackupStorageCard Component
 * Storage type selector with conditional FTP form / Dropbox OAuth
 */
function BackupStorageCard( { settings, onSettingsChange, disabled = false } ) {
	const storeData = settings.backup_store_data || {};

	const [ ftpHost, setFtpHost ] = useState( storeData.ftp?.ftp_host || '' );
	const [ ftpPort, setFtpPort ] = useState( storeData.ftp?.ftp_port || '21' );
	const [ ftpUser, setFtpUser ] = useState( storeData.ftp?.ftp_user || '' );
	const [ ftpPassword, setFtpPassword ] = useState(
		storeData.ftp?.ftp_password || ''
	);
	const [ ftpPath, setFtpPath ] = useState( storeData.ftp?.ftp_path || '/' );
	useEffect( () => {
		const ftp = settings.backup_store_data?.ftp;
		if ( ftp ) {
			setFtpHost( ftp.ftp_host || '' );
			setFtpPort( ftp.ftp_port || '21' );
			setFtpUser( ftp.ftp_user || '' );
			setFtpPassword( ftp.ftp_password || '' );
			setFtpPath( ftp.ftp_path || '/' );
		}
	}, [ settings.backup_store_data ] );

	const [ isSaving, setIsSaving ] = useState( false );
	const [ isDeleting, setIsDeleting ] = useState( false );
	const [ isAuthorizing, setIsAuthorizing ] = useState( false );

	const currentStore = settings.backup_store || 'local';

	const handleStoreChange = async ( value ) => {
		if ( disabled ) return;
		try {
			await saveSettings( { backup_store: value } );
			onSettingsChange( { ...settings, backup_store: value } );
		} catch {
			toaster.create( {
				title: __( 'Error', 'anti-spam' ),
				description: __(
					'Failed to update storage type.',
					'anti-spam'
				),
				type: 'error',
			} );
		}
	};

	const handleSaveFtp = async () => {
		setIsSaving( true );
		try {
			await saveStorageConfig( 'ftp', {
				ftp_host: ftpHost,
				ftp_port: ftpPort,
				ftp_user: ftpUser,
				ftp_password: ftpPassword,
				ftp_path: ftpPath,
			} );
			toaster.create( {
				title: __( 'Success', 'anti-spam' ),
				description: __( 'FTP configuration saved.', 'anti-spam' ),
				type: 'success',
			} );
		} catch {
			toaster.create( {
				title: __( 'Error', 'anti-spam' ),
				description: __(
					'Failed to save FTP configuration.',
					'anti-spam'
				),
				type: 'error',
			} );
		} finally {
			setIsSaving( false );
		}
	};

	const handleDeleteFtp = async () => {
		if (
			! confirm(
				__(
					'Are you sure you want to remove the FTP configuration?',
					'anti-spam'
				)
			)
		) {
			return;
		}
		setIsDeleting( true );
		try {
			await deleteStorageConfig( 'ftp' );
			setFtpHost( '' );
			setFtpPort( '21' );
			setFtpUser( '' );
			setFtpPassword( '' );
			setFtpPath( '/' );
			toaster.create( {
				title: __( 'Success', 'anti-spam' ),
				description: __( 'FTP configuration removed.', 'anti-spam' ),
				type: 'success',
			} );
		} catch {
			toaster.create( {
				title: __( 'Error', 'anti-spam' ),
				description: __(
					'Failed to remove FTP configuration.',
					'anti-spam'
				),
				type: 'error',
			} );
		} finally {
			setIsDeleting( false );
		}
	};

	const handleDropboxAuthorize = async () => {
		setIsAuthorizing( true );
		try {
			const response = await getDropboxOAuthUrl( 'dropbox' );
			if ( response.url ) {
				window.open( response.url, '_blank' );
			}
		} catch {
			toaster.create( {
				title: __( 'Error', 'anti-spam' ),
				description: __(
					'Failed to get authorization URL.',
					'anti-spam'
				),
				type: 'error',
			} );
		} finally {
			setIsAuthorizing( false );
		}
	};

	const handleDropboxDisconnect = async () => {
		if (
			! confirm(
				__(
					'Are you sure you want to disconnect Dropbox?',
					'anti-spam'
				)
			)
		) {
			return;
		}
		try {
			await deleteStorageConfig( 'dropbox' );
			toaster.create( {
				title: __( 'Success', 'anti-spam' ),
				description: __( 'Dropbox disconnected.', 'anti-spam' ),
				type: 'success',
			} );
		} catch {
			toaster.create( {
				title: __( 'Error', 'anti-spam' ),
				description: __( 'Failed to disconnect Dropbox.', 'anti-spam' ),
				type: 'error',
			} );
		}
	};

	const isDropboxConnected = !! storeData.dropbox?.access_token;

	return (
		<Box
			bg="white"
			p={ 6 }
			borderRadius="md"
			borderWidth="1px"
			borderColor="gray.200"
		>
			<Flex
				align={ { base: 'flex-start', sm: 'center' } }
				justify="space-between"
				direction={ { base: 'column', sm: 'row' } }
				gap={ { base: 3, sm: 0 } }
				mb={ 5 }
			>
				<Text fontSize="lg" fontWeight="semibold" color="gray.900">
					{ __( 'Backup Storage', 'anti-spam' ) }
				</Text>
				<SegmentedButton
					options={ STORAGE_OPTIONS }
					value={ currentStore }
					onChange={ handleStoreChange }
					disabled={ disabled }
				/>
			</Flex>

			{ currentStore === 'local' && (
				<Text fontSize="sm" color="gray.600">
					{ __(
						'Backups will be stored on your server in the uploads directory.',
						'anti-spam'
					) }
				</Text>
			) }

			{ currentStore === 'ftp' && (
				<Box>
					<Flex
						gap={ 4 }
						mb={ 3 }
						direction={ { base: 'column', sm: 'row' } }
					>
						<Box flex="1">
							<Text
								fontSize="sm"
								fontWeight="500"
								color="gray.700"
								mb={ 1 }
							>
								{ __( 'Host', 'anti-spam' ) }
							</Text>
							<Input
								value={ ftpHost }
								onChange={ ( e ) =>
									setFtpHost( e.target.value )
								}
								placeholder="ftp.example.com"
								size="sm"
								borderRadius="md"
								disabled={ disabled }
							/>
						</Box>
						<Box w={ { base: '100%', sm: '100px' } }>
							<Text
								fontSize="sm"
								fontWeight="500"
								color="gray.700"
								mb={ 1 }
							>
								{ __( 'Port', 'anti-spam' ) }
							</Text>
							<Input
								value={ ftpPort }
								onChange={ ( e ) =>
									setFtpPort( e.target.value )
								}
								placeholder="21"
								size="sm"
								borderRadius="md"
								disabled={ disabled }
							/>
						</Box>
					</Flex>
					<Flex
						gap={ 4 }
						mb={ 3 }
						direction={ { base: 'column', sm: 'row' } }
					>
						<Box flex="1">
							<Text
								fontSize="sm"
								fontWeight="500"
								color="gray.700"
								mb={ 1 }
							>
								{ __( 'Username', 'anti-spam' ) }
							</Text>
							<Input
								value={ ftpUser }
								onChange={ ( e ) =>
									setFtpUser( e.target.value )
								}
								size="sm"
								borderRadius="md"
								disabled={ disabled }
							/>
						</Box>
						<Box flex="1">
							<Text
								fontSize="sm"
								fontWeight="500"
								color="gray.700"
								mb={ 1 }
							>
								{ __( 'Password', 'anti-spam' ) }
							</Text>
							<Input
								type="password"
								value={ ftpPassword }
								onChange={ ( e ) =>
									setFtpPassword( e.target.value )
								}
								size="sm"
								borderRadius="md"
								disabled={ disabled }
							/>
						</Box>
					</Flex>
					<Box mb={ 4 }>
						<Text
							fontSize="sm"
							fontWeight="500"
							color="gray.700"
							mb={ 1 }
						>
							{ __( 'Remote Path', 'anti-spam' ) }
						</Text>
						<Input
							value={ ftpPath }
							onChange={ ( e ) => setFtpPath( e.target.value ) }
							placeholder="/"
							size="sm"
							borderRadius="md"
							disabled={ disabled }
						/>
					</Box>
					<Flex gap={ 2 }>
						<Button
							size="sm"
							colorScheme="purple"
							onClick={ handleSaveFtp }
							disabled={ disabled || isSaving }
						>
							{ isSaving && <Spinner size="sm" mr={ 2 } /> }
							{ __( 'Save', 'anti-spam' ) }
						</Button>
						<Button
							size="sm"
							variant="outline"
							onClick={ handleDeleteFtp }
							disabled={ disabled || isDeleting }
						>
							{ __( 'Delete', 'anti-spam' ) }
						</Button>
					</Flex>
				</Box>
			) }

			{ currentStore === 'dropbox' && (
				<Box>
					{ isDropboxConnected ? (
						<Flex align="center" gap={ 3 }>
							<Box
								w={ 2 }
								h={ 2 }
								borderRadius="full"
								bg="green.400"
							/>
							<Text fontSize="sm" color="gray.700">
								{ __( 'Dropbox connected', 'anti-spam' ) }
							</Text>
							<Button
								size="sm"
								variant="outline"
								onClick={ handleDropboxDisconnect }
								disabled={ disabled }
							>
								{ __( 'Disconnect', 'anti-spam' ) }
							</Button>
						</Flex>
					) : (
						<Button
							size="sm"
							colorScheme="purple"
							onClick={ handleDropboxAuthorize }
							disabled={ disabled || isAuthorizing }
						>
							{ isAuthorizing && <Spinner size="sm" mr={ 2 } /> }
							{ __( 'Authorize Dropbox', 'anti-spam' ) }
						</Button>
					) }
				</Box>
			) }
		</Box>
	);
}

export default BackupStorageCard;
