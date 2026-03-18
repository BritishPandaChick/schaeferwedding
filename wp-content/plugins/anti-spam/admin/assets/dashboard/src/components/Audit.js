import { __, _n, sprintf } from '@wordpress/i18n';
import {
	Box,
	Flex,
	Heading,
	Text,
	VStack,
	Button,
	Spinner,
} from '@chakra-ui/react';
import {
	useContext,
	useState,
	useEffect,
	useCallback,
} from '@wordpress/element';
import { AppContext } from '../provider';
import AuditItem from './AuditItem';
import EmailOptInBanner from './EmailOptInBanner';
import SecurityTabNavigation from './SecurityTabNavigation';
import VulnerabilitiesTab from './VulnerabilitiesTab';
import { hideAuditItem, unhideAuditItem } from '../services/api';

/**
 * Audit Component
 * Displays security audit issues and warnings with tabs
 */
function Audit() {
	const { auditData, setAuditData, isLicenseActive, vulnerabilityData } =
		useContext( AppContext );
	const [ activeTab, setActiveTab ] = useState( 'audit' );
	const [ showOptIn, setShowOptIn ] = useState( true );
	const [ hasScanned, setHasScanned ] = useState(
		() => localStorage.getItem( 'titan_security_scanned' ) === 'yes'
	);
	const [ isScanning, setIsScanning ] = useState( false );
	const [ showHidden, setShowHidden ] = useState( false );

	// Check localStorage on mount
	useEffect( () => {
		const hideOptIn = localStorage.getItem( 'titan_hide_optin' );
		if ( hideOptIn === 'yes' ) {
			setShowOptIn( false );
		}
	}, [] );

	const handleScan = useCallback( () => {
		setIsScanning( true );
		setTimeout( () => {
			localStorage.setItem( 'titan_security_scanned', 'yes' );
			setHasScanned( true );
			setIsScanning( false );
		}, 3000 );
	}, [] );

	// Move an item from visible → hidden optimistically, then persist.
	const handleHide = useCallback(
		( id ) => {
			setAuditData( ( prev ) => {
				const item = prev.items.find( ( i ) => i.id === id );
				if ( ! item ) return prev;
				const newItems = prev.items.filter( ( i ) => i.id !== id );
				const newHidden = [ ...( prev.hidden_items || [] ), item ];
				return {
					...prev,
					items: newItems,
					count: newItems.length,
					hidden_items: newHidden,
					hided_count: newHidden.length,
				};
			} );
			hideAuditItem( id ).catch( () => {
				// Rollback on failure.
				setAuditData( ( prev ) => {
					const item = ( prev.hidden_items || [] ).find(
						( i ) => i.id === id
					);
					if ( ! item ) return prev;
					const newHidden = prev.hidden_items.filter(
						( i ) => i.id !== id
					);
					const newItems = [ ...prev.items, item ];
					return {
						...prev,
						items: newItems,
						count: newItems.length,
						hidden_items: newHidden,
						hided_count: newHidden.length,
					};
				} );
			} );
		},
		[ setAuditData ]
	);

	// Move an item from hidden → visible optimistically, then persist.
	const handleUnhide = useCallback(
		( id ) => {
			setAuditData( ( prev ) => {
				const item = ( prev.hidden_items || [] ).find(
					( i ) => i.id === id
				);
				if ( ! item ) return prev;
				const newHidden = prev.hidden_items.filter(
					( i ) => i.id !== id
				);
				const newItems = [ ...prev.items, item ];
				return {
					...prev,
					items: newItems,
					count: newItems.length,
					hidden_items: newHidden,
					hided_count: newHidden.length,
				};
			} );
			unhideAuditItem( id ).catch( () => {
				// Rollback on failure.
				setAuditData( ( prev ) => {
					const item = prev.items.find( ( i ) => i.id === id );
					if ( ! item ) return prev;
					const newItems = prev.items.filter( ( i ) => i.id !== id );
					const newHidden = [ ...( prev.hidden_items || [] ), item ];
					return {
						...prev,
						items: newItems,
						count: newItems.length,
						hidden_items: newHidden,
						hided_count: newHidden.length,
					};
				} );
			} );
		},
		[ setAuditData ]
	);

	// Calculate total vulnerabilities
	const getTotalVulnerabilities = () => {
		if ( ! vulnerabilityData ) return 0;
		return Object.values( vulnerabilityData ).reduce( ( total, item ) => {
			return (
				total +
				( item.vulnerabilities ? item.vulnerabilities.length : 0 )
			);
		}, 0 );
	};

	// Generate status summary text
	const getStatusSummary = () => {
		const vulnCount = getTotalVulnerabilities();
		const warningCount = auditData.count || 0;
		const totalIssues = vulnCount + warningCount;

		if ( totalIssues === 0 ) {
			return __( 'No issues found', 'anti-spam' );
		}

		const parts = [];
		if ( vulnCount > 0 ) {
			parts.push(
				_n(
					`${ vulnCount } vulnerability`,
					`${ vulnCount } vulnerabilities`,
					vulnCount,
					'anti-spam'
				)
			);
		}

		if ( warningCount > 0 ) {
			parts.push(
				_n(
					`${ warningCount } warning`,
					`${ warningCount } warnings`,
					warningCount,
					'anti-spam'
				)
			);
		}

		return sprintf(
			/* translators: %s: comma-separated list of issues */
			__( 'Issues found: %s', 'anti-spam' ),
			parts.join( ', ' )
		);
	};

	const hiddenItems = auditData.hidden_items || [];

	return (
		<Box
			borderRadius="lg"
			borderWidth="1px"
			borderColor={ showOptIn ? 'purple.200' : 'gray.200' }
			overflow="hidden"
			bg="white"
		>
			{ showOptIn && (
				<EmailOptInBanner onDismiss={ () => setShowOptIn( false ) } />
			) }
			<Box p={ 6 }>
				<Flex align="center" justify="space-between" mb={ 6 }>
					<Box>
						<Heading
							as="h2"
							fontSize="lg"
							fontWeight="semibold"
							color="gray.900"
							mb={ 1 }
						>
							{ __( 'Security Audit', 'anti-spam' ) }
						</Heading>
						<Text fontSize="sm" color="gray.500">
							{ hasScanned
								? getStatusSummary()
								: __(
										'Run a scan to check your site for issues.',
										'anti-spam'
								  ) }
						</Text>
					</Box>
				</Flex>

				{ ! hasScanned ? (
					<VStack spacing={ 4 } align="center" py={ 12 }>
						{ isScanning ? (
							<>
								<Spinner
									size="xl"
									color="purple.500"
									borderWidth="3px"
								/>
								<Text fontSize="sm" color="gray.500">
									{ __(
										'Scanning your site\u2026',
										'anti-spam'
									) }
								</Text>
							</>
						) : (
							<>
								<Box
									w="56px"
									h="56px"
									borderRadius="full"
									bg="purple.50"
									display="flex"
									alignItems="center"
									justifyContent="center"
								>
									<svg
										width="28"
										height="28"
										viewBox="0 0 24 24"
										fill="none"
										xmlns="http://www.w3.org/2000/svg"
									>
										<path
											d="M12 2L3 7v5c0 5.25 3.75 10.15 9 11.25C17.25 22.15 21 17.25 21 12V7L12 2z"
											stroke="#7C3AED"
											strokeWidth="2"
											strokeLinecap="round"
											strokeLinejoin="round"
										/>
									</svg>
								</Box>
								<VStack spacing={ 1 }>
									<Text
										fontWeight="semibold"
										color="gray.900"
										fontSize="md"
									>
										{ __(
											'Run a Security Scan',
											'anti-spam'
										) }
									</Text>
									<Text
										fontSize="sm"
										color="gray.500"
										textAlign="center"
										maxW="320px"
									>
										{ __(
											'Check your site for security issues, outdated software, and known vulnerabilities.',
											'anti-spam'
										) }
									</Text>
								</VStack>
								<Button
									colorScheme="purple"
									color="white"
									_hover={ { color: 'white' } }
									size="md"
									fontWeight="semibold"
									minW="140px"
									onClick={ handleScan }
								>
									{ __( 'Scan Now', 'anti-spam' ) }
								</Button>
							</>
						) }
					</VStack>
				) : (
					<>
						<SecurityTabNavigation
							activeTab={ activeTab }
							setActiveTab={ setActiveTab }
							auditCount={ auditData.count || 0 }
							vulnerabilityCount={ getTotalVulnerabilities() }
							isLicenseActive={ isLicenseActive }
						/>

						{ activeTab === 'audit' && (
							<>
								{ auditData.items &&
								auditData.items.length > 0 ? (
									<VStack spacing={ 3 } align="stretch">
										{ auditData.items.map( ( item ) => (
											<AuditItem
												key={ item.id }
												id={ item.id }
												severity={ item.severity }
												title={ item.title }
												description={ item.description }
												time={ item.time }
												fix={ item.fix }
												onHide={ handleHide }
											/>
										) ) }
									</VStack>
								) : (
									<Text
										fontSize="sm"
										color="gray.500"
										textAlign="center"
										py={ 4 }
									>
										{ __(
											'No audit issues found.',
											'anti-spam'
										) }
									</Text>
								) }

								{ hiddenItems.length > 0 && (
									<Box mt={ 6 }>
										<Button
											variant="ghost"
											size="sm"
											color="gray.400"
											px={ 0 }
											_hover={ {
												color: 'gray.600',
												bg: 'transparent',
											} }
											onClick={ () =>
												setShowHidden( ( v ) => ! v )
											}
										>
											<svg
												width="12"
												height="12"
												viewBox="0 0 24 24"
												fill="none"
												xmlns="http://www.w3.org/2000/svg"
												style={ {
													marginRight: '6px',
													transform: showHidden
														? 'rotate(90deg)'
														: 'rotate(0deg)',
													transition:
														'transform 0.15s',
												} }
											>
												<polyline
													points="9 18 15 12 9 6"
													stroke="currentColor"
													strokeWidth="2"
													strokeLinecap="round"
													strokeLinejoin="round"
												/>
											</svg>
											{ sprintf(
												/* translators: %d: number of hidden audit items */
												_n(
													'%d hidden item',
													'%d hidden items',
													hiddenItems.length,
													'anti-spam'
												),
												hiddenItems.length
											) }
										</Button>

										{ showHidden && (
											<VStack
												spacing={ 2 }
												align="stretch"
												mt={ 2 }
											>
												{ hiddenItems.map( ( item ) => (
													<AuditItem
														key={ item.id }
														id={ item.id }
														severity={
															item.severity
														}
														title={ item.title }
														description={
															item.description
														}
														time={ item.time }
														fix={ null }
														onUnhide={
															handleUnhide
														}
														isHidden
													/>
												) ) }
											</VStack>
										) }
									</Box>
								) }
							</>
						) }

						{ activeTab === 'vulnerabilities' && (
							<VulnerabilitiesTab
								isLicenseActive={ isLicenseActive }
								vulnerabilityData={ vulnerabilityData }
								getTotalVulnerabilities={
									getTotalVulnerabilities
								}
							/>
						) }
					</>
				) }
			</Box>
		</Box>
	);
}

export default Audit;
