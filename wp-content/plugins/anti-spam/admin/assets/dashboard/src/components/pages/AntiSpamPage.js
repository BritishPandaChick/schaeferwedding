import { __ } from '@wordpress/i18n';
import { useState, useContext, useEffect } from '@wordpress/element';
import { Box, Text, Button } from '@chakra-ui/react';
import { SettingToggle } from '../common/SettingsControls';
import PageHeader from '../common/PageHeader';
import { AppContext } from '../../provider';
import { saveSettings } from '../../services/api';
import { toaster } from '../Toaster';

/**
 * AntiSpamPage Component
 * Anti-spam settings and configuration
 */
function AntiSpamPage() {
	const { settings, setSettings, isLicenseActive, isLoadingSettings } =
		useContext( AppContext );

	const [ antiSpamMode, setAntiSpamMode ] = useState(
		Boolean( settings.antispam_mode ?? true )
	);
	const [ saveComments, setSaveComments ] = useState(
		Boolean( settings.save_spam_comments ?? true )
	);
	const [ privacyNotice, setPrivacyNotice ] = useState(
		Boolean( settings.comment_form_privacy_notice )
	);
	const [ advancedSpamFilter, setAdvancedSpamFilter ] = useState(
		Boolean( settings.advanced_spam_filter )
	);
	const [ isSaving, setIsSaving ] = useState( false );
	const hasPrivacyPolicyPage = settings.has_privacy_policy_page ?? true;
	const privacySettingsUrl =
		window.titanSecurityObjects?.privacySettingsUrl || '';

	// Update local state when settings change
	useEffect( () => {
		if ( ! isLoadingSettings ) {
			setAntiSpamMode( Boolean( settings.antispam_mode ?? true ) );
			setSaveComments( Boolean( settings.save_spam_comments ?? true ) );
			setPrivacyNotice( Boolean( settings.comment_form_privacy_notice ) );
			setAdvancedSpamFilter( Boolean( settings.advanced_spam_filter ) );
		}
	}, [ settings, isLoadingSettings ] );

	const handleSave = async () => {
		setIsSaving( true );
		try {
			const data = {
				antispam_mode: antiSpamMode,
				save_spam_comments: saveComments,
				comment_form_privacy_notice: privacyNotice,
				advanced_spam_filter: advancedSpamFilter,
			};

			const response = await saveSettings( data );

			if ( response.success ) {
				setSettings( { ...settings, ...data } );
				toaster.success( {
					title: __( 'Settings saved', 'anti-spam' ),
					description:
						response.message ||
						__(
							'Your anti-spam settings have been saved successfully.',
							'anti-spam'
						),
				} );
			}
		} catch ( error ) {
			toaster.error( {
				title: __( 'Error saving settings', 'anti-spam' ),
				description:
					error.message ||
					__(
						'Failed to save settings. Please try again.',
						'anti-spam'
					),
			} );
		} finally {
			setIsSaving( false );
		}
	};

	return (
		<Box>
			<PageHeader
				title={ __( 'Anti-spam Settings', 'anti-spam' ) }
				description={ __(
					'Configure spam protection for your site',
					'anti-spam'
				) }
				onSave={ handleSave }
				isSaving={ isSaving }
				isDisabled={ isLoadingSettings }
			/>

			<Box
				bg="white"
				borderRadius="lg"
				borderWidth="1px"
				borderColor="gray.200"
				overflow="hidden"
			>
				<Box
					px={ 6 }
					py={ 4 }
					borderBottomWidth="1px"
					borderColor="gray.100"
				>
					<Text fontSize="lg" fontWeight="semibold" color="gray.900">
						{ __( 'Base Options', 'anti-spam' ) }
					</Text>
					<Text fontSize="sm" color="gray.600" mt={ 1 }>
						{ __(
							'Configure basic anti-spam settings for your site.',
							'anti-spam'
						) }
					</Text>
				</Box>

				<Box px={ 6 }>
					<SettingToggle
						label={ __( 'Anti-spam mode', 'anti-spam' ) }
						description={ __(
							'Enable or disable spam protection for all site.',
							'anti-spam'
						) }
						enabled={ antiSpamMode }
						onChange={ setAntiSpamMode }
					/>

					<Box borderTopWidth="1px" borderColor="gray.100" />

					<SettingToggle
						label={ __( 'Save spam comments', 'anti-spam' ) }
						description={ __(
							'Save spam comments into spam section. Useful for testing how the plugin works.',
							'anti-spam'
						) }
						enabled={ saveComments }
						onChange={ setSaveComments }
					/>

					<Box borderTopWidth="1px" borderColor="gray.100" />

					{ hasPrivacyPolicyPage ? (
						<SettingToggle
							label={ __(
								'Show privacy policy link under your comment forms',
								'anti-spam'
							) }
							description={ __(
								'Display a link to your site Privacy Policy page under comment forms to explain how comment data may be processed.',
								'anti-spam'
							) }
							enabled={ privacyNotice }
							onChange={ setPrivacyNotice }
						/>
					) : (
						<>
							<SettingToggle
								label={ __(
									'Show privacy policy link under your comment forms',
									'anti-spam'
								) }
								description={ __(
									'Your site needs a Privacy Policy page before this option can be used.',
									'anti-spam'
								) }
								enabled={ false }
								onChange={ setPrivacyNotice }
								disabled={ true }
								showProBadge={ false }
							/>
							{ privacySettingsUrl ? (
								<Box pb={ 5 }>
									<Button
										as="a"
										href={ privacySettingsUrl }
										size="sm"
										colorScheme="purple"
										variant="outline"
									>
										{ __(
											'Open Privacy Settings',
											'anti-spam'
										) }
									</Button>
								</Box>
							) : null }
						</>
					) }

					<Box borderTopWidth="1px" borderColor="gray.100" />

					<SettingToggle
						label={ __(
							'Analyze comments for spam using Machine Learning',
							'anti-spam'
						) }
						description={ __(
							'Use advanced Machine Learning algorithms to detect spam comments with higher accuracy. Mark comments as spam based on patterns learned from vast datasets.',
							'anti-spam'
						) }
						enabled={ advancedSpamFilter }
						onChange={ setAdvancedSpamFilter }
						disabled={ ! isLicenseActive }
					/>
				</Box>
			</Box>
		</Box>
	);
}

export default AntiSpamPage;
