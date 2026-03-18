import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Box, Flex, Heading, Text, Button } from '@chakra-ui/react';
import DatabasePrefixFix from './checks/DatabasePrefixFix';

/**
 * AuditItem Component
 * Displays a single security audit item with severity indicator
 */
function AuditItem( {
	id,
	severity,
	title,
	description,
	time,
	fix,
	onFixSuccess,
	onHide,
	onUnhide,
	isHidden = false,
} ) {
	const [ fixIssueId, setFixIssueId ] = useState( null );
	const [ isFixModalOpen, setIsFixModalOpen ] = useState( false );

	const severityColors = {
		high: { bg: 'red.50', border: 'red.500' },
		medium: { bg: 'orange.50', border: 'orange.500' },
		low: { bg: 'yellow.50', border: 'yellow.500' },
	};

	const colors = isHidden
		? { bg: 'gray.50', border: 'gray.300' }
		: severityColors[ severity ] || severityColors.medium;

	// Check if this is a database prefix fix
	const isDatabasePrefixFix =
		fix && fix.includes( 'action=fix-database-prefix' );

	// Format UNIX timestamp to readable date
	const formatTime = ( timestamp ) => {
		if ( ! timestamp ) return '';
		const date = new Date( parseInt( timestamp ) * 1000 );
		return date.toLocaleDateString( undefined, {
			year: 'numeric',
			month: '2-digit',
			day: '2-digit',
			hour: '2-digit',
			minute: '2-digit',
		} );
	};

	/**
	 * Handle fix button click
	 * Open fix modal for internal actions, or open external URLs in new tab
	 */
	const handleFixClick = ( fixUrl ) => {
		if ( ! fixUrl ) {
			return;
		}

		// Check if this is an internal fix action
		if ( fixUrl.includes( 'action=fix-database-prefix' ) ) {
			// Extract issue ID and open modal
			try {
				const url = new URL( fixUrl, window.location.origin );
				const issueId = url.searchParams.get(
					'wtitan_fixing_issue_id'
				);

				// Store issue ID and open modal
				setFixIssueId( issueId ? parseInt( issueId, 10 ) : id );
				setIsFixModalOpen( true );
			} catch ( error ) {
				// If URL parsing fails, open in new tab as fallback
				window.open( fixUrl, '_blank' );
			}
		} else {
			// External URL (like update-core.php) - open in new tab
			window.open( fixUrl, '_blank' );
		}
	};

	return (
		<>
			<Box
				bg={ colors.bg }
				borderLeft="4px solid"
				borderColor={ colors.border }
				borderRadius="0 8px 8px 0"
				p={ 4 }
				opacity={ isHidden ? 0.7 : 1 }
			>
				<Flex justify="space-between" align="flex-start" gap={ 4 }>
					<Box flex="1">
						<Heading
							as="h3"
							fontSize="sm"
							fontWeight="semibold"
							color={ isHidden ? 'gray.500' : 'gray.900' }
							mb={ 1 }
						>
							{ title }
						</Heading>
						{ ! isHidden && (
							<Text
								fontSize="sm"
								color="gray.600"
								mb={ 2 }
								dangerouslySetInnerHTML={ {
									__html: description,
								} }
							/>
						) }
						{ time && ! isHidden && (
							<Flex
								align="center"
								gap={ 1 }
								fontSize="xs"
								color="gray.500"
							>
								<svg
									width="12"
									height="12"
									viewBox="0 0 24 24"
									fill="none"
									xmlns="http://www.w3.org/2000/svg"
								>
									<circle
										cx="12"
										cy="12"
										r="10"
										stroke="currentColor"
										strokeWidth="2"
										strokeLinecap="round"
										strokeLinejoin="round"
									/>
									<polyline
										points="12 6 12 12 16 14"
										stroke="currentColor"
										strokeWidth="2"
										strokeLinecap="round"
										strokeLinejoin="round"
									/>
								</svg>
								<Text>{ formatTime( time ) }</Text>
							</Flex>
						) }
					</Box>
					<Flex gap={ 2 } align="center" flexShrink={ 0 }>
						{ isHidden && onUnhide && (
							<Button
								px={ 3 }
								py={ 1 }
								fontSize="xs"
								variant="ghost"
								color="gray.500"
								borderRadius="md"
								_hover={ { bg: 'gray.100', color: 'gray.700' } }
								onClick={ () => onUnhide( id ) }
							>
								{ __( 'Unhide', 'anti-spam' ) }
							</Button>
						) }
						{ ! isHidden && fix && (
							<Button
								px={ 4 }
								py={ 2 }
								fontSize="sm"
								variant="outline"
								color="gray.700"
								border="1px solid"
								borderColor="gray.300"
								borderRadius="lg"
								_hover={ { bg: 'gray.50' } }
								transition="all 0.2s"
								onClick={ () => handleFixClick( fix ) }
							>
								{ __( 'Fix it', 'anti-spam' ) }
							</Button>
						) }
						{ ! isHidden && onHide && (
							<Button
								px={ 2 }
								py={ 2 }
								fontSize="xs"
								variant="ghost"
								color="gray.400"
								borderRadius="md"
								_hover={ { bg: 'gray.100', color: 'gray.600' } }
								title={ __( 'Hide this item', 'anti-spam' ) }
								onClick={ () => onHide( id ) }
							>
								<svg
									width="14"
									height="14"
									viewBox="0 0 24 24"
									fill="none"
									xmlns="http://www.w3.org/2000/svg"
								>
									<path
										d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"
										stroke="currentColor"
										strokeWidth="2"
										strokeLinecap="round"
										strokeLinejoin="round"
									/>
									<line
										x1="1"
										y1="1"
										x2="23"
										y2="23"
										stroke="currentColor"
										strokeWidth="2"
										strokeLinecap="round"
										strokeLinejoin="round"
									/>
								</svg>
							</Button>
						) }
					</Flex>
				</Flex>
			</Box>

			{ /* Database Prefix Fix Modal - always render to ensure proper portal behavior */ }
			{ isDatabasePrefixFix && isFixModalOpen && (
				<DatabasePrefixFix
					isOpen={ isFixModalOpen }
					onClose={ () => setIsFixModalOpen( false ) }
					issueId={ fixIssueId }
					onSuccess={ onFixSuccess }
				/>
			) }
		</>
	);
}

export default AuditItem;
