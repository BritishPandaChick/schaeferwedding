/**
 * API service for Titan Security Dashboard
 */

import apiFetch from '@wordpress/api-fetch';

const API_NAMESPACE = 'titan-security/v1';

/**
 * Get all settings
 *
 * @returns {Promise<Object>} Settings object
 */
export const getSettings = async () => {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/settings`,
			method: 'GET',
		} );
		return response.settings || {};
	} catch ( error ) {
		console.error( 'Failed to fetch settings:', error );
		throw error;
	}
};

/**
 * Save settings
 *
 * @param {Object} data - Settings data to save
 * @returns {Promise<Object>} Response object
 */
export const saveSettings = async ( data ) => {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/settings`,
			method: 'POST',
			data: { data },
		} );
		return response;
	} catch ( error ) {
		console.error( 'Failed to save settings:', error );
		throw error;
	}
};

/**
 * Get logs content
 *
 * @returns {Promise<Object>} Logs data
 */
export const getLogs = async () => {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/logs`,
			method: 'GET',
		} );
		return response;
	} catch ( error ) {
		console.error( 'Failed to fetch logs:', error );
		throw error;
	}
};

/**
 * Clean up logs
 *
 * @returns {Promise<Object>} Response object
 */
export const cleanLogs = async () => {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/logs/clean`,
			method: 'POST',
		} );
		return response;
	} catch ( error ) {
		console.error( 'Failed to clean logs:', error );
		throw error;
	}
};

/**
 * Export logs
 *
 * @returns {Promise<Object>} Response with download URL
 */
export const exportLogs = async () => {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/logs/export`,
			method: 'GET',
		} );
		return response;
	} catch ( error ) {
		console.error( 'Failed to export logs:', error );
		throw error;
	}
};

// ── Backup API ──────────────────────────────────────────────────────────────

/**
 * Start a new backup
 *
 * @returns {Promise<Object>} Response
 */
export const startBackup = async () => {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/backup/start`,
			method: 'POST',
		} );
		return response;
	} catch ( error ) {
		console.error( 'Failed to start backup:', error );
		throw error;
	}
};

/**
 * Abort an in-progress backup
 *
 * @returns {Promise<Object>} Response
 */
export const abortBackup = async () => {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/backup/abort`,
			method: 'POST',
		} );
		return response;
	} catch ( error ) {
		console.error( 'Failed to abort backup:', error );
		throw error;
	}
};

/**
 * Get backup progress
 *
 * @returns {Promise<Object>} Progress data
 */
export const getBackupProgress = async () => {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/backup/progress`,
			method: 'GET',
		} );
		return response;
	} catch ( error ) {
		console.error( 'Failed to fetch backup progress:', error );
		throw error;
	}
};

/**
 * Get list of existing backups
 *
 * @returns {Promise<Array>} Backups list
 */
export const getBackupList = async () => {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/backup/list`,
			method: 'GET',
		} );
		return response.backups || [];
	} catch ( error ) {
		console.error( 'Failed to fetch backup list:', error );
		throw error;
	}
};

/**
 * Delete a backup
 *
 * @param {string} date - Backup date identifier
 * @returns {Promise<Object>} Response
 */
export const deleteBackup = async ( date ) => {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/backup/delete`,
			method: 'POST',
			data: { date },
		} );
		return response;
	} catch ( error ) {
		console.error( 'Failed to delete backup:', error );
		throw error;
	}
};

/**
 * Get backup download URL
 *
 * @param {string} date - Backup date identifier
 * @returns {Promise<Object>} Response with URL
 */
export const getBackupDownloadUrl = async ( date ) => {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/backup/download?date=${ encodeURIComponent(
				date
			) }`,
			method: 'GET',
		} );
		return response;
	} catch ( error ) {
		console.error( 'Failed to get backup download URL:', error );
		throw error;
	}
};

/**
 * Save storage configuration
 *
 * @param {string} store - Storage type (ftp, dropbox)
 * @param {Object} config - Storage config data
 * @returns {Promise<Object>} Response
 */
export const saveStorageConfig = async ( store, config ) => {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/backup/storage`,
			method: 'POST',
			data: { store, config },
		} );
		return response;
	} catch ( error ) {
		console.error( 'Failed to save storage config:', error );
		throw error;
	}
};

