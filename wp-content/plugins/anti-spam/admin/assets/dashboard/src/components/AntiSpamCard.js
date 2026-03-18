import { __ } from '@wordpress/i18n';
import { Box, Flex, Text, Button, Grid, HStack } from '@chakra-ui/react';

/**
 * AntiSpamCard Component
 * Displays anti-spam protection status with stats for different time periods
 */
function AntiSpamCard( { stats, isActive, onConfigure } ) {
	return (
		<Box
			bg="white"
			borderRadius="lg"
			borderWidth="1px"
			borderColor="gray.200"
			p={ 6 }
		>
			<Flex
				justify="space-between"
				align={ { base: 'flex-start', sm: 'start' } }
				direction={ { base: 'column', sm: 'row' } }
				gap={ { base: 3, sm: 0 } }
				mb={ 4 }
			>
				<HStack gap={ 3 }>
					<Flex
						w="48px"
						h="48px"
						borderRadius="lg"
						bg={ isActive ? 'green.50' : 'gray.50' }
						align="center"
						justify="center"
					>
						<svg
							width="24"
							height="24"
							viewBox="0 0 24 24"
							fill="none"
							xmlns="http://www.w3.org/2000/svg"
						>
							<path
								d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"
								stroke={ isActive ? '#16a34a' : '#4b5563' }
								strokeWidth="2"
								strokeLinecap="round"
								strokeLinejoin="round"
							/>
						</svg>
					</Flex>
					<Box>
						<Text
							fontSize="lg"
							fontWeight="semibold"
							color="gray.900"
						>
							{ __( 'Anti-Spam Protection', 'anti-spam' ) }
						</Text>
						<HStack gap={ 2 } mt={ 1 }>
							<Box
								w="8px"
								h="8px"
								borderRadius="full"
								bg={ isActive ? 'green.500' : 'gray.400' }
							/>
							<Text
								fontSize="sm"
								color={ isActive ? 'green.600' : 'gray.600' }
								fontWeight="medium"
							>
								{ isActive
									? __( 'Active & Protecting', 'anti-spam' )
									: __( 'Inactive', 'anti-spam' ) }
							</Text>
						</HStack>
					</Box>
				</HStack>
				<Button
					size="sm"
					variant="outline"
					color="gray.700"
					borderColor="gray.300"
					_hover={ { bg: 'gray.50' } }
					onClick={ onConfigure }
				>
					{ __( 'Configure', 'anti-spam' ) } →
				</Button>
			</Flex>

			<Grid
				gridTemplateColumns={ { base: '1fr', sm: 'repeat(3, 1fr)' } }
				gap={ { base: 4, md: 6 } }
				pt={ 4 }
				borderTopWidth="1px"
				borderColor="gray.100"
			>
				<Box>
					<Text fontSize="xs" color="gray.600" mb={ 2 }>
						{ __( 'Today', 'anti-spam' ) }
					</Text>
					<Text
						fontSize="3xl"
						fontWeight="bold"
						color="gray.900"
						mb={ 1 }
					>
						{ stats?.blocked_today || 0 }
					</Text>
					<Text fontSize="sm" color="gray.500">
						{ __( 'spam blocked', 'anti-spam' ) }
					</Text>
				</Box>

				<Box>
					<Text fontSize="xs" color="gray.600" mb={ 2 }>
						{ __( 'This Week', 'anti-spam' ) }
					</Text>
					<Text
						fontSize="3xl"
						fontWeight="bold"
						color="gray.900"
						mb={ 1 }
					>
						{ stats?.blocked_7_days || 0 }
					</Text>
					<Text fontSize="sm" color="gray.500">
						{ __( 'spam blocked', 'anti-spam' ) }
					</Text>
				</Box>

				<Box>
					<Text fontSize="xs" color="gray.600" mb={ 2 }>
						{ __( 'This Month', 'anti-spam' ) }
					</Text>
					<Text
						fontSize="3xl"
						fontWeight="bold"
						color="gray.900"
						mb={ 1 }
					>
						{ stats?.blocked_30_days || 0 }
					</Text>
					<Text fontSize="sm" color="gray.500">
						{ __( 'spam blocked', 'anti-spam' ) }
					</Text>
				</Box>
			</Grid>
		</Box>
	);
}

export default AntiSpamCard;
