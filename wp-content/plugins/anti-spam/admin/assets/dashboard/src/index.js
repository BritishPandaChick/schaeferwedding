import { createRoot } from '@wordpress/element';

import { Provider } from './provider';

import Dashboard from './components/Dashboard';

import './style.scss';

const root = createRoot(
	document.getElementById( 'titan-security-dashboard' )
);
root.render(
	<Provider>
		<Dashboard />
	</Provider>
);
