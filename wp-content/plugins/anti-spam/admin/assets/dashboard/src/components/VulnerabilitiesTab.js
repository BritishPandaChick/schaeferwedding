import { __ } from '@wordpress/i18n';
import {
	Box,
	Flex,
	Text,
	Button,
	VStack,
	Heading,
	HStack,
} from '@chakra-ui/react';
import SoftwareSection from './SoftwareSection';

/**
 * VulnerabilitiesTab Component
 * Displays vulnerabilities tab content with grouped software sections
 */
function VulnerabilitiesTab( {
	isLicenseActive,
	vulnerabilityData,
	getTotalVulnerabilities,
} ) {
	if ( ! isLicenseActive ) {
		return (
			<Box textAlign="center" py={ 8 }>
				<VStack spacing={ 4 }>
					<Box>
						<Heading
							as="h3"
							fontSize="lg"
							color="gray.900"
							mb={ 2 }
						>
							{ __(
								'Unlock Vulnerability Scanner',
								'anti-spam'
							) }
						</Heading>
						<Text fontSize="sm" color="gray.600" mb={ 4 }>
							{ __(
								'Scan your WordPress plugins and themes for known security vulnerabilities with our Pro version.',
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
						minW="180px"
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
				</VStack>
			</Box>
		);
	}

	if ( ! vulnerabilityData || getTotalVulnerabilities() === 0 ) {
		return (
			<Text fontSize="sm" color="gray.500" textAlign="center" py={ 8 }>
				{ __(
					'No vulnerabilities found. Your are up to date!',
					'anti-spam'
				) }
			</Text>
		);
	}

	// Filter functions
	const getPluginEntries = () => {
		return Object.entries( vulnerabilityData )
			.filter(
				( [ key, data ] ) =>
					key !== 'wordpress' && data.vulnerabilities?.length > 0
			)
			.filter( ( [ key, data ] ) => {
				const isTheme =
					data.vulnerabilities &&
					data.vulnerabilities.some(
						( v ) => v.software?.[ 0 ]?.type === 'theme'
					);
				return ! isTheme;
			} );
	};

	const getThemeEntries = () => {
		return Object.entries( vulnerabilityData )
			.filter(
				( [ key, data ] ) =>
					key !== 'wordpress' && data.vulnerabilities?.length > 0
			)
			.filter( ( [ key, data ] ) => {
				const isTheme =
					data.vulnerabilities &&
					data.vulnerabilities.some(
						( v ) => v.software?.[ 0 ]?.type === 'theme'
					);
				return isTheme;
			} );
	};

	const pluginEntries = getPluginEntries();
	const themeEntries = getThemeEntries();

	return (
		<Box>
			<Flex justify="space-between" align="center" mb={ 6 }>
				<Text fontSize="sm" color="gray.600">
					{ __(
						'Review vulnerabilities and update your software to stay secure.',
						'anti-spam'
					) }
				</Text>
				<Button
					size="sm"
					variant="outline"
					color="gray.700"
					borderColor="gray.300"
					_hover={ { bg: 'gray.50' } }
					asChild
				>
					<a
						href={ window.titanSecurityObjects?.updateUrl }
						target="_blank"
					>
						{ __( 'Go to Updates', 'anti-spam' ) }
					</a>
				</Button>
			</Flex>

			<Box>
				{ vulnerabilityData.wordpress &&
					vulnerabilityData.wordpress.vulnerabilities?.length > 0 && (
						<SoftwareSection
							softwareKey="wordpress"
							data={ vulnerabilityData.wordpress }
							type="core"
						/>
					) }

				{ pluginEntries.length > 0 && (
					<Box>
						<HStack mb={ 4 } align="center">
							<span
								className="dashicons dashicons-admin-plugins"
								style={ { fontSize: '16px' } }
							></span>
							<Text
								fontWeight="semibold"
								color="gray.900"
								fontSize="sm"
							>
								{ __( 'Plugins', 'anti-spam' ) }
							</Text>
						</HStack>

						<Box pl={ 6 }>
							{ pluginEntries.map( ( [ key, data ] ) => (
								<SoftwareSection
									key={ key }
									softwareKey={ key }
									data={ data }
									type="plugin"
								/>
							) ) }
						</Box>
					</Box>
				) }

				{ themeEntries.length > 0 && (
					<Box>
						<HStack mb={ 4 } align="center">
							<span
								className="dashicons dashicons-admin-appearance"
								style={ { fontSize: '16px' } }
							></span>
							<Text
								fontWeight="semibold"
								color="gray.900"
								fontSize="sm"
							>
								{ __( 'Themes', 'anti-spam' ) }
							</Text>
						</HStack>

						<Box pl={ 6 }>
							{ themeEntries.map( ( [ key, data ] ) => (
								<SoftwareSection
									key={ key }
									softwareKey={ key }
									data={ data }
									type="theme"
								/>
							) ) }
						</Box>
					</Box>
				) }
			</Box>
		</Box>
	);
}

export default VulnerabilitiesTab;
