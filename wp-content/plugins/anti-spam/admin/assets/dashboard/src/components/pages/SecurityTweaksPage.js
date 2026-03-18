import { __ } from '@wordpress/i18n';
import { useState, useContext, useEffect } from '@wordpress/element';
import { Box, VStack, Text } from '@chakra-ui/react';
import {
	SettingToggle,
	DropdownControl,
	TextareaControl,
} from '../common/SettingsControls';
import PageHeader from '../common/PageHeader';
import { AppContext } from '../../provider';
import { saveSettings } from '../../services/api';
import { toaster } from '../Toaster';

/**
 * SecurityTweaksPage Component
 * Security tweaks and hardening settings
 */
function SecurityTweaksPage() {
	const { settings, setSettings, isLoadingSettings } =
		useContext( AppContext );

	// Base settings
	const [ strongPassword, setStrongPassword ] = useState(
		Boolean( settings.strong_password )
	);
	const [ strongPasswordMinRole, setStrongPasswordMinRole ] = useState(
		settings.strong_password_min_role ?? 'administrator'
	);
	const [ protectAuthorGet, setProtectAuthorGet ] = useState(
		Boolean( settings.protect_author_get )
	);
	const [ removeXPingback, setRemoveXPingback ] = useState(
		Boolean( settings.remove_x_pingback )
	);

	// Hide WordPress versions
	const [ removeHtmlComments, setRemoveHtmlComments ] = useState(
		Boolean( settings.remove_html_comments )
	);
	const [ removeMetaGenerator, setRemoveMetaGenerator ] = useState(
		Boolean( settings.remove_meta_generator )
	);
	const [ removeJsVersion, setRemoveJsVersion ] = useState(
		Boolean( settings.remove_js_version )
	);
	const [ removeStyleVersion, setRemoveStyleVersion ] = useState(
		Boolean( settings.remove_style_version )
	);
	const [ removeVersionExclude, setRemoveVersionExclude ] = useState(
		settings.remove_version_exclude ?? ''
	);

	const [ isSaving, setIsSaving ] = useState( false );

	// Update local state when settings change
	useEffect( () => {
		if ( ! isLoadingSettings ) {
			setStrongPassword( Boolean( settings.strong_password ) );
			setStrongPasswordMinRole(
				settings.strong_password_min_role ?? 'administrator'
			);
			setProtectAuthorGet( Boolean( settings.protect_author_get ) );
			setRemoveXPingback( Boolean( settings.remove_x_pingback ) );
			setRemoveHtmlComments( Boolean( settings.remove_html_comments ) );
			setRemoveMetaGenerator( Boolean( settings.remove_meta_generator ) );
			setRemoveJsVersion( Boolean( settings.remove_js_version ) );
			setRemoveStyleVersion( Boolean( settings.remove_style_version ) );
			setRemoveVersionExclude( settings.remove_version_exclude ?? '' );
		}
	}, [ settings, isLoadingSettings ] );

	const handleSave = async () => {
		setIsSaving( true );
		try {
			const data = {
				strong_password: strongPassword,
				strong_password_min_role: strongPasswordMinRole,
				protect_author_get: protectAuthorGet,
				remove_x_pingback: removeXPingback,
				remove_html_comments: removeHtmlComments,
				remove_meta_generator: removeMetaGenerator,
				remove_js_version: removeJsVersion,
				remove_style_version: removeStyleVersion,
				remove_version_exclude: removeVersionExclude,
			};

			const response = await saveSettings( data );

			if ( response.success ) {
				setSettings( { ...settings, ...data } );
				toaster.success( {
					title: __( 'Settings saved', 'anti-spam' ),
					description:
						response.message ||
						__(
							'Your security settings have been saved successfully.',
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
				title={ __( 'Settings', 'anti-spam' ) }
				description={ __(
					'Configure security hardening and protection settings',
					'anti-spam'
				) }
				onSave={ handleSave }
				isSaving={ isSaving }
				isDisabled={ isLoadingSettings }
			/>

			<VStack gap={ 6 } align="stretch">
				{ /* Base Settings Section */ }
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
						<Text
							fontSize="lg"
							fontWeight="semibold"
							color="gray.900"
						>
							{ __( 'Base Settings', 'anti-spam' ) }
						</Text>
						<Text fontSize="sm" color="gray.600" mt={ 1 }>
							{ __(
								'Basic recommended security settings.',
								'anti-spam'
							) }
						</Text>
					</Box>
					<Box px={ 6 }>
						<SettingToggle
							label={ __(
								'Strong Password Requirement',
								'anti-spam'
							) }
							description={ __(
								'Force users to use strong passwords as rated by the WordPress password meter.',
								'anti-spam'
							) }
							enabled={ strongPassword }
							onChange={ setStrongPassword }
						/>
						{ strongPassword && (
							<>
								<Box
									borderTopWidth="1px"
									borderColor="gray.100"
								/>
								<DropdownControl
									label={ __(
										'Strong Password Minimum Role',
										'anti-spam'
									) }
									description={ __(
										'Minimum role at which a user must choose a strong password. Warning: If your site invites public registrations, setting the role too low may annoy your members.',
										'anti-spam'
									) }
									value={ strongPasswordMinRole }
									onChange={ setStrongPasswordMinRole }
									options={ [
										[ 'administrator', 'Administrator' ],
										[ 'editor', 'Editor' ],
										[ 'author', 'Author' ],
										[ 'contributor', 'Contributor' ],
										[ 'subscriber', 'Subscriber' ],
									] }
								/>
							</>
						) }
						<Box borderTopWidth="1px" borderColor="gray.100" />
						<SettingToggle
							label={ __( 'Hide author login', 'anti-spam' ) }
							description={ __(
								"An attacker can find out the author's login, using a similar request to get your site. mysite.com/?author=1. Titan sets the redirect to exclude the possibility of obtaining a login.",
								'anti-spam'
							) }
							enabled={ protectAuthorGet }
							onChange={ setProtectAuthorGet }
						/>
						<Box borderTopWidth="1px" borderColor="gray.100" />
						<Box>
							<SettingToggle
								label={ __( 'Disable XML-RPC', 'anti-spam' ) }
								description={ __(
									'A pingback is basically an automated comment that gets created when another blog links to you. Pingbacks are essentially nothing more than spam and simply waste resources.',
									'anti-spam'
								) }
								enabled={ removeXPingback }
								onChange={ setRemoveXPingback }
							/>
						</Box>
					</Box>
				</Box>

				{ /* Hide WordPress Versions Section */ }
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
						<Text
							fontSize="lg"
							fontWeight="semibold"
							color="gray.900"
						>
							{ __( 'Hide WordPress Versions', 'anti-spam' ) }
						</Text>
						<Text fontSize="sm" color="gray.600" mt={ 1 }>
							{ __(
								'WordPress itself and many plugins show their version at the public areas of your site. An attacker receiving this information may be aware of vulnerabilities found in the version of the WordPress core or plugins.',
								'anti-spam'
							) }
						</Text>
					</Box>
					<Box px={ 6 }>
						<SettingToggle
							label={ __( 'Remove HTML comments', 'anti-spam' ) }
							description={ __(
								'This function will remove all HTML comments in the source code, except for special and hidden comments. This is necessary to hide the version of installed plugins.',
								'anti-spam'
							) }
							enabled={ removeHtmlComments }
							onChange={ setRemoveHtmlComments }
						/>
						<Box borderTopWidth="1px" borderColor="gray.100" />
						<SettingToggle
							label={ __( 'Remove meta generator', 'anti-spam' ) }
							description={ __(
								'Allows attacker to learn the version of WP installed on the site. This meta tag has no useful function. Titan removes the meta tag from the <head> section.',
								'anti-spam'
							) }
							enabled={ removeMetaGenerator }
							onChange={ setRemoveMetaGenerator }
							recommended
						/>
						<Box borderTopWidth="1px" borderColor="gray.100" />
						<SettingToggle
							label={ __(
								'Remove Version from Script',
								'anti-spam'
							) }
							description={ __(
								'Removes the version query string (e.g. ?ver=6.4) from enqueued JavaScript files. This prevents attackers from identifying the exact WordPress or plugin version through JS file URLs, and allows browsers to cache the files more effectively.',
								'anti-spam'
							) }
							enabled={ removeJsVersion }
							onChange={ setRemoveJsVersion }
							recommended
						/>
						<Box borderTopWidth="1px" borderColor="gray.100" />
						<SettingToggle
							label={ __(
								'Remove Version from Stylesheet',
								'anti-spam'
							) }
							description={ __(
								'Removes the version query string (e.g. ?ver=6.4) from enqueued CSS files. This prevents attackers from fingerprinting your WordPress or plugin version through stylesheet URLs, and improves CSS caching in browsers.',
								'anti-spam'
							) }
							enabled={ removeStyleVersion }
							onChange={ setRemoveStyleVersion }
							recommended
						/>
						<Box borderTopWidth="1px" borderColor="gray.100" />
						<TextareaControl
							label={ __(
								'Exclude stylesheet/script file names',
								'anti-spam'
							) }
							description={ __(
								'Enter Stylesheet/Script file names to exclude from version removal (each exclude file starts with a new line). Example: http://testwp.dev/wp-includes/js/jquery/jquery.js',
								'anti-spam'
							) }
							value={ removeVersionExclude }
							onChange={ setRemoveVersionExclude }
							placeholder={ __(
								'Enter file names (one per line)',
								'anti-spam'
							) }
						/>
					</Box>
				</Box>
			</VStack>
		</Box>
	);
}

export default SecurityTweaksPage;
