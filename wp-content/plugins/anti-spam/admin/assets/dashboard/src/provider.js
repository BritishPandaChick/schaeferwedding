import {
	ChakraProvider,
	createSystem,
	defaultConfig,
	defineConfig,
} from '@chakra-ui/react';

import { createContext, useState, useEffect } from '@wordpress/element';
import { Toaster } from './components/Toaster';
import { getSettings as fetchSettings } from './services/api';

export const AppContext = createContext();

// Titan Security brand colors (using purple/blue theme)
const mainBrandColor = '#6366f1';

const config = defineConfig( {
	theme: {
		tokens: {
			colors: {
				purple: {
					50: { value: '#eef2ff' },
					100: { value: '#e0e7ff' },
					200: { value: '#c7d2fe' },
					300: { value: '#a5b4fc' },
					400: { value: '#818cf8' },
					500: { value: '#6366f1' },
					600: { value: mainBrandColor },
					700: { value: '#4f46e5' },
					800: { value: '#4338ca' },
					900: { value: '#3730a3' },
					950: { value: '#1e1b4b' },
				},
				colorPalette: {
					solid: { value: mainBrandColor },
				},
			},
		},
	},
} );

const system = createSystem( defaultConfig, config );

export const Provider = ( props ) => {
	const { children, ...rest } = props;

	const [ settings, setSettings ] = useState(
		window.titanSecurityObjects?.settings || {}
	);

	const [ isLoadingSettings, setIsLoadingSettings ] = useState( false );

	const [ license, setLicense ] = useState(
		window.titanSecurityObjects?.license || {}
	);

	const [ scanData, setScanData ] = useState(
		window.titanSecurityObjects?.scanStatus || {}
	);

	const [ auditData, setAuditData ] = useState(
		window.titanSecurityObjects?.audit || {
			items: [],
			count: 0,
			hidden_items: [],
			hided_count: 0,
			vulnerabilities: 0,
		}
	);

	const [ vulnerabilityData, setVulnerabilityData ] = useState(
		window.titanSecurityObjects?.vulnerabilities || {}
	);

	const [ twoFactorData, setTwoFactorData ] = useState(
		window.titanSecurityObjects?.twoFactor || {
			available: false,
			enabled: false,
			setup_complete: false,
			qr_value: '',
			secret_display: '',
			restore_codes: [],
			ip_whitelist: [],
		}
	);

	const isLicenseActive = 'valid' === license?.status;

	// Fetch settings on mount
	useEffect( () => {
		const loadSettings = async () => {
			setIsLoadingSettings( true );
			try {
				const fetchedSettings = await fetchSettings();
				setSettings( fetchedSettings );
			} catch ( error ) {
				console.error( 'Failed to load settings:', error );
			} finally {
				setIsLoadingSettings( false );
			}
		};
		loadSettings();
	}, [] );

	return (
		<ChakraProvider value={ system }>
			<AppContext.Provider
				value={ {
					settings,
					setSettings,
					isLoadingSettings,
					license,
					setLicense,
					isLicenseActive,
					scanData,
					setScanData,
					auditData,
					setAuditData,
					vulnerabilityData,
					setVulnerabilityData,
					twoFactorData,
					setTwoFactorData,
				} }
				{ ...rest }
			>
				{ children }
				<Toaster />
			</AppContext.Provider>
		</ChakraProvider>
	);
};
