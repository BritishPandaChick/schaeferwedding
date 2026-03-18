import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { Box, VStack, Text, Button, Table, Spinner } from '@chakra-ui/react';
import PageHeader from '../common/PageHeader';
import { getBruteforceLog, unlockBruteforce } from '../../services/api';
import { toaster } from '../Toaster';

/**
 * LoginAttemptsLogPage Component
 * Display brute force login attempts log
 */
function LoginAttemptsLogPage() {
	const [ log, setLog ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ unlockingIps, setUnlockingIps ] = useState( new Set() );

	const fetchLog = async () => {
		setIsLoading( true );
		try {
			const response = await getBruteforceLog();
			if ( response.success ) {
				setLog( response.log || [] );
			}
		} catch ( error ) {
			toaster.error( {
				title: __( 'Error loading log', 'anti-spam' ),
				description:
					error.message ||
					__( 'Failed to load login attempts log.', 'anti-spam' ),
			} );
		} finally {
			setIsLoading( false );
		}
	};

	useEffect( () => {
		fetchLog();
	}, [] );

	const handleUnlock = async ( ip, username ) => {
		const key = `${ ip }-${ username }`;
		setUnlockingIps( ( prev ) => new Set( prev ).add( key ) );

		try {
			const response = await unlockBruteforce( ip, username );
			if ( response.success ) {
				toaster.success( {
					title: __( 'IP unlocked', 'anti-spam' ),
					description:
						response.message ||
						__( 'IP has been unlocked successfully.', 'anti-spam' ),
				} );
				// Refresh the log
				await fetchLog();
			}
		} catch ( error ) {
			toaster.error( {
				title: __( 'Error unlocking IP', 'anti-spam' ),
				description:
					error.message ||
					__( 'Failed to unlock IP. Please try again.', 'anti-spam' ),
			} );
		} finally {
			setUnlockingIps( ( prev ) => {
				const newSet = new Set( prev );
				newSet.delete( key );
				return newSet;
			} );
		}
	};

	return (
		<VStack gap={ 6 } align="stretch">
			<PageHeader
				title={ __( 'Login Attempts Log', 'anti-spam' ) }
				description={ __(
					'In this table, you can see the login attempts that the brute force protection module has tracked.',
					'anti-spam'
				) }
			/>

			<Box
				bg="white"
				borderRadius="lg"
				borderWidth="1px"
				borderColor="gray.200"
			>
				{ isLoading ? (
					<Box textAlign="center" py={ 12 }>
						<Spinner size="xl" color="indigo.500" />
						<Text mt={ 4 } color="gray.600">
							{ __( 'Loading login attempts...', 'anti-spam' ) }
						</Text>
					</Box>
				) : log.length === 0 ? (
					<Box textAlign="center" py={ 12 }>
						<Text fontSize="lg" color="gray.600">
							{ __(
								'No login attempts logged yet.',
								'anti-spam'
							) }
						</Text>
						<Text mt={ 2 } fontSize="sm" color="gray.500">
							{ __(
								'Failed login attempts will appear here once brute force protection is enabled.',
								'anti-spam'
							) }
						</Text>
					</Box>
				) : (
					<Box overflowX="auto">
						<Table.Root variant="line" size="sm" minW="600px">
							<Table.Header>
								<Table.Row bg="gray.50">
									<Table.ColumnHeader fontWeight="600">
										{ __( 'Date', 'anti-spam' ) }
									</Table.ColumnHeader>
									<Table.ColumnHeader fontWeight="600">
										{ __( 'IP', 'anti-spam' ) }
									</Table.ColumnHeader>
									<Table.ColumnHeader fontWeight="600">
										{ __(
											'Tried to log in as',
											'anti-spam'
										) }
									</Table.ColumnHeader>
									<Table.ColumnHeader fontWeight="600">
										{ __( 'Gateway', 'anti-spam' ) }
									</Table.ColumnHeader>
									<Table.ColumnHeader fontWeight="600">
										{ __( 'Action', 'anti-spam' ) }
									</Table.ColumnHeader>
								</Table.Row>
							</Table.Header>
							<Table.Body>
								{ log.map( ( entry ) => {
									const unlockKey = `${ entry.ip }-${ entry.username }`;
									const isUnlocking =
										unlockingIps.has( unlockKey );
									const rowKey = `${ entry.date_formatted }-${ entry.ip }-${ entry.username }`;

									return (
										<Table.Row key={ rowKey }>
											<Table.Cell>
												{ entry.date_formatted }
											</Table.Cell>
											<Table.Cell
												fontFamily="mono"
												fontSize="sm"
											>
												{ entry.ip }
											</Table.Cell>
											<Table.Cell>
												<Text fontWeight="500">
													{ entry.username }
												</Text>
												<Text
													fontSize="xs"
													color="gray.600"
												>
													{ entry.counter }{ ' ' }
													{ entry.counter === 1
														? __(
																'lockout',
																'anti-spam'
														  )
														: __(
																'lockouts',
																'anti-spam'
														  ) }
												</Text>
											</Table.Cell>
											<Table.Cell>
												{ entry.gateway }
											</Table.Cell>
											<Table.Cell>
												{ entry.is_locked ? (
													<Button
														size="sm"
														colorPalette="red"
														onClick={ () =>
															handleUnlock(
																entry.ip,
																entry.username
															)
														}
														loading={ isUnlocking }
														disabled={ isUnlocking }
													>
														{ __(
															'Unlock',
															'anti-spam'
														) }
													</Button>
												) : entry.unlocked ? (
													<Text
														fontSize="sm"
														color="gray.500"
														fontStyle="italic"
													>
														{ __(
															'Unlocked',
															'anti-spam'
														) }
													</Text>
												) : (
													<Text
														fontSize="sm"
														color="green.600"
													>
														{ __(
															'Expired',
															'anti-spam'
														) }
													</Text>
												) }
											</Table.Cell>
										</Table.Row>
									);
								} ) }
							</Table.Body>
						</Table.Root>
					</Box>
				) }
			</Box>
		</VStack>
	);
}

export default LoginAttemptsLogPage;
