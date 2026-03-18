import { __ } from '@wordpress/i18n';
import { Box, Flex, Heading, Text, Button, Spinner } from '@chakra-ui/react';

/**
 * PageHeader Component
 * Reusable page header with title, description and action button
 */
function PageHeader( {
	title,
	description,
	onSave,
	saveLabel = __( 'Save Changes', 'anti-spam' ),
	isSaving = false,
	isDisabled = false,
} ) {
	return (
		<Flex
			justify="space-between"
			align={ { base: 'flex-start', sm: 'center' } }
			direction={ { base: 'column', sm: 'row' } }
			gap={ { base: 3, sm: 0 } }
			mb={ 6 }
		>
			<Box>
				<Heading
					fontSize="2xl"
					fontWeight="semibold"
					color="gray.900"
					mb={ 1 }
				>
					{ title }
				</Heading>
				<Text fontSize="sm" color="gray.600">
					{ description }
				</Text>
			</Box>
			{ onSave && (
				<Button
					onClick={ onSave }
					w={ { base: 'full', sm: 'auto' } }
					px={ 4 }
					py={ 2 }
					bg="purple.600"
					color="white"
					borderRadius="md"
					fontWeight="medium"
					fontSize="sm"
					_hover={ { bg: 'purple.700' } }
					_active={ { bg: 'purple.800' } }
					transition="all 0.2s"
					disabled={ isSaving || isDisabled }
					opacity={ isSaving || isDisabled ? 0.6 : 1 }
					cursor={
						isSaving || isDisabled ? 'not-allowed' : 'pointer'
					}
				>
					{ isSaving && <Spinner size="sm" mr={ 2 } /> }
					{ isSaving ? __( 'Saving...', 'anti-spam' ) : saveLabel }
				</Button>
			) }
		</Flex>
	);
}

export default PageHeader;
