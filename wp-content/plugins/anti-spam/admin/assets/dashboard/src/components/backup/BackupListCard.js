import { __, sprintf } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Box, Flex, Text, Button, Spinner } from '@chakra-ui/react';
import { toaster } from '../Toaster';
import {
	startBackup,
	abortBackup,
	deleteBackup,
	getBackupDownloadUrl,
} from '../../services/api';

/**
 * BackupListCard Component
 * Create backup action + progress bar + table of existing backups
 */
function BackupListCard( {
	backups,
	onRefresh,
	backupStatus,
	backupProgress,
	progressInfo,
	onStatusChange,
	disabled = false,
} ) {
	const [ isStarting, setIsStarting ] = useState( false );
	const [ isAborting, setIsAborting ] = useState( false );
	const [ loadingAction, setLoadingAction ] = useState( null );

	const isRunning = backupStatus === 'started';

	const handleStart = async () => {
		setIsStarting( true );
		try {
			await startBackup();
			onStatusChange( 'started' );
			toaster.create( {
				title: __( 'Success', 'anti-spam' ),
				description: __( 'Backup started.', 'anti-spam' ),
				type: 'success',
			} );
		} catch {
			toaster.create( {
				title: __( 'Error', 'anti-spam' ),
				description: __( 'Failed to start backup.', 'anti-spam' ),
				type: 'error',
			} );
		} finally {
			setIsStarting( false );
		}
	};

	const handleAbort = async () => {
		if (
			! confirm(
				__( 'Are you sure you want to abort the backup?', 'anti-spam' )
			)
		) {
			return;
		}
		setIsAborting( true );
		try {
			await abortBackup();
			onStatusChange( 'stopped' );
			toaster.create( {
				title: __( 'Success', 'anti-spam' ),
				description: __( 'Backup aborted.', 'anti-spam' ),
				type: 'success',
			} );
		} catch {
			toaster.create( {
				title: __( 'Error', 'anti-spam' ),
				description: __( 'Failed to abort backup.', 'anti-spam' ),
				type: 'error',
			} );
		} finally {
			setIsAborting( false );
		}
	};

	const handleDownload = async ( date ) => {
		setLoadingAction( `download-${ date }` );
		try {
			const response = await getBackupDownloadUrl( date );
			if ( response.files && response.files.length > 1 ) {
				toaster.create( {
					title: __( 'Multi-part download', 'anti-spam' ),
					description: sprintf(
						/* translators: %d: number of backup parts */
						__(
							'This backup has %d parts. Each part will begin downloading shortly.',
							'anti-spam'
						),
						response.files.length
					),
					type: 'info',
				} );
				// Multi-part backup: trigger download for each part.
				response.files.forEach( ( file, index ) => {
					setTimeout( () => {
						const a = document.createElement( 'a' );
						a.href = file.url;
						a.download = file.name;
						a.style.display = 'none';
						document.body.appendChild( a );
						a.click();
						document.body.removeChild( a );
					}, index * 500 );
				} );
			} else if ( response.url ) {
				window.location.href = response.url;
			}
		} catch {
			toaster.create( {
				title: __( 'Error', 'anti-spam' ),
				description: __( 'Failed to get download URL.', 'anti-spam' ),
				type: 'error',
			} );
		} finally {
			setLoadingAction( null );
		}
	};

	const handleDelete = async ( date ) => {
		if (
			! confirm(
				__(
					'Are you sure you want to delete this backup? This action cannot be undone.',
					'anti-spam'
				)
			)
		) {
			return;
		}
		setLoadingAction( `delete-${ date }` );
		try {
			await deleteBackup( date );
			toaster.create( {
				title: __( 'Success', 'anti-spam' ),
				description: __( 'Backup deleted.', 'anti-spam' ),
				type: 'success',
			} );
			onRefresh();
		} catch {
			toaster.create( {
				title: __( 'Error', 'anti-spam' ),
				description: __( 'Failed to delete backup.', 'anti-spam' ),
				type: 'error',
			} );
		} finally {
			setLoadingAction( null );
		}
	};

	const getStorageBadge = ( storage ) => {
		const colors = {
			local: { bg: 'gray.100', color: 'gray.700' },
			ftp: { bg: 'blue.100', color: 'blue.700' },
			dropbox: { bg: 'purple.100', color: 'purple.700' },
		};
		const style = colors[ storage ] || colors.local;
		return (
			<Box
				as="span"
				bg={ style.bg }
				color={ style.color }
				fontSize="xs"
				fontWeight="600"
				px={ 2 }
				py={ 0.5 }
				borderRadius="full"
				textTransform="capitalize"
			>
				{ storage }
			</Box>
		);
	};

	return (
		<Box
			bg="white"
			p={ 6 }
			borderRadius="md"
			borderWidth="1px"
			borderColor="gray.200"
		>
			{ /* Header with title + create button */ }
			<Flex
				align={ { base: 'flex-start', sm: 'center' } }
				justify="space-between"
				direction={ { base: 'column', sm: 'row' } }
				gap={ { base: 3, sm: 0 } }
				mb={ 4 }
			>
				<Text fontSize="lg" fontWeight="semibold" color="gray.900">
					{ __( 'Backups', 'anti-spam' ) }
				</Text>
				{ ! isRunning && (
					<Button
						size="sm"
						colorScheme="purple"
						onClick={ handleStart }
						disabled={ disabled || isStarting }
					>
						{ isStarting && <Spinner size="sm" mr={ 2 } /> }
						{ __( 'Create New Backup', 'anti-spam' ) }
					</Button>
				) }
			</Flex>

			{ /* Progress bar when backup is running */ }
			{ isRunning && (
				<Box mb={ 5 } bg="gray.50" p={ 4 } borderRadius="md">
					<Flex justify="space-between" align="center" mb={ 2 }>
						<Text fontSize="sm" color="gray.700">
							{ __( 'Backup in progress...', 'anti-spam' ) }
						</Text>
						<Text fontSize="sm" fontWeight="600" color="gray.900">
							{ Math.round( backupProgress ) }%
						</Text>
					</Flex>
					<Box
						bg="gray.200"
						borderRadius="full"
						h="8px"
						overflow="hidden"
					>
						<Box
							bg="purple.500"
							h="100%"
							borderRadius="full"
							transition="width 0.5s"
							style={ {
								width: `${ backupProgress }%`,
							} }
						/>
					</Box>
					<Flex justify="space-between" align="center" mt={ 2 }>
						<Text fontSize="xs" color="gray.500">
							{ progressInfo &&
								progressInfo.last_modify &&
								`${ __( 'Last update:', 'anti-spam' ) } ${
									progressInfo.last_modify
								}` }
							{ progressInfo &&
								progressInfo.size &&
								` (${ progressInfo.size })` }
						</Text>
						<Button
							size="xs"
							variant="surface"
							colorPalette="red"
							onClick={ handleAbort }
							disabled={ isAborting }
						>
							{ isAborting && <Spinner size="xs" mr={ 1 } /> }
							{ __( 'Abort', 'anti-spam' ) }
						</Button>
					</Flex>
				</Box>
			) }

			{ /* Backup table */ }
			{ ! backups || backups.length === 0 ? (
				<Text
					fontSize="sm"
					color="gray.500"
					textAlign="center"
					py={ 4 }
				>
					{ __( 'No backups found.', 'anti-spam' ) }
				</Text>
			) : (
				<Box overflowX="auto">
					<table
						style={ {
							width: '100%',
							minWidth: '500px',
							borderCollapse: 'collapse',
							fontSize: '14px',
						} }
					>
						<thead>
							<tr
								style={ {
									borderBottom: '2px solid #e2e8f0',
								} }
							>
								<th
									style={ {
										textAlign: 'left',
										padding: '8px 12px',
										fontWeight: 600,
										color: '#4a5568',
										fontSize: '13px',
									} }
								>
									{ __( 'Date', 'anti-spam' ) }
								</th>
								<th
									style={ {
										textAlign: 'left',
										padding: '8px 12px',
										fontWeight: 600,
										color: '#4a5568',
										fontSize: '13px',
									} }
								>
									{ __( 'Size', 'anti-spam' ) }
								</th>
								<th
									style={ {
										textAlign: 'left',
										padding: '8px 12px',
										fontWeight: 600,
										color: '#4a5568',
										fontSize: '13px',
									} }
								>
									{ __( 'Storage', 'anti-spam' ) }
								</th>
								<th
									style={ {
										textAlign: 'right',
										padding: '8px 12px',
										fontWeight: 600,
										color: '#4a5568',
										fontSize: '13px',
									} }
								>
									{ __( 'Actions', 'anti-spam' ) }
								</th>
							</tr>
						</thead>
						<tbody>
							{ backups.map( ( backup ) => (
								<tr
									key={ backup.date }
									style={ {
										borderBottom: '1px solid #edf2f7',
									} }
								>
									<td
										style={ {
											padding: '10px 12px',
											color: '#2d3748',
										} }
									>
										{ backup.date }
									</td>
									<td
										style={ {
											padding: '10px 12px',
											color: '#4a5568',
										} }
									>
										{ backup.size || '\u2014' }
									</td>
									<td
										style={ {
											padding: '10px 12px',
										} }
									>
										{ getStorageBadge( backup.storage ) }
									</td>
									<td
										style={ {
											padding: '10px 12px',
											textAlign: 'right',
										} }
									>
										<Flex gap={ 2 } justify="flex-end">
											<Button
												size="xs"
												variant="outline"
												onClick={ () =>
													handleDownload(
														backup.date
													)
												}
												disabled={
													disabled ||
													loadingAction ===
														`download-${ backup.date }`
												}
											>
												{ loadingAction ===
													`download-${ backup.date }` && (
													<Spinner
														size="xs"
														mr={ 1 }
													/>
												) }
												{ __(
													'Download',
													'anti-spam'
												) }
											</Button>
											<Button
												size="xs"
												variant="outline"
												colorScheme="red"
												onClick={ () =>
													handleDelete( backup.date )
												}
												disabled={
													disabled ||
													loadingAction ===
														`delete-${ backup.date }`
												}
											>
												{ loadingAction ===
													`delete-${ backup.date }` && (
													<Spinner
														size="xs"
														mr={ 1 }
													/>
												) }
												{ __( 'Delete', 'anti-spam' ) }
											</Button>
										</Flex>
									</td>
								</tr>
							) ) }
						</tbody>
					</table>
				</Box>
			) }
		</Box>
	);
}

export default BackupListCard;
