import { __, _n, sprintf } from '@wordpress/i18n';
import { HStack, Button, Text, Box, Tooltip } from '@chakra-ui/react';

const InfoIcon = () => (
	<svg
		width="12"
		height="12"
		viewBox="0 0 24 24"
		fill="none"
		xmlns="http://www.w3.org/2000/svg"
		style={ { marginLeft: '5px', flexShrink: 0, opacity: 0.5 } }
	>
		<circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="2" />
		<line
			x1="12"
			y1="8"
			x2="12"
			y2="8"
			stroke="currentColor"
			strokeWidth="2.5"
			strokeLinecap="round"
		/>
		<line
			x1="12"
			y1="12"
			x2="12"
			y2="16"
			stroke="currentColor"
			strokeWidth="2"
			strokeLinecap="round"
		/>
	</svg>
);

/**
 * SecurityTabNavigation Component
 * Handles tab navigation between Security Audit and Vulnerabilities
 */
function SecurityTabNavigation( {
	activeTab,
	setActiveTab,
	auditCount,
	vulnerabilityCount,
	isLicenseActive,
} ) {
	return (
		<HStack
			gap={ 2 }
			mb={ 6 }
			bg="gray.50"
			p={ 1 }
			borderRadius="md"
			flexWrap="wrap"
		>
			<Button
				onClick={ () => setActiveTab( 'audit' ) }
				variant="ghost"
				size="sm"
				px={ 3 }
				py={ 2 }
				bg={ activeTab === 'audit' ? 'white' : 'transparent' }
				color={ activeTab === 'audit' ? 'gray.900' : 'gray.600' }
				_hover={ { bg: activeTab === 'audit' ? 'white' : 'gray.100' } }
				fontWeight={ activeTab === 'audit' ? 'semibold' : 'normal' }
				boxShadow={ activeTab === 'audit' ? 'sm' : 'none' }
			>
				<svg
					width="14"
					height="14"
					viewBox="0 0 24 24"
					fill="none"
					xmlns="http://www.w3.org/2000/svg"
					style={ { marginRight: '6px' } }
				>
					<path
						d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"
						stroke="currentColor"
						strokeWidth="2"
						strokeLinecap="round"
						strokeLinejoin="round"
					/>
				</svg>
				{ sprintf(
					/* translators: %d: number of security audit issues */
					__( 'Security Audit (%d)', 'anti-spam' ),
					auditCount
				) }
				<Tooltip.Root>
					<Tooltip.Trigger>
						<Box as="span" onClick={ ( e ) => e.stopPropagation() }>
							<InfoIcon />
						</Box>
					</Tooltip.Trigger>
					<Tooltip.Positioner>
						<Tooltip.Content
							fontSize="xs"
							maxW="220px"
							whiteSpace="normal"
							bg="white"
							color="gray.700"
							boxShadow="md"
							css={ { '--tooltip-bg': 'white' } }
						>
							<Tooltip.Arrow>
								<Tooltip.ArrowTip />
							</Tooltip.Arrow>
							{ __(
								'Results are cached for 5 minutes and refresh on the next page load after expiry.',
								'anti-spam'
							) }
						</Tooltip.Content>
					</Tooltip.Positioner>
				</Tooltip.Root>
			</Button>

			<Button
				onClick={ () => setActiveTab( 'vulnerabilities' ) }
				variant="ghost"
				size="sm"
				px={ 3 }
				py={ 2 }
				bg={ activeTab === 'vulnerabilities' ? 'white' : 'transparent' }
				color={
					activeTab === 'vulnerabilities' ? 'gray.900' : 'gray.600'
				}
				_hover={ {
					bg: activeTab === 'vulnerabilities' ? 'white' : 'gray.100',
				} }
				fontWeight={
					activeTab === 'vulnerabilities' ? 'semibold' : 'normal'
				}
				boxShadow={ activeTab === 'vulnerabilities' ? 'sm' : 'none' }
			>
				<svg
					width="14"
					height="14"
					viewBox="0 0 24 24"
					fill="none"
					xmlns="http://www.w3.org/2000/svg"
					style={ { marginRight: '6px' } }
				>
					<path
						d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"
						stroke="currentColor"
						strokeWidth="2"
						strokeLinecap="round"
						strokeLinejoin="round"
					/>
					<line
						x1="12"
						y1="9"
						x2="12"
						y2="13"
						stroke="currentColor"
						strokeWidth="2"
						strokeLinecap="round"
						strokeLinejoin="round"
					/>
					<line
						x1="12"
						y1="17"
						x2="12.01"
						y2="17"
						stroke="currentColor"
						strokeWidth="2"
						strokeLinecap="round"
						strokeLinejoin="round"
					/>
				</svg>
				{ __( 'Vulnerabilities', 'anti-spam' ) }
				{ isLicenseActive ? (
					<Text as="span" color="inherit">
						{ ` (${ vulnerabilityCount })` }
					</Text>
				) : (
					<Box
						ml={ 2 }
						bg="purple.500"
						color="white"
						fontSize="10px"
						fontWeight="bold"
						px="6px"
						py="2px"
						borderRadius="full"
						lineHeight="1"
					>
						PRO
					</Box>
				) }
				<Tooltip.Root>
					<Tooltip.Trigger>
						<Box as="span" onClick={ ( e ) => e.stopPropagation() }>
							<InfoIcon />
						</Box>
					</Tooltip.Trigger>
					<Tooltip.Positioner>
						<Tooltip.Content
							fontSize="xs"
							maxW="220px"
							whiteSpace="normal"
							bg="white"
							color="gray.700"
							boxShadow="md"
							css={ { '--tooltip-bg': 'white' } }
						>
							<Tooltip.Arrow>
								<Tooltip.ArrowTip />
							</Tooltip.Arrow>
							{ __(
								'Vulnerability data is cached for 12 hours. Cache is also cleared automatically when plugins or themes are updated, activated, or removed.',
								'anti-spam'
							) }
						</Tooltip.Content>
					</Tooltip.Positioner>
				</Tooltip.Root>
			</Button>
		</HStack>
	);
}

export default SecurityTabNavigation;
