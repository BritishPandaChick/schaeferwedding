import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { Box, Flex, Text, Input } from '@chakra-ui/react';
import { SegmentedButton } from '../common/SettingsControls';
import { SettingToggle } from '../common/SettingsControls';

const SCHEDULE_OPTIONS = [
	{ value: 'off', label: __( 'Manually', 'anti-spam' ) },
	{ value: '2h', label: __( 'Every 2h', 'anti-spam' ) },
	{ value: '8h', label: __( 'Every 8h', 'anti-spam' ) },
	{ value: '1d', label: __( 'Daily', 'anti-spam' ) },
];

const SPEED_OPTIONS = [
	{ value: 'slow', label: __( 'Slow', 'anti-spam' ) },
	{ value: 'fast', label: __( 'Fast', 'anti-spam' ) },
	{ value: 'custom', label: __( 'Custom', 'anti-spam' ) },
];

/**
 * Backup speed conversion constants.
 *
 * The backup system processes files in batches ("iterations").
 * Each iteration runs once every 5 seconds (12 times per minute),
 * so files_per_iteration * 12 = files per minute.
 */
const ITERATIONS_TO_FPM = 12;
const MIN_SPEED_FPM = ITERATIONS_TO_FPM;
const SLOW_ITERATIONS = 100;
const FAST_ITERATIONS = 500;
const DEFAULT_CUSTOM_ITERATIONS = 200;
const DEFAULT_CUSTOM_SPEED_FPM = DEFAULT_CUSTOM_ITERATIONS * ITERATIONS_TO_FPM;

const getIterationsFromSettings = ( settings ) => {
	const iterations = Number( settings.backup_files_per_iteration );

	if ( Number.isFinite( iterations ) && iterations > 0 ) {
		return Math.floor( iterations );
	}

	return SLOW_ITERATIONS;
};

const getSpeedModeFromIterations = ( iterations ) => {
	if ( iterations === SLOW_ITERATIONS ) {
		return 'slow';
	}
	if ( iterations === FAST_ITERATIONS ) {
		return 'fast';
	}
	return 'custom';
};

/**
 * BackupSettingsCard Component
 * Schedule + auto-cleanup + speed controls
 */
