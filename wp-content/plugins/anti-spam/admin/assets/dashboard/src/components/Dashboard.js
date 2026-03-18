import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Box, Flex, Heading, IconButton, Text, VStack } from '@chakra-ui/react';
import { useRouting } from '../hooks/useRouting';

// Import page components
import DashboardPage from './pages/DashboardPage';
import AntiSpamPage from './pages/AntiSpamPage';
import SecurityTweaksPage from './pages/SecurityTweaksPage';
import LimitLoginAttemptsPage from './pages/LimitLoginAttemptsPage';
import LoginAttemptsLogPage from './pages/LoginAttemptsLogPage';
import SettingsPage from './pages/SettingsPage';
import LogsPage from './pages/LogsPage';
import BackupPage from './pages/BackupPage';
import TwoFactorPage from './pages/TwoFactorPage';
import NavItem from './NavItem';

/**
 * Dashboard Component
 * Main application container with header, sidebar navigation, and page routing
 */
function Dashboard() {
	const { currentPage, navigateTo } = useRouting( 'dashboard' );
	const [ isSidebarOpen, setIsSidebarOpen ] = useState( false );

	const renderPage = () => {
		switch ( currentPage ) {
			case 'dashboard':
				return <DashboardPage setCurrentPage={ navigateTo } />;
			case 'antispam':
				return <AntiSpamPage />;
			case 'security-settings':
				return <SecurityTweaksPage />;
			case 'security-limit-login':
				return <LimitLoginAttemptsPage />;
			case 'login-attempts-log':
				return <LoginAttemptsLogPage />;
			case 'settings':
				return <SettingsPage />;
			case 'logs':
				return <LogsPage />;
			case 'backup':
				return <BackupPage />;
			case 'twofactor':
				return <TwoFactorPage />;
			default:
				return <DashboardPage setCurrentPage={ navigateTo } />;
		}
	};

	const handleNav = ( page ) => {
		navigateTo( page );
		setIsSidebarOpen( false );
	};

	const navItems = (
		<>
			<NavItem
				icon="activity"
				label={ __( 'Dashboard', 'anti-spam' ) }
				active={ currentPage === 'dashboard' }
				onClick={ () => handleNav( 'dashboard' ) }
			/>
			<NavItem
				icon="shield"
				label={ __( 'Anti-Spam', 'anti-spam' ) }
				active={ currentPage === 'antispam' }
				onClick={ () => handleNav( 'antispam' ) }
			/>
			<NavItem
				icon="database"
				label={ __( 'Backup', 'anti-spam' ) }
				active={ currentPage === 'backup' }
				onClick={ () => handleNav( 'backup' ) }
			/>
			<NavItem
				icon="key"
				label={ __( 'Two-Factor', 'anti-spam' ) }
				active={ currentPage === 'twofactor' }
				onClick={ () => handleNav( 'twofactor' ) }
			/>
			<NavItem
				icon="lock"
				label={ __( 'Security', 'anti-spam' ) }
				active={
					currentPage === 'security-settings' ||
					currentPage === 'security-limit-login' ||
					currentPage === 'login-attempts-log'
				}
				subItems={ [
					{
						label: __( 'Settings', 'anti-spam' ),
						active: currentPage === 'security-settings',
						onClick: () => handleNav( 'security-settings' ),
					},
					{
						label: __( 'Limit Login Attempts', 'anti-spam' ),
						active: currentPage === 'security-limit-login',
						onClick: () => handleNav( 'security-limit-login' ),
					},
					{
						label: __( 'Login Attempts Log', 'anti-spam' ),
						active: currentPage === 'login-attempts-log',
						onClick: () => handleNav( 'login-attempts-log' ),
					},
				] }
			/>
			<NavItem
				icon="list"
				label={ __( 'Error Log', 'anti-spam' ) }
				active={ currentPage === 'logs' }
				onClick={ () => handleNav( 'logs' ) }
			/>
			<NavItem
				icon="settings"
				label={ __( 'Settings', 'anti-spam' ) }
				active={ currentPage === 'settings' }
				onClick={ () => handleNav( 'settings' ) }
			/>
		</>
	);

	return (
		<Box minH="100vh" bg="gray.50">
			<Box
				bg="white"
				borderBottom="1px solid"
				borderColor="gray.200"
				position={ { base: 'static', lg: 'sticky' } }
				top={ { lg: '32px' } }
				zIndex={ 10 }
			>
				<Box px={ { base: 3, md: 6 } } py={ 4 }>
					<Flex align="center" justify="space-between">
						<Flex align="center" gap={ 3 }>
							<IconButton
								display={ { base: 'flex', lg: 'none' } }
								aria-label={ __( 'Open menu', 'anti-spam' ) }
								variant="ghost"
								size="sm"
								onClick={ () =>
									setIsSidebarOpen( ! isSidebarOpen )
								}
							>
								<svg
									width="20"
									height="20"
									viewBox="0 0 24 24"
									fill="none"
									xmlns="http://www.w3.org/2000/svg"
								>
									{ isSidebarOpen ? (
										<path
											d="M18 6L6 18M6 6l12 12"
											stroke="currentColor"
											strokeWidth="2"
											strokeLinecap="round"
											strokeLinejoin="round"
										/>
									) : (
										<path
											d="M3 12h18M3 6h18M3 18h18"
											stroke="currentColor"
											strokeWidth="2"
											strokeLinecap="round"
											strokeLinejoin="round"
										/>
									) }
								</svg>
							</IconButton>
							<svg
								width="32"
								height="32"
								viewBox="0 0 24 24"
								fill="none"
								xmlns="http://www.w3.org/2000/svg"
							>
								<path
									d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"
									stroke="#6366f1"
									strokeWidth="2"
									strokeLinecap="round"
									strokeLinejoin="round"
								/>
							</svg>
							<Box>
								<Heading
									size="md"
									fontWeight="semibold"
									color="gray.900"
								>
									{ __(
										'Titan Anti-spam & Security',
										'anti-spam'
									) }
								</Heading>
								<Text fontSize="sm" color="gray.500">
									{ __( 'Version', 'anti-spam' ) }{ ' ' }
									{ window.titanSecurityObjects?.version }
								</Text>
							</Box>
						</Flex>
					</Flex>
				</Box>
			</Box>

			{ /* Mobile Overlay Menu */ }
			{ isSidebarOpen && (
				<>
					<Box
						display={ { base: 'block', lg: 'none' } }
						position="fixed"
						top="46px"
						left={ 0 }
						right={ 0 }
						bottom={ 0 }
						bg="blackAlpha.600"
						zIndex={ 19 }
						onClick={ () => setIsSidebarOpen( false ) }
					/>
					<Box
						display={ { base: 'block', lg: 'none' } }
						position="fixed"
						top="46px"
						left={ 0 }
						bottom={ 0 }
						w="280px"
						bg="white"
						zIndex={ 20 }
						boxShadow="lg"
						overflowY="auto"
					>
						<Flex
							align="center"
							justify="space-between"
							px={ 4 }
							py={ 4 }
							borderBottom="1px solid"
							borderColor="gray.200"
						>
							<Flex align="center" gap={ 2 }>
								<svg
									width="24"
									height="24"
									viewBox="0 0 24 24"
									fill="none"
									xmlns="http://www.w3.org/2000/svg"
								>
									<path
										d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"
										stroke="#6366f1"
										strokeWidth="2"
										strokeLinecap="round"
										strokeLinejoin="round"
									/>
								</svg>
								<Text
									fontWeight="semibold"
									fontSize="sm"
									color="gray.900"
								>
									{ __( 'Menu', 'anti-spam' ) }
								</Text>
							</Flex>
							<IconButton
								aria-label={ __( 'Close menu', 'anti-spam' ) }
								variant="ghost"
								size="sm"
								onClick={ () => setIsSidebarOpen( false ) }
							>
								<svg
									width="18"
									height="18"
									viewBox="0 0 24 24"
									fill="none"
									xmlns="http://www.w3.org/2000/svg"
								>
									<path
										d="M18 6L6 18M6 6l12 12"
										stroke="currentColor"
										strokeWidth="2"
										strokeLinecap="round"
										strokeLinejoin="round"
									/>
								</svg>
							</IconButton>
						</Flex>
						<VStack as="nav" p={ 4 } spacing={ 1 } align="stretch">
							{ navItems }
						</VStack>
					</Box>
				</>
			) }

			<Flex>
				{ /* Desktop Sidebar */ }
				<Box
					display={ { base: 'none', lg: 'block' } }
					w="256px"
					bg="white"
					borderRight="1px solid"
					borderColor="gray.200"
					minH="calc(100vh - 104px)"
					flexShrink={ 0 }
				>
					<VStack as="nav" p={ 4 } spacing={ 1 } align="stretch">
						{ navItems }
					</VStack>
				</Box>

				<Box
					as="main"
					flex="1"
					p={ { base: 3, md: 6 } }
					maxW="1680px"
					minW={ 0 }
				>
					{ renderPage() }
				</Box>
			</Flex>
		</Box>
	);
}

export default Dashboard;
