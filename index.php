<?php

/**
 * Library for accessing TP Link devices.
 */
class TP_Link_API {

	private $username;
	private $password;
	public $token;
	public $devices;

	/**
	 * Class constructor.
	 */
	public function __construct( $username, $password, $token = null, $devices = null ) {
		$this->username = $username;
		$this->password = $password;

		// Get token.
		if ( null === $token ) {
			$this->token = $this->get_token();
		} else {
			$this->token = $token;
		}

		// Get devices.
		if ( null === $devices ) {
			$this->devices = $this->get_devices();
		} else {
			$this->devices = $devices;
		}
	}

	/**
	 * Toggle lights.
	 */
	public function toggle() {
		$devices = $this->get_devices();

		foreach ( $devices as $device ) {
			$device_id = $device['deviceId'];
			$this->turn_device_on( $device_id );
			$this->turn_device_off( $device_id );
		}
	}

	/**
	 * Get the devices.
	 *
	 * @return array|false False if not successful, else array of devices.
	 */
	public function get_devices() {

		$command = "curl -XPOST \
		  -H 'Content-Type: application/json;charset=UTF-8' \
		  -d '{\"method\":\"getDeviceList\"}' \
		  'https://wap.tplinkcloud.com/?appName=Kasa_Android&termID=96a26069-5b27-40e5-8408-de7d2371bf16&appVer=1.4.4.607&ospf=Android%2B6.0.1&netType=wifi&locale=es_ES&token=" . $this->token . "'";

		$devices = shell_exec( $command );
		$devices = json_decode( $devices, true );

  		if ( isset( $devices['result']['deviceList'] ) ) {
  			$devices_list = $devices['result']['deviceList'];
			return $devices_list;
		} else {
			trigger_error ( 'No devices found' );
			return false;
		}
	}

	/**
	 * Turn a device off.
	 *
	 * @param int $device_id The device ID.
	 * @return bool True if successful.
	 */
	public function turn_device_off( $device_id ) {

		$command = 'curl -XPOST
			-H "Content-type: application/json"
			-d \'{"method":"passthrough","params":{"deviceId":"' . $device_id . '","requestData":"{\"system\":{\"set_relay_state\":{\"state\":0}}}"}}\'
			\'https://eu-wap.tplinkcloud.com/?appName=Kasa_Android&termID=96a26069-5b27-40e5-8408-de7d2371bf16&appVer=1.4.4.607&ospf=Android%2B6.0.1&netType=wifi&locale=es_ES&token=' . $this->token . '\'';
		$command = $this->prepare_command( $command );

		$response = shell_exec( $command );
		$response = json_decode( $response, true );

		if ( ! isset( $response['error_code'] ) || 0 !== $response['error_code'] ) {
			trigger_error ( 'Device did not turn off' );
			return false;
		} else {
			return true;
		}

	}

	/**
	 * Turn a device on.
	 *
	 * @param int $device_id The device ID.
	 * @return bool True if successful.
	 */
	public function turn_device_on( $device_id ) {

		$command = 'curl -XPOST
			-H "Content-type: application/json"
			-d \'{"method":"passthrough","params":{"deviceId":"' . $device_id . '","requestData":"{\"system\":{\"set_relay_state\":{\"state\":1}}}"}}\'
			\'https://eu-wap.tplinkcloud.com/?appName=Kasa_Android&termID=96a26069-5b27-40e5-8408-de7d2371bf16&appVer=1.4.4.607&ospf=Android%2B6.0.1&netType=wifi&locale=es_ES&token=' . $this->token . '\'';
		$command = $this->prepare_command( $command );

		$response = shell_exec( $command );
		$response = json_decode( $response, true );

		if ( ! isset( $response['error_code'] ) || 0 !== $response['error_code'] ) {
			trigger_error ( 'Device did not turn on' );
			return false;
		} else {
			return true;
		}

	}

	/**
	 * Get system info for a device.
	 *
	 * @access private
	 * @return array|false False if not successful, else array of device system information.
	 */
	private function get_device_system_info( $device_id ) {

		$command = 'curl -XPOST \
    -H \'Content-Type: application/json;charset=UTF-8\' \
    -d \'{"method": "passthrough", "params": { "deviceId": "' . $device_id . '","requestData": {"system":{"get_sysinfo":null}}}}\' \
    \'https://wap.tplinkcloud.com/?appName=Kasa_Android&termID=96a26069-5b27-40e5-8408-de7d2371bf16&appVer=1.4.4.607&ospf=Android%2B6.0.1&netType=wifi&locale=es_ES&token=' . $this->token . "'";

		$response = shell_exec( $command );
		$response = json_decode( $response, true );

		// Handle errors.
		if ( ! isset( $response['error_code'] ) || 0 !== $response['error_code'] ) {
			trigger_error ( 'System info request error' );
			return false;
		}

		return $response;
	}

	/**
	 * Get state of a device.
	 *
	 * @return int|false False if not successful, else 1 if on or 0 if off.
	 */
	public function get_device_state( $device_id ) {
		$system_info = $this->get_device_system_info( $device_id );

		// Handle errors.
		if ( ! isset( $system_info['result']['responseData']['system']['get_sysinfo']['relay_state'] ) ) {
			trigger_error ( 'System state request error' );
			return false;
		}

		$state = $system_info['result']['responseData']['system']['get_sysinfo']['relay_state'];
		if ( 0 === $state || 1 === $state ) {
			return $state;
		}

	}

	/**
	 * Get the authentication token.
	 *
	 * @access private
	 * @return array|false False if not successful, else array of devices.
	 */
	private function get_token() {

		$command = "curl -XPOST \
		  -H \"Content-type: application/json\"
		  -d '{
			\"method\": \"login\",
			\"url\":\"https://wap.tplinkcloud.com\",
			\"params\":{
			  \"appType\":\"Kasa_Android\",
			  \"cloudPassword\":\"$this->password\",
			  \"cloudUserName\":\"$this->username\",
			  \"terminalUUID\":\"96a26069-5b27-40e5-8408-de7d2371bf16\"
			}
		  }'
		  'https://wap.tplinkcloud.com/?appName=Kasa_Android&termID=96a26069-5b27-40e5-8408-de7d2371bf16&appVer=1.4.4.607&ospf=Android%2B6.0.1&netType=wifi&locale=es_ES'";
		$command = $this->prepare_command( $command );

		$response = shell_exec( $command );
		$response = json_decode( $response, true );

		if ( isset( $response['result']['token'] ) ) {
			$token = $response['result']['token'];
			$token = str_replace( '"', '', $token );

			return $token;
		} else {
			trigger_error ( 'No token found' );
			return false;
		}
	}

	/**
	 * Temporary method for hacking in easier to read curl requests.
	 *
	 * @access private
	 */
	private function prepare_command( $command ) {
		$command = str_replace( "\n", '', $command );
		$command = str_replace( "\t", ' ', $command );
		$command = str_replace( '  ', ' ', $command );
		$command = str_replace( '   ', ' ', $command );
		$command = str_replace( '    ', ' ', $command );
		$command = str_replace( '     ', ' ', $command );
		$command = str_replace( '      ', ' ', $command );

		return $command;
	}

}