function BackupSettingsCard( {
	settings,
	onSettingsChange,
	disabled = false,
	showProBadge,
} ) {
	const initialIterations = getIterationsFromSettings( settings );
	const initialSpeedMode = getSpeedModeFromIterations( initialIterations );
	const [ speedMode, setSpeedMode ] = useState( initialSpeedMode );
	const [ lastCustomIterations, setLastCustomIterations ] = useState( () =>
		initialSpeedMode === 'custom'
			? initialIterations
			: DEFAULT_CUSTOM_ITERATIONS
	);
	const [ customSpeed, setCustomSpeed ] = useState( () =>
		initialSpeedMode === 'custom'
			? initialIterations * ITERATIONS_TO_FPM
			: DEFAULT_CUSTOM_SPEED_FPM
	);

	// Sync local speed state when settings change externally
	useEffect( () => {
		const iterations = getIterationsFromSettings( settings );
		const nextMode = getSpeedModeFromIterations( iterations );
		setSpeedMode( nextMode );

		// Preserve previous custom value when toggling to slow/fast.
		if ( nextMode === 'custom' ) {
			setLastCustomIterations( iterations );
			setCustomSpeed( iterations * ITERATIONS_TO_FPM );
		}
	}, [ settings.backup_files_per_iteration ] );

	const handleScheduleChange = ( value ) => {
		if ( disabled ) return;
		onSettingsChange( { ...settings, schedule_backup: value } );
	};

	const handleRemoveOldDataChange = ( value ) => {
		if ( disabled ) return;
		onSettingsChange( { ...settings, remove_old_data: value } );
	};

	const handleSpeedModeChange = ( mode ) => {
		if ( disabled ) return;
		setSpeedMode( mode );
		if ( mode === 'slow' ) {
			onSettingsChange( {
				...settings,
				backup_files_per_iteration: SLOW_ITERATIONS,
			} );
		} else if ( mode === 'fast' ) {
			onSettingsChange( {
				...settings,
				backup_files_per_iteration: FAST_ITERATIONS,
			} );
		} else if ( mode === 'custom' ) {
			const iterationsToRestore =
				lastCustomIterations || DEFAULT_CUSTOM_ITERATIONS;
			setCustomSpeed( iterationsToRestore * ITERATIONS_TO_FPM );
			onSettingsChange( {
				...settings,
				backup_files_per_iteration: iterationsToRestore,
			} );
		}
	};

	const handleCustomSpeedApply = () => {
		const value = Math.max(
			MIN_SPEED_FPM,
			parseInt( customSpeed, 10 ) || MIN_SPEED_FPM
		);
		const iterations = Math.floor( value / ITERATIONS_TO_FPM );
		setCustomSpeed( value );
		setLastCustomIterations( iterations );
		onSettingsChange( {
			...settings,
			backup_files_per_iteration: iterations,
		} );
	};

	return (
		<Box
			bg="white"
			p={ 6 }
			borderRadius="md"
			borderWidth="1px"
			borderColor="gray.200"
		>
			<Text fontSize="lg" fontWeight="semibold" color="gray.900">
				{ __( 'Backup Settings', 'anti-spam' ) }
			</Text>

			{ /* Schedule */ }
			<Flex
				align={ { base: 'flex-start', md: 'center' } }
				justify="space-between"
				direction={ { base: 'column', md: 'row' } }
				gap={ { base: 3, md: 0 } }
				py={ 4 }
				borderBottom="1px solid"
				borderColor="gray.100"
			>
				<Box>
					<Text
						fontWeight="600"
						color="gray.800"
						fontSize="15px"
						mb={ 1 }
					>
						{ __( 'Backup Schedule', 'anti-spam' ) }
					</Text>
					<Text fontSize="sm" color="gray.600">
						{ __(
							'Choose how often to create automatic backups.',
							'anti-spam'
						) }
					</Text>
				</Box>
				<SegmentedButton
					options={ SCHEDULE_OPTIONS }
					value={ settings.schedule_backup || 'off' }
					onChange={ handleScheduleChange }
					disabled={ disabled }
				/>
			</Flex>

			{ /* Remove old data */ }
			<Box borderBottom="1px solid" borderColor="gray.100">
				<SettingToggle
					label={ __( 'Remove Old Archives', 'anti-spam' ) }
					description={ __(
						'Automatically remove backup archives older than 7 days.',
						'anti-spam'
					) }
					enabled={ settings.remove_old_data || false }
					onChange={ handleRemoveOldDataChange }
					disabled={ disabled }
					showProBadge={ showProBadge }
				/>
			</Box>

			{ /* Speed */ }
			<Box pt={ 4 }>
				<Flex
					align={ { base: 'flex-start', md: 'center' } }
					justify="space-between"
					direction={ { base: 'column', md: 'row' } }
					gap={ { base: 3, md: 0 } }
				>
					<Box>
						<Text
							fontWeight="600"
							color="gray.800"
							fontSize="15px"
							mb={ 1 }
						>
							{ __( 'Backup Speed', 'anti-spam' ) }
						</Text>
						<Text fontSize="sm" color="gray.600">
							{ __(
								'Higher speed uses more server resources.',
								'anti-spam'
							) }
						</Text>
					</Box>
					<SegmentedButton
						options={ SPEED_OPTIONS }
						value={ speedMode }
						onChange={ handleSpeedModeChange }
						disabled={ disabled }
					/>
				</Flex>
				{ speedMode === 'custom' && (
					<Flex align="center" gap={ 2 } mt={ 3 } justify="flex-end">
						<Input
							type="number"
							value={ customSpeed }
							onChange={ ( e ) =>
								setCustomSpeed( e.target.value )
							}
							onBlur={ handleCustomSpeedApply }
							onKeyDown={ ( e ) => {
								if ( e.key === 'Enter' ) {
									handleCustomSpeedApply();
								}
							} }
							min={ MIN_SPEED_FPM }
							size="sm"
							w="120px"
							borderRadius="md"
							disabled={ disabled }
						/>
						<Text
							fontSize="xs"
							color="gray.500"
							whiteSpace="nowrap"
						>
							{ __( 'files/min', 'anti-spam' ) }
						</Text>
					</Flex>
				) }
			</Box>
		</Box>
	);
}

export default BackupSettingsCard;
