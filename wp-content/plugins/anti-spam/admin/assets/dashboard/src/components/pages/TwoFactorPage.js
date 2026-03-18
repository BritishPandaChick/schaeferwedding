import { __ } from '@wordpress/i18n';
import { useContext } from '@wordpress/element';
import { Box, VStack, Flex, Text, Heading, Button } from '@chakra-ui/react';
import PageHeader from '../common/PageHeader';
import SetupCard from '../two-factor/SetupCard';
import ActiveCard from '../two-factor/ActiveCard';
import UserManagement from '../two-factor/UserManagement';
import { AppContext } from '../../provider';

/**
 * TwoFactorPage Component
 * Main 2FA page - shows setup or active state based on current user's status
 */
function TwoFactorPage() {
	const { twoFactorData, isLicenseActive } = useContext( AppContext );

	const isActive = twoFactorData.enabled && twoFactorData.setup_complete;

	if ( ! isLicenseActive ) {
		return (
			<Box>
				<PageHeader
					title={ __( 'Two-Factor Authentication', 'anti-spam' ) }
					description={ __(
						'Add an extra layer of security to your account',
						'anti-spam'
					) }
				/>

				<Box
					bg="purple.50"
					borderRadius="lg"
					borderWidth="1px"
					borderColor="purple.200"
					p={ 6 }
					mb={ 6 }
				>
					<Flex align="center" gap={ 6 }>
						<Box flex="1">
							<Heading
								fontSize="lg"
								fontWeight="semibold"
								color="gray.900"
								mb={ 2 }
							>
								{ __(
									'Unlock Two-Factor Authentication',
									'anti-spam'
								) }
							</Heading>
							<Text
								fontSize="sm"
								color="gray.600"
								lineHeight="tall"
							>
								{ __(
									'Protect your WordPress accounts with an additional layer of security. Require a one-time code from an authenticator app on every login.',
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
					</Flex>
				</Box>

				{ /* Disabled Preview */ }
				<Box pointerEvents="none" opacity={ 0.5 }>
					<VStack gap={ 6 } align="stretch">
						<SetupCard disabled />
						<ActiveCard disabled />
					</VStack>
				</Box>
			</Box>
		);
	}

	return (
		<Box>
			<Flex justify="space-between" align="center" mb={ 6 }>
				<Box>
					<Flex align="center" gap={ 3 }>
						<Box
							fontSize="2xl"
							fontWeight="semibold"
							color="gray.900"
						>
							{ __( 'Two-Factor Authentication', 'anti-spam' ) }
						</Box>
						{ isActive && (
							<Flex
								align="center"
								gap={ 1.5 }
								bg="green.50"
								color="green.700"
								px={ 3 }
								py={ 1 }
								borderRadius="full"
								fontSize="xs"
								fontWeight="600"
							>
								<Box
									w="6px"
									h="6px"
									borderRadius="full"
									bg="green.500"
								/>
								{ __( 'Active', 'anti-spam' ) }
							</Flex>
						) }
					</Flex>
					<Text fontSize="sm" color="gray.600" mt={ 1 }>
						{ isActive
							? __(
									'Your account is protected with 2FA',
									'anti-spam'
							  )
							: __(
									'Add an extra layer of security to your account',
									'anti-spam'
							  ) }
					</Text>
				</Box>
			</Flex>

			<VStack gap={ 6 } align="stretch">
				{ isActive ? <ActiveCard /> : <SetupCard /> }

				{ isLicenseActive && <UserManagement /> }
			</VStack>
		</Box>
	);
}

export default TwoFactorPage;
