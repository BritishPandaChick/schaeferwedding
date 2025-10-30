<?php
/**
 * Ajax handlers
 *
 * @author        Alex Kovalev <alex.kovalevv@gmail.com>, Github: https://github.com/alexkovalevv
 * @copyright (c) 2017 Webraftic Ltd
 * @version       1.0
 */

// Exit if accessed directly
if( !defined('ABSPATH') ) {
	exit;
}

/**
 * Обработчик ajax запросов для виджета подписки на новости
 *
 * @param Wbcr_Factory475_Plugin $plugin_instance
 *
 * @since 2.3.0
 *
 */
function wbcr_factory_templates_128_subscribe($plugin_instance)
{
	$plugin_name = $plugin_instance->request->post('plugin_name', null, true);

	if( ($plugin_instance->getPluginName() !== $plugin_name) || !$plugin_instance->current_user_can() ) {
		wp_die(-1, 403);
	}

	$email = $plugin_instance->request->post('email', null, true);
	$group_id = $plugin_instance->request->post('group_id', null, true);

	check_admin_referer("clearfy_subscribe_for_{$plugin_name}");

	if( empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) ) {
		wp_send_json_error(['error_message' => __('You did not send your email address or it is incorrect!', 'wbcr_factory_templates_128')]);
	}

	if( empty($group_id) ) {
		wp_send_json_error(['error_message' => __('Group ID is empty!', 'wbcr_factory_templates_128')]);
	}

	$response = wp_remote_post('https://api.themeisle.com/tracking/subscribe', array(
		'timeout' => 10,
		'headers' => array(
			'Content-Type'  => 'application/json',
			'Cache-Control' => 'no-cache',
			'Accept'        => 'application/json, */*;q=0.1',
		),
		'body'    => wp_json_encode(
			array(
				'slug'  => $plugin_name,
				'site'  => home_url(),
				'email' => $email,
			)
		),
	));
	if( is_wp_error($response) ) {
		wp_send_json_error(['error_message' => $response->get_error_message()]);
	}

	$data = @json_decode(wp_remote_retrieve_body($response), ARRAY_A);


	if( isset($data['code']) ) {
		$plugin_instance->updatePopulateOption('factory_clearfy_user_subsribed', 1);
		wp_send_json_success(['subscribed' => $data['subscribed']]);
	}

	die();
}

/**
 * Обработчик ajax запросов для проверки, активации, деактивации лицензионного ключа
 *
 * @param Wbcr_Factory475_Plugin $plugin_instance
 *
 * @since         2.0.7
 *
 */
function wbcr_factory_templates_128_check_license($plugin_instance)
{

	$plugin_name = $plugin_instance->request->post('plugin_name', null, true);

	if( ($plugin_instance->getPluginName() !== $plugin_name) || !$plugin_instance->current_user_can() ) {
		wp_die(-1, 403);
	}

	$action = $plugin_instance->request->post('license_action', false, true);
	$license_key = $plugin_instance->request->post('licensekey', null);

	check_admin_referer("clearfy_activate_license_for_{$plugin_name}");

	if( empty($action) || !in_array($action, ['activate', 'deactivate', 'sync', 'unsubscribe']) ) {
		wp_send_json_error(['error_message' => __('Licensing action not passed or this action is prohibited!', 'wbcr_factory_templates_128')]);
		die();
	}

	$result = null;
	$success_message = '';

	try {
		switch( $action ) {
			case 'activate':
				if( empty($license_key) || strlen($license_key) > 32 ) {
					wp_send_json_error(['error_message' => __('License key is empty or license key too long (license key is 32 characters long)', 'wbcr_factory_templates_128')]);
				} else {
					$plugin_instance->premium->activate($license_key);
					$success_message = __('Your license has been successfully activated', 'wbcr_factory_templates_128');
				}
				break;
			case 'deactivate':
				$plugin_instance->premium->deactivate();
				$success_message = __('The license is deactivated', 'wbcr_factory_templates_128');
				break;
			case 'sync':
				$plugin_instance->premium->sync();
				$success_message = __('The license has been updated', 'wbcr_factory_templates_128');
				break;
			case 'unsubscribe':
				$plugin_instance->premium->cancel_paid_subscription();
				$success_message = __('Subscription success cancelled', 'wbcr_factory_templates_128');
				break;
		}
	} catch( Exception $e ) {

		/**
		 * Экшен выполняется, когда проверка лицензии вернула ошибку
		 *
		 * @param string $license_key
		 * @param string $error_message
		 *
		 * @param string $action
		 * @since 2.0.7
		 *
		 * @since 2.1.2 Переименован в {$plugin_name}/factory/clearfy/check_license_error
		 */
		do_action("{$plugin_name}/factory/clearfy/check_license_error", $action, $license_key, $e->getMessage());

		wp_send_json_error(['error_message' => $e->getMessage()]);
		die();
	}

	/**
	 * Экшен выполняется, когда проверка лицензии успешно завершена
	 *
	 * @param string $license_key
	 *
	 * @param string $action
	 * @since 2.1.2 Переименован в {$plugin_name}/factory/clearfy/check_license_success
	 * @since 2.0.7
	 *
	 */
	do_action("{$plugin_name}/factory/clearfy/check_license_success", $action, $license_key);

	wp_send_json_success(['message' => $success_message]);

	die();
}