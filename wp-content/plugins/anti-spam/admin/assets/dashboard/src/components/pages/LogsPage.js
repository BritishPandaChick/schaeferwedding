import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { Box, Button, VStack, Flex, Text, Spinner } from '@chakra-ui/react';
import { toaster } from '../Toaster';
import { getLogs, cleanLogs, exportLogs } from '../../services/api';

/**
 * LogsPage Component
 * Displays plugin error logs with options to export and clean up
 */
function LogsPage() {
	const [ logContent, setLogContent ] = useState( '' );
	const [ logSize, setLogSize ] = useState( '' );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ isCleaning, setIsCleaning ] = useState( false );
	const [ isExporting, setIsExporting ] = useState( false );

	useEffect( () => {
		fetchLogs();
	}, [] );

	const fetchLogs = async () => {
		setIsLoading( true );
		try {
			const response = await getLogs();
			setLogContent( response.content || '' );
			setLogSize( response.size || '0 B' );
		} catch ( error ) {
			toaster.create( {
				title: __( 'Error', 'anti-spam' ),
				description: __( 'Failed to fetch logs.', 'anti-spam' ),
				type: 'error',
			} );
		} finally {
			setIsLoading( false );
		}
	};

	const handleCleanup = async () => {
		if (
			! confirm(
				__(
					'Are you sure you want to clean up all logs? This action cannot be undone.',
					'anti-spam'
				)
			)
		) {
			return;
		}

		setIsCleaning( true );
		try {
			await cleanLogs();
			toaster.create( {
				title: __( 'Success', 'anti-spam' ),
				description: __( 'Logs cleaned successfully.', 'anti-spam' ),
				type: 'success',
			} );
			// Refresh logs after cleanup
			await fetchLogs();
		} catch ( error ) {
			toaster.create( {
				title: __( 'Error', 'anti-spam' ),
				description: __( 'Failed to clean logs.', 'anti-spam' ),
				type: 'error',
			} );
		} finally {
			setIsCleaning( false );
		}
	};

	const handleExport = async () => {
		setIsExporting( true );
		try {
			const response = await exportLogs();
			if ( response.url ) {
				// Trigger download
				window.location.href = response.url;
				toaster.create( {
					title: __( 'Success', 'anti-spam' ),
					description: __(
						'Export file created successfully.',
						'anti-spam'
					),
					type: 'success',
				} );
			}
		} catch ( error ) {
			toaster.create( {
				title: __( 'Error', 'anti-spam' ),
				description: __( 'Failed to export logs.', 'anti-spam' ),
				type: 'error',
			} );
		} finally {
			setIsExporting( false );
		}
	};

	return (
		<VStack align="stretch" gap={ 6 }>
			{ /* Header */ }
			<Box>
				<Flex align="center" justify="space-between" mb={ 2 }>
					<Box>
						<Text
							fontSize="2xl"
							fontWeight="semibold"
							color="gray.900"
						>
							{ __( 'Error Log', 'anti-spam' ) }
						</Text>
						<Text fontSize="sm" color="gray.600" mt={ 1 }>
							{ __(
								'In this section, you can track errors. Sending this log to us, will help in solving possible issues.',
								'anti-spam'
							) }
						</Text>
					</Box>
				</Flex>
			</Box>

			{ /* Actions Panel */ }
			<Box
				bg="white"
				p={ 6 }
				borderRadius="md"
				borderWidth="1px"
				borderColor="gray.200"
			>
				<Flex gap={ 3 } direction={ { base: 'column', sm: 'row' } }>
					<Button
						onClick={ handleExport }
						disabled={ isExporting }
						size="sm"
						colorScheme="purple"
						variant="outline"
						w={ { base: 'full', sm: 'auto' } }
					>
						{ isExporting && <Spinner size="sm" mr={ 2 } /> }
						{ __( 'Export Debug Information', 'anti-spam' ) }
					</Button>
					<Button
						onClick={ handleCleanup }
						disabled={ isCleaning }
						size="sm"
						variant="outline"
						w={ { base: 'full', sm: 'auto' } }
					>
						{ isCleaning ? (
							<>
								<Spinner size="sm" mr={ 2 } />
								{ __( 'Working...', 'anti-spam' ) }
							</>
						) : (
							/* translators: %s: log file size */
							__( 'Clean-up Logs', 'anti-spam' ) +
							` (${ logSize })`
						) }
					</Button>
				</Flex>
			</Box>

			{ /* Logs Viewer */ }
			<Box
				bg="white"
				p={ 6 }
				borderRadius="md"
				borderWidth="1px"
				borderColor="gray.200"
			>
				<Box
					bg="gray.900"
					color="gray.100"
					p={ 4 }
					borderRadius="md"
					fontFamily="monospace"
					fontSize="sm"
					overflowX="auto"
					maxH="600px"
					overflowY="auto"
					css={ {
						'&::-webkit-scrollbar': {
							width: '8px',
							height: '8px',
						},
						'&::-webkit-scrollbar-track': {
							background: '#2D3748',
						},
						'&::-webkit-scrollbar-thumb': {
							background: '#4A5568',
							borderRadius: '4px',
						},
						'&::-webkit-scrollbar-thumb:hover': {
							background: '#718096',
						},
					} }
				>
					{ isLoading ? (
						<Flex justify="center" align="center" minH="200px">
							<Spinner size="lg" color="purple.400" />
						</Flex>
					) : logContent ? (
						<Box
							dangerouslySetInnerHTML={ { __html: logContent } }
							sx={ {
								'& br': {
									display: 'block',
									content: '""',
									marginTop: '0.25em',
								},
							} }
						/>
					) : (
						<Text color="gray.400" textAlign="center" py={ 8 }>
							{ __( 'No logs found.', 'anti-spam' ) }
						</Text>
					) }
				</Box>
			</Box>
		</VStack>
	);
}

export default LogsPage;