/**
 * Delete storage configuration
 *
 * @param {string} store - Storage type
 * @returns {Promise<Object>} Response
 */
export const deleteStorageConfig = async ( store ) => {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/backup/storage/delete`,
			method: 'POST',
			data: { store },
		} );
		return response;
	} catch ( error ) {
		console.error( 'Failed to delete storage config:', error );
		throw error;
	}
};

/**
 * Get Dropbox OAuth URL
 *
 * @param {string} storage - Storage type
 * @returns {Promise<Object>} Response with OAuth URL
 */
export const getDropboxOAuthUrl = async ( storage ) => {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/backup/storage/oauth?storage=${ encodeURIComponent(
				storage
			) }`,
			method: 'GET',
		} );
		return response;
	} catch ( error ) {
		console.error( 'Failed to get OAuth URL:', error );
		throw error;
	}
};

/**
 * Get spam check statistics
 *
 * @returns {Promise<Object>} Stats object with enqueued, processing, completed, failed counts
 */
export const getSpamCheckStats = async () => {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/spam-check/stats`,
			method: 'GET',
		} );
		return response;
	} catch ( error ) {
		console.error( 'Failed to unlock IP:', error );
		throw error;
	}
};

/**
 * Get brute force login attempts log
 *
 * @returns {Promise<Object>} Log data
 */
export const getBruteforceLog = async () => {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/bruteforce/log`,
			method: 'GET',
		} );
		return response;
	} catch ( error ) {
		console.error( 'Failed to fetch brute force log:', error );
		throw error;
	}
};

/**
 * Unlock a locked IP/username
 *
 * @param {string} ip - IP address to unlock
 * @param {string} username - Username to unlock
 * @returns {Promise<Object>} Response object
 */
export const unlockBruteforce = async ( ip, username ) => {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/bruteforce/unlock`,
			method: 'POST',
			data: { ip, username },
		} );
		return response;
	} catch ( error ) {
		console.error( 'Failed to unlock IP:', error );
		throw error;
	}
};

/**
 * Manage license (activate/deactivate)
 *
 * @param {string} action - 'activate' or 'deactivate'
 * @param {string} key - License key
 * @returns {Promise<Object>} Response object
 */
export const manageLicense = async ( action, key ) => {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/license`,
			method: 'POST',
			data: {
				data: {
					key,
					action,
				},
			},
		} );
		return response;
	} catch ( error ) {
		console.error( 'Failed to manage license:', error );
		throw error;
	}
};

// Two-Factor Authentication API

/**
 * Get current user's 2FA status
 *
 * @returns {Promise<Object>} 2FA status data
 */
export const getTwoFactorStatus = async () => {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/two-factor/status`,
			method: 'GET',
		} );
		return response;
	} catch ( error ) {
		console.error( 'Failed to fetch 2FA status:', error );
		throw error;
	}
};

/**
 * Generate new QR/secret for 2FA setup
 *
 * @returns {Promise<Object>} QR URL and secret display
 */
export const setupTwoFactor = async () => {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/two-factor/setup`,
			method: 'POST',
		} );
		return response;
	} catch ( error ) {
		console.error( 'Failed to setup 2FA:', error );
		throw error;
	}
};

/**
 * Verify TOTP code and activate 2FA
 *
 * @param {string} code - 6-digit TOTP code
 * @returns {Promise<Object>} Response with restore codes
 */
export const verifyTwoFactor = async ( code ) => {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/two-factor/verify`,
			method: 'POST',
			data: { code },
		} );
		return response;
	} catch ( error ) {
		console.error( 'Failed to verify 2FA:', error );
		throw error;
	}
};

/**
 * Disable 2FA for current user
 *
 * @returns {Promise<Object>} Response object
 */
export const disableTwoFactor = async () => {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/two-factor/disable`,
			method: 'POST',
		} );
		return response;
	} catch ( error ) {
		console.error( 'Failed to disable 2FA:', error );
		throw error;
	}
};

/**
 * Regenerate backup codes
 *
 * @returns {Promise<Object>} Response with new codes
 */
export const regenerateBackupCodes = async () => {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/two-factor/regenerate-codes`,
			method: 'POST',
		} );
		return response;
	} catch ( error ) {
		console.error( 'Failed to regenerate backup codes:', error );
		throw error;
	}
};

/**
 * Save IP whitelist
 *
 * @param {string[]} ips - Array of IP addresses
 * @returns {Promise<Object>} Response object
 */
export const saveIpWhitelist = async ( ips ) => {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/two-factor/ip-whitelist`,
			method: 'POST',
			data: { ips },
		} );
		return response;
	} catch ( error ) {
		console.error( 'Failed to save IP whitelist:', error );
		throw error;
	}
};

