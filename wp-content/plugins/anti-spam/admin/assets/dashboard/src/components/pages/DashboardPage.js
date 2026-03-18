import { __ } from '@wordpress/i18n';
import { useContext, useState } from '@wordpress/element';
import { VStack } from '@chakra-ui/react';
import AntiSpamCard from '../AntiSpamCard';
import Audit from '../Audit';
import CachingTipBanner from '../CachingTipBanner';
import { AppContext } from '../../provider';

/**
 * DashboardPage Component
 */
function DashboardPage( { setCurrentPage } ) {
	const { settings } = useContext( AppContext );
	const cachingTip = window.titanSecurityObjects?.cachingTip;
	const [ showCachingTip, setShowCachingTip ] = useState(
		!! cachingTip?.show
	);

	return (
		<VStack gap={ 6 } align="stretch">
			{ showCachingTip && (
				<CachingTipBanner
					pluginPath={ cachingTip.pluginPath }
					pluginSlug={ cachingTip.pluginSlug }
					learnMoreUrl={ cachingTip.learnMoreUrl }
					isInstalled={ cachingTip.isInstalled }
					onDismiss={ () => setShowCachingTip( false ) }
				/>
			) }

			<AntiSpamCard
				stats={ titanSecurityObjects?.stats }
				isActive={ !! settings?.antispam_mode }
				onConfigure={ () => setCurrentPage( 'antispam' ) }
			/>

			<Audit />
		</VStack>
	);
}

export default DashboardPage;
