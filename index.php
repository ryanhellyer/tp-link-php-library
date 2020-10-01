<?php

ini_set( 'display_errors', 1 );
ini_set( 'display_startup_errors', 1 );
error_reporting( E_ALL );



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
	 */
	private function get_devices( $token ) {

		$command = "curl -XPOST \
		  -H 'Content-Type: application/json;charset=UTF-8' \
		  -d '{\"method\":\"getDeviceList\"}' \
		  'https://wap.tplinkcloud.com/?appName=Kasa_Android&termID=96a26069-5b27-40e5-8408-de7d2371bf16&appVer=1.4.4.607&ospf=Android%2B6.0.1&netType=wifi&locale=es_ES&token=$token'";

		$devices = shell_exec( $command );
		$devices = json_decode( $devices, true );

  		if ( isset( $devices['result']['deviceList'] ) ) {
			return $devices['result']['deviceList'];
		} else {
			return false;
		}
	}

	/**
	 */
	public function get_token( $username, $password ) {

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

		$response = shell_exec( $command );
		$response = json_decode( $response, true );

		if ( isset( $response['result']['token'] ) ) {
			return $response['result']['token'];
		} else {
			return false;
		}
	}

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
