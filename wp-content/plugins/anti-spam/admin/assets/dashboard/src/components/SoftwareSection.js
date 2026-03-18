import { __, _n } from '@wordpress/i18n';
import { Box, Text, HStack, Button } from '@chakra-ui/react';
import { useState } from '@wordpress/element';
import VulnerabilityItem from './VulnerabilityItem';

/**
 * SoftwareSection Component
 * Displays vulnerabilities for a specific software (WordPress Core, Plugin, or Theme)
 */
function SoftwareSection( { softwareKey, data, type = 'plugin' } ) {
	const [ expanded, setExpanded ] = useState( false );

	let displayName = '';
	let iconClass = '';

	switch ( type ) {
		case 'core':
			displayName = __( 'WordPress Core', 'anti-spam' );
			iconClass = 'dashicons-wordpress';
			break;
		case 'theme':
			displayName =
				softwareKey.charAt( 0 ).toUpperCase() +
				softwareKey.slice( 1 ).replace( '-', ' ' );
			break;
		default: // plugin
			displayName =
				softwareKey.charAt( 0 ).toUpperCase() +
				softwareKey.slice( 1 ).replace( '-', ' ' );
	}

	const vulnerabilities = data.vulnerabilities || [];
	const visibleVulns = expanded
		? vulnerabilities
		: vulnerabilities.slice( 0, 1 );
	const hasMore = vulnerabilities.length > 1;

	return (
		<Box mb={ 6 }>
			<HStack mb={ 3 } align="center">
				{ type === 'core' && (
					<span
						className={ `dashicons ${ iconClass }` }
						style={ { fontSize: '16px' } }
					></span>
				) }
				<Text fontWeight="semibold" color="gray.900" fontSize="sm">
					{ displayName }{ ' ' }
					<Text as="span" color="gray.500" fontWeight="normal">
						v{ data.version }
					</Text>
				</Text>
			</HStack>

			<Box>
				{ visibleVulns.map( ( vuln ) => (
					<VulnerabilityItem
						key={ vuln.id }
						vulnerability={ vuln }
						software={ data }
					/>
				) ) }
			</Box>

			{ hasMore && (
				<Button
					variant="link"
					size="sm"
					color="blue.600"
					fontWeight="normal"
					fontSize="sm"
					onClick={ () => setExpanded( ! expanded ) }
					leftIcon={
						<span
							className={ `dashicons ${
								expanded
									? 'dashicons-arrow-up-alt2'
									: 'dashicons-arrow-down-alt2'
							}` }
							style={ { fontSize: '14px' } }
						></span>
					}
					p={ 0 }
					h="auto"
					minH="auto"
				>
					{ expanded
						? __( 'Show less', 'anti-spam' )
						: _n(
								`Show ${
									vulnerabilities.length - 1
								} more vulnerability`,
								`Show ${
									vulnerabilities.length - 1
								} more vulnerabilities`,
								vulnerabilities.length - 1,
								'anti-spam'
						  ) }
				</Button>
			) }
		</Box>
	);
}

export default SoftwareSection;
