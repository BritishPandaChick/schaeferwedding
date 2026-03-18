import { __ } from '@wordpress/i18n';
import { useState, useEffect, useCallback } from '@wordpress/element';
import { Box, Flex, Text, Button, Spinner, Input } from '@chakra-ui/react';
import {
	getTwoFactorUsers,
	toggleUserTwoFactor,
	regenerateUserCodes,
} from '../../services/api';
import { toaster } from '../Toaster';

/**
 * UserManagement Component
 * Admin user table for managing 2FA across all users
 */
function UserManagement() {
	const [ users, setUsers ] = useState( [] );
	const [ total, setTotal ] = useState( 0 );
	const [ totalPages, setTotalPages ] = useState( 0 );
	const [ page, setPage ] = useState( 1 );
	const [ search, setSearch ] = useState( '' );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ actionLoading, setActionLoading ] = useState( {} );
	const perPage = 20;

	const fetchUsers = useCallback( async () => {
		setIsLoading( true );
		try {
			const response = await getTwoFactorUsers( page, perPage, search );
			if ( response.success ) {
				setUsers( response.users );
				setTotal( response.total );
				setTotalPages( response.total_pages );
			}
		} catch ( error ) {
			toaster.error( {
				title: __( 'Error', 'anti-spam' ),
				description:
					error.message || __( 'Failed to load users.', 'anti-spam' ),
			} );
		} finally {
			setIsLoading( false );
		}
	}, [ page, search ] );

	useEffect( () => {
		fetchUsers();
	}, [ fetchUsers ] );

	// Debounced search
	const [ searchInput, setSearchInput ] = useState( '' );
	useEffect( () => {
		const timer = setTimeout( () => {
			setSearch( searchInput );
			setPage( 1 );
		}, 400 );
		return () => clearTimeout( timer );
	}, [ searchInput ] );

	const handleToggle = async ( userId, enabled ) => {
		setActionLoading( ( prev ) => ( {
			...prev,
			[ `toggle-${ userId }` ]: true,
		} ) );
		try {
			const response = await toggleUserTwoFactor( userId, enabled );
			if ( response.success ) {
				setUsers( ( prev ) =>
					prev.map( ( u ) =>
						u.id === userId
							? {
									...u,
									enabled,
									setup_complete: enabled
										? u.setup_complete
										: false,
							  }
							: u
					)
				);
				toaster.success( {
					title: enabled
						? __( '2FA enabled', 'anti-spam' )
						: __( '2FA disabled', 'anti-spam' ),
					description: response.message,
				} );
			}
		} catch ( error ) {
			toaster.error( {
				title: __( 'Error', 'anti-spam' ),
				description:
					error.message ||
					__( 'Failed to update user.', 'anti-spam' ),
			} );
		} finally {
			setActionLoading( ( prev ) => {
				const next = { ...prev };
				delete next[ `toggle-${ userId }` ];
				return next;
			} );
		}
	};

	const handleRegenCodes = async ( userId ) => {
		setActionLoading( ( prev ) => ( {
			...prev,
			[ `regen-${ userId }` ]: true,
		} ) );
		try {
			const response = await regenerateUserCodes( userId );
			if ( response.success ) {
				toaster.success( {
					title: __( 'Codes regenerated', 'anti-spam' ),
					description: response.message,
				} );
			}
		} catch ( error ) {
			toaster.error( {
				title: __( 'Error', 'anti-spam' ),
				description:
					error.message ||
					__( 'Failed to regenerate codes.', 'anti-spam' ),
			} );
		} finally {
			setActionLoading( ( prev ) => {
				const next = { ...prev };
				delete next[ `regen-${ userId }` ];
				return next;
			} );
		}
	};

	const startItem = ( page - 1 ) * perPage + 1;
	const endItem = Math.min( page * perPage, total );

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
					{ __( 'User Management', 'anti-spam' ) }
				</Text>
				<Text fontSize="sm" color="gray.600" mt={ 0.5 }>
					{ __(
						'Manage two-factor authentication for all users',
						'anti-spam'
					) }
				</Text>
			</Box>

			<Box px={ 6 } py={ 4 }>
				<Input
					value={ searchInput }
					onChange={ ( e ) => setSearchInput( e.target.value ) }
					placeholder={ __( 'Search users...', 'anti-spam' ) }
					maxW={ { base: '100%', sm: '300px' } }
					fontSize="sm"
					borderColor="gray.300"
				/>
			</Box>

			{ isLoading ? (
				<Flex justify="center" py={ 8 }>
					<Spinner size="md" />
				</Flex>
			) : (
				<>
					<Box overflowX="auto">
						<Box as="table" w="full" fontSize="sm" minW="600px">
							<Box as="thead">
								<Box as="tr" bg="gray.50">
									<Box
										as="th"
										textAlign="left"
										px={ 6 }
										py={ 3 }
										fontWeight="600"
										color="gray.600"
										fontSize="xs"
										textTransform="uppercase"
										letterSpacing="wider"
									>
										{ __( 'Username', 'anti-spam' ) }
									</Box>
									<Box
										as="th"
										textAlign="left"
										px={ 6 }
										py={ 3 }
										fontWeight="600"
										color="gray.600"
										fontSize="xs"
										textTransform="uppercase"
										letterSpacing="wider"
									>
										{ __( '2FA Status', 'anti-spam' ) }
									</Box>
									<Box
										as="th"
										textAlign="right"
										px={ 6 }
										py={ 3 }
										fontWeight="600"
										color="gray.600"
										fontSize="xs"
										textTransform="uppercase"
										letterSpacing="wider"
									>
										{ __( 'Actions', 'anti-spam' ) }
									</Box>
								</Box>
							</Box>
							<Box as="tbody">
								{ users.map( ( user ) => (
									<Box
										as="tr"
										key={ user.id }
										borderTopWidth="1px"
										borderColor="gray.100"
										_hover={ {
											bg: 'gray.50',
										} }
									>
										<Box as="td" px={ 6 } py={ 3 }>
											<Text
												fontWeight="500"
												color="gray.900"
											>
												{ user.display_name ||
													user.username }
											</Text>
											<Text
												fontSize="xs"
												color="gray.500"
											>
												{ user.email }
											</Text>
										</Box>
										<Box as="td" px={ 6 } py={ 3 }>
											<Flex align="center" gap={ 1.5 }>
												<Box
													w="6px"
													h="6px"
													borderRadius="full"
													bg={
														user.enabled &&
														user.setup_complete
															? 'green.500'
															: user.enabled
															? 'yellow.500'
															: 'gray.400'
													}
												/>
												<Text
													fontSize="sm"
													color={
														user.enabled
															? 'gray.800'
															: 'gray.500'
													}
												>
													{ user.enabled &&
													user.setup_complete
														? __(
																'Active',
																'anti-spam'
														  )
														: user.enabled
														? __(
																'Pending Setup',
																'anti-spam'
														  )
														: __(
																'Inactive',
																'anti-spam'
														  ) }
												</Text>
											</Flex>
										</Box>
										<Box
											as="td"
											px={ 6 }
											py={ 3 }
											textAlign="right"
										>
											<Flex gap={ 2 } justify="flex-end">
												{ user.enabled ? (
													<Button
														onClick={ () =>
															handleToggle(
																user.id,
																false
															)
														}
														size="xs"
														variant="outline"
														borderColor="red.300"
														color="red.600"
														fontSize="xs"
														disabled={
															actionLoading[
																`toggle-${ user.id }`
															]
														}
													>
														{ actionLoading[
															`toggle-${ user.id }`
														]
															? __(
																	'...',
																	'anti-spam'
															  )
															: __(
																	'Disable',
																	'anti-spam'
															  ) }
													</Button>
												) : (
													<Button
														onClick={ () =>
															handleToggle(
																user.id,
																true
															)
														}
														size="xs"
														variant="outline"
														borderColor="green.300"
														color="green.600"
														fontSize="xs"
														disabled={
															actionLoading[
																`toggle-${ user.id }`
															]
														}
													>
														{ actionLoading[
															`toggle-${ user.id }`
														]
															? __(
																	'...',
																	'anti-spam'
															  )
															: __(
																	'Enable',
																	'anti-spam'
															  ) }
													</Button>
												) }
												{ user.enabled &&
													user.setup_complete && (
														<Button
															onClick={ () =>
																handleRegenCodes(
																	user.id
																)
															}
															size="xs"
															variant="outline"
															borderColor="gray.300"
															color="gray.600"
															fontSize="xs"
															disabled={
																actionLoading[
																	`regen-${ user.id }`
																]
															}
														>
															{ actionLoading[
																`regen-${ user.id }`
															]
																? __(
																		'...',
																		'anti-spam'
																  )
																: __(
																		'Regen Codes',
																		'anti-spam'
																  ) }
														</Button>
													) }
											</Flex>
										</Box>
									</Box>
								) ) }
								{ users.length === 0 && (
									<Box as="tr">
										<Box
											as="td"
											colSpan={ 3 }
											px={ 6 }
											py={ 8 }
											textAlign="center"
											color="gray.500"
											fontSize="sm"
										>
											{ search
												? __(
														'No users found matching your search.',
														'anti-spam'
												  )
												: __(
														'No users found.',
														'anti-spam'
												  ) }
										</Box>
									</Box>
								) }
							</Box>
						</Box>
					</Box>

					{ /* Pagination */ }
					{ total > 0 && (
						<Flex
							px={ 6 }
							py={ 4 }
							justify="space-between"
							align={ { base: 'flex-start', sm: 'center' } }
							direction={ { base: 'column', sm: 'row' } }
							gap={ { base: 3, sm: 0 } }
							borderTopWidth="1px"
							borderColor="gray.100"
						>
							<Text fontSize="sm" color="gray.600">
								{ total > 0
									? `${ __(
											'Showing',
											'anti-spam'
									  ) } ${ startItem }-${ endItem } ${ __(
											'of',
											'anti-spam'
									  ) } ${ total } ${ __(
											'users',
											'anti-spam'
									  ) }`
									: '' }
							</Text>
							<Flex gap={ 2 }>
								<Button
									onClick={ () =>
										setPage( ( p ) => Math.max( 1, p - 1 ) )
									}
									size="sm"
									variant="outline"
									borderColor="gray.300"
									color="gray.700"
									fontSize="sm"
									disabled={ page <= 1 }
								>
									{ __( 'Prev', 'anti-spam' ) }
								</Button>
								<Button
									onClick={ () => setPage( ( p ) => p + 1 ) }
									size="sm"
									variant="outline"
									borderColor="gray.300"
									color="gray.700"
									fontSize="sm"
									disabled={ page >= totalPages }
								>
									{ __( 'Next', 'anti-spam' ) }
								</Button>
							</Flex>
						</Flex>
					) }
				</>
			) }
		</Box>
	);
}

export default UserManagement;
