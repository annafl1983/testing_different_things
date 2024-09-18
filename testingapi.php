<?php 

if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}


class SALESFORCE_API extends SALESFORCE {
	
	public function __construct() {
		
		$access_token = get_field( 'salesforce_access_token', 'options' );
		
		$this->version_api	= '60.0';
		$this->host 		= get_field( 'salesforce_endpoint', 'options' );
		$this->token 		= strlen( $access_token ) ? $access_token : 'no_access_token';
		$this->email 		= array();
		
		$emails = get_field( 'salesforce_admin_email', 'options' );
		$emails = explode( ',', $emails );
		
		foreach ( $emails as $email ) {
			
			$email = trim( $email );
			
			if ( is_email( $email ) )
				$this->email[] = $email;
		}
	}

	
private function load_guzzle() {
		require_once( trailingslashit( WP_CONTENT_DIR ) . "lib/phpguzzle/guzzle.php" );
	}
	
	private function request( $method, $point, $params = array(), $headers = array(), $form_data = 'multipart' ) {
		
		$this->load_guzzle();
		
		$client = new GuzzleHttp\Client();
		
		$url = trailingslashit( $this->host ) . $point;
	
		$defaults = array( 'Accept' => '*/*' );
		$headers = wp_parse_args( $headers, $defaults );
	
		$options = array( 'headers' => $headers );
		
		sleep(1);
		switch( $method ) {
			case 'GET':
				$options['query'] = $params;
				try {
					$response = $client->get( $url, $options );
				} catch (GuzzleHttp\Exception\ClientException $e) {
					$response = $e->getResponse();
				} catch (GuzzleHttp\Exception\RequestException $e) {
					$response = $e->getResponse();
				} catch (GuzzleHttp\Exception\ConnectException $e) {
					$response = $e->getResponse();
				} catch (GuzzleHttp\Exception\ServerException $e) {
					$response = $e->getResponse();
				}
				break;
			case 'POST':
				$options[$form_data] = $params;
				try {
					$response = $client->post( $url, $options );
				} catch (GuzzleHttp\Exception\ClientException $e) {
					$response = $e->getResponse();
				} catch (GuzzleHttp\Exception\RequestException $e) {
					$response = $e->getResponse();
				} catch (GuzzleHttp\Exception\ConnectException $e) {
					$response = $e->getResponse();
				} catch (GuzzleHttp\Exception\ServerException $e) {
					$response = $e->getResponse();
				}
				break;
			case 'PATCH':
				$options['body'] = $params;
				try {
					$response = $client->patch( $url, $options );
				} catch (GuzzleHttp\Exception\ClientException $e) {
					$response = $e->getResponse();
				} catch (GuzzleHttp\Exception\RequestException $e) {
					$response = $e->getResponse();
				} catch (GuzzleHttp\Exception\ConnectException $e) {
					$response = $e->getResponse();
				} catch (GuzzleHttp\Exception\ServerException $e) {
					$response = $e->getResponse();
				}
				break;
		}
		
		$httpCode 			= $response->getStatusCode();
		$responseHeaders 	= $response->getHeaders();
		$responseBody 		= (array) json_decode( $response->getBody()->getContents() );
		
		/*
			Коди відповідей:
				200 Запит виконано успішно
				400 Невірний запит
				401 Не авторизований
				404 Не знайдено
				500 Внутрішня помилка сервера
		*/
		
		
		if ( $httpCode == 401 ) {
			
			if ( isset( $responseBody[0]->errorCode ) && $responseBody[0]->errorCode == 'INVALID_SESSION_ID' ) {
				
				$result = $this->get_token();
				if ( isset( $result['access_token'] ) ) {
					update_field( 'salesforce_access_token', $result['access_token'], 'options' );
					return false;
				}
			}
			
			if ( ! empty( $this->email ) ) {
				$headers = array(
					'From: Kepner-Tregoe <info@kepner-tregoe.com>',
					'content-type: text/html',
				);
	
				$subject = 'SalesForce API failed (Unauthorized)';
				$message = maybe_serialize( $responseBody );
				wp_mail( $this->email, $subject, $message, $headers );
			}
			
			return false;
		}
		
		if ( ! in_array( $httpCode, array( 200, 201, 204 ) ) ) {
			
			if ( ! empty( $this->email ) ) {
				$headers = array(
					'From: Kepner-Tregoe <info@kepner-tregoe.com>',
					'content-type: text/html',
				);
	
				$subject = sprintf( 'SalesForce API failed (Status: %1$s)', $httpCode );
				$message = maybe_serialize( $responseBody );
				wp_mail( $this->email, $subject, $message, $headers );
			}
			
			return false;
		}
		
		return $responseBody;
	}

	
	
	public function get_token( $params = array() ) {
		
		$defaults = array( 	array( 'name' => 'username', 'contents' => get_field( 'salesforce_username', 'options' ) ),
							array( 'name' => 'password', 'contents' => get_field( 'salesforce_password', 'options' ) . get_field( 'salesforce_security_token', 'options' ) ),
							array( 'name' => 'grant_type', 'contents' => 'password' ),
							array( 'name' => 'client_id', 'contents' => get_field( 'salesforce_consumer_key', 'options' ) ),
							array( 'name' => 'client_secret', 'contents' => get_field( 'salesforce_consumer_secret', 'options' ) ),
						);
		$params = wp_parse_args( $params, $defaults );
		
		$url = "services/oauth2/token";
		
		return $this->request( 'POST', $url, $params );
	}
	
	public function create_salesforce_workshop_order( $params ) {
		
		$url = "services/data/v{$this->version_api}/sobjects/KT_Web_Response__c/";
		
		$headers = array( 	'Content-Type' 	=> 'application/json',
							'Authorization' => "Bearer {$this->token}",
						);
		
		$response = $this->request( 'POST', $url, json_encode( $params ), $headers, 'body' );
		if ( ! isset( $response['id'] ) )
			return false;
		
		return $response['id'];
	}
	
	public function update_salesforce_workshop_order( $salesforce_object_id, $params ) {
		
		$url = "services/data/v{$this->version_api}/sobjects/KT_Web_Response__c/" . $salesforce_object_id;
		
		$headers = array( 	'Content-Type' 	=> 'application/json',
							'Authorization' => "Bearer {$this->token}",
						);
		
		return $this->request( 'PATCH', $url, json_encode( $params ), $headers );
	}
	
	public function create_salesforce_workshop_participant( $params ) {
		
		$url = "services/data/v{$this->version_api}/sobjects/Workshop_Registration_Participant__c/";
		
		$headers = array( 	'Content-Type' 	=> 'application/json',
							'Authorization' => "Bearer {$this->token}",
						);
		
		$response = $this->request( 'POST', $url, json_encode( $params ), $headers, 'body' );
		if ( ! isset( $response['id'] ) )
			return false;
		
		return $response['id'];
	}
	
	public function update_salesforce_workshop_participant( $salesforce_object_id, $params ) {
		
		$url = "services/data/v{$this->version_api}/sobjects/Workshop_Registration_Participant__c/" . $salesforce_object_id;
		
		$headers = array( 	'Content-Type' 	=> 'application/json',
							'Authorization' => "Bearer {$this->token}",
						);
		
		return $this->request( 'PATCH', $url, json_encode( $params ), $headers );
	}

	
}

