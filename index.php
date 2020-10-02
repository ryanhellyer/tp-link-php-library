<?php

/**
 * Library for accessing TP Link devices.
 */
class TP_Link_API {

	/**
	 * Class constructor.
	 */
	public function __construct( $username, $password ) {

		$token   = $this->get_token( $username, $password );

		$devices = $this->get_devices( $token );

		foreach ( $devices as $device ) {
			$device_id = $device['deviceId'];
			$this->turn_device_on( $device_id, $token );
			$this->turn_device_off( $device_id, $token );
		}
	}

	/**
	 * Turn a device off.
	 *
	 * @access private
	 * @param int $device_id The device ID.
	 * @param string $token Authentication token.
	 * @return bool True if successful.
	 */
	private function turn_device_off( $device_id, $token ) {

		$command = 'curl -XPOST
			-H "Content-type: application/json"
			-d \'{"method":"passthrough","params":{"deviceId":"' . $device_id . '","requestData":"{\"system\":{\"set_relay_state\":{\"state\":0}}}"}}\'
			\'https://eu-wap.tplinkcloud.com/?appName=Kasa_Android&termID=96a26069-5b27-40e5-8408-de7d2371bf16&appVer=1.4.4.607&ospf=Android%2B6.0.1&netType=wifi&locale=es_ES&token=' . $token . '\'';
		$command = $this->prepare_command( $command );

		$response = shell_exec( $command );
		$response = json_decode( $response, true );

		if ( ! isset( $response['error_code'] ) || 0 === $response['error_code'] ) {
			return false;
		} else {
			return true;
		}

	}

	/**
	 * Turn a device on.
	 *
	 * @access private
	 * @param int $device_id The device ID.
	 * @param string $token Authentication token.
	 * @return bool True if successful.
	 */
	private function turn_device_on( $device_id, $token ) {

		$command = 'curl -XPOST
			-H "Content-type: application/json"
			-d \'{"method":"passthrough","params":{"deviceId":"' . $device_id . '","requestData":"{\"system\":{\"set_relay_state\":{\"state\":1}}}"}}\'
			\'https://eu-wap.tplinkcloud.com/?appName=Kasa_Android&termID=96a26069-5b27-40e5-8408-de7d2371bf16&appVer=1.4.4.607&ospf=Android%2B6.0.1&netType=wifi&locale=es_ES&token=' . $token . '\'';
		$command = $this->prepare_command( $command );

		$response = shell_exec( $command );
		$response = json_decode( $response, true );

		if ( ! isset( $response['error_code'] ) || 0 === $response['error_code'] ) {
			return false;
		} else {
			return true;
		}

	}

	/**
	 * Get the devices.
	 *
	 * @access private
	 * @param string $token Authentication token.
	 * @return array|false False if not successful, else array of devices.
	 */
	private function get_devices( $token ) {

		// Return cache if available.
		if ( $this->get_cache( 'devices', 60 ) ) {
			return $this->get_cache( 'devices', 60 );
		}

		$command = "curl -XPOST \
		  -H 'Content-Type: application/json;charset=UTF-8' \
		  -d '{\"method\":\"getDeviceList\"}' \
		  'https://wap.tplinkcloud.com/?appName=Kasa_Android&termID=96a26069-5b27-40e5-8408-de7d2371bf16&appVer=1.4.4.607&ospf=Android%2B6.0.1&netType=wifi&locale=es_ES&token=$token'";

		$devices = shell_exec( $command );
		$devices = json_decode( $devices, true );

  		if ( isset( $devices['result']['deviceList'] ) ) {
  			$devices_list = $devices['result']['deviceList'];

			$this->update_cache( 'devices', $devices_list );

			return $devices_list;
		} else {
			return false;
		}
	}

	/**
	 * Get the authentication token.
	 *
	 * @access private
	 * @param string $username The username.
	 * @param string $password The password.
	 * @return array|false False if not successful, else array of devices.
	 */
	public function get_token( $username, $password ) {

		// Return cache if available.
		if ( $this->get_cache( 'token', 60 ) ) {
			return $this->get_cache( 'token', 60 );
		}

		$command = "curl -XPOST \
		  -H \"Content-type: application/json\"
		  -d '{
			\"method\": \"login\",
			\"url\":\"https://wap.tplinkcloud.com\",
			\"params\":{
			  \"appType\":\"Kasa_Android\",
			  \"cloudPassword\":\"$password\",
			  \"cloudUserName\":\"$username\",
			  \"terminalUUID\":\"96a26069-5b27-40e5-8408-de7d2371bf16\"
			}
		  }'
		  'https://wap.tplinkcloud.com/?appName=Kasa_Android&termID=96a26069-5b27-40e5-8408-de7d2371bf16&appVer=1.4.4.607&ospf=Android%2B6.0.1&netType=wifi&locale=es_ES'";
		$command = $this->prepare_command( $command );
//echo $command;die;
		$response = shell_exec( $command );
//print_r( $response );
		$response = json_decode( $response, true );

		if ( isset( $response['result']['token'] ) ) {
			$token = $response['result']['token'];

			$this->update_cache( 'token', $token );

			return $token;
		} else {
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

	/**
	 * Get cached data.
	 *
	 * @access private
	 * @param string $key The cache key.
	 * @param int $expiry The expiry time in seconds.
	 * @return XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
	 */
	private function get_cache( $key, $expiry ) {
		$data = file_get_contents( '../' . $key . '.txt' );
		$data = explode( ':', $data );
		if ( isset( $data[0] ) && isset( $data[1] ) ) {
			$last_time = $data[0];
			$result     = $data[1];

			if ( ( $last_time + $expiry ) > time() ) {

				// If data is an array, the decode it first
				if ( is_array( json_decode( $result, true ) ) ) {
					$result = json_decode( $result, true );
				}

				return $result;
			}
		}
	}

	/**
	 * Update cached data.
	 *
	 * @access private
	 * @param string $key The cache key.
	 * @param string $data The data to store.
	 */
	private function update_cache( $key, $data ) {
		file_put_contents( '../' . $key . '.txt', time() . ':' . json_encode( $data ) );
	}

}