/**
 * Get paginated users with 2FA status (admin only)
 *
 * @param {number} page - Page number
 * @param {number} perPage - Items per page
 * @param {string} search - Search query
 * @returns {Promise<Object>} Users list with pagination
 */
export const getTwoFactorUsers = async (
	page = 1,
	perPage = 20,
	search = ''
) => {
	try {
		const params = new URLSearchParams( {
			page: String( page ),
			per_page: String( perPage ),
		} );
		if ( search ) {
			params.set( 'search', search );
		}
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/two-factor/users?${ params.toString() }`,
			method: 'GET',
		} );
		return response;
	} catch ( error ) {
		console.error( 'Failed to fetch 2FA users:', error );
		throw error;
	}
};

/**
 * Toggle 2FA for a specific user (admin only)
 *
 * @param {number} userId - User ID
 * @param {boolean} enabled - Enable or disable
 * @returns {Promise<Object>} Response object
 */
export const toggleUserTwoFactor = async ( userId, enabled ) => {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/two-factor/users/toggle`,
			method: 'POST',
			data: { user_id: userId, enabled },
		} );
		return response;
	} catch ( error ) {
		console.error( 'Failed to toggle user 2FA:', error );
		throw error;
	}
};

/**
 * Regenerate backup codes for a specific user (admin only)
 *
 * @param {number} userId - User ID
 * @returns {Promise<Object>} Response object
 */
export const regenerateUserCodes = async ( userId ) => {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/two-factor/users/regenerate-codes`,
			method: 'POST',
			data: { user_id: userId },
		} );
		return response;
	} catch ( error ) {
		console.error( 'Failed to regenerate user codes:', error );
		throw error;
	}
};

/**
 * Hide an audit item persistently
 *
 * @param {string} id - Audit item ID
 * @returns {Promise<Object>} Response object
 */
export const hideAuditItem = async ( id ) => {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/audit/hide`,
			method: 'POST',
			data: { id },
		} );
		return response;
	} catch ( error ) {
		console.error( 'Failed to hide audit item:', error );
		throw error;
	}
};

/**
 * Unhide a previously hidden audit item
 *
 * @param {string} id - Audit item ID
 * @returns {Promise<Object>} Response object
 */
export const unhideAuditItem = async ( id ) => {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/audit/unhide`,
			method: 'POST',
			data: { id },
		} );
		return response;
	} catch ( error ) {
		console.error( 'Failed to unhide audit item:', error );
		throw error;
	}
};

/**
 * Dismiss the caching performance tip banner
 *
 * @returns {Promise<Object>} Response object
 */
export const dismissCacheTip = async () => {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/cache-tip/dismiss`,
			method: 'POST',
		} );
		return response;
	} catch ( error ) {
		console.error( 'Failed to dismiss cache tip:', error );
		throw error;
	}
};

/**
 * Install or activate a plugin
 *
 * @param {string} plugin - Plugin path (e.g., 'plugin-name/plugin-name.php')
 * @param {string} pluginSlug - Plugin slug for WordPress.org (e.g., 'plugin-name')
 * @returns {Promise<Object>} Response object
 */
export const installOrActivatePlugin = async ( plugin, pluginSlug ) => {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/plugins/install-or-activate`,
			method: 'POST',
			data: { plugin, plugin_slug: pluginSlug },
		} );
		return response;
	} catch ( error ) {
		console.error( 'Failed to install or activate plugin:', error );
		throw error;
	}
};

/**
 * Change database prefix
 *
 * @param {string} newPrefix - New database prefix
 * @param {number} fixingIssueId - Audit issue ID being fixed
 * @returns {Promise<Object>} Response object
 */
export const changeDatabasePrefix = async ( newPrefix, fixingIssueId ) => {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/database-prefix`,
			method: 'POST',
			data: {
				new_prefix: newPrefix,
				fixing_issue_id: fixingIssueId,
			},
		} );
		return response;
	} catch ( error ) {
		console.error( 'Failed to change database prefix:', error );
		throw error;
	}
};
