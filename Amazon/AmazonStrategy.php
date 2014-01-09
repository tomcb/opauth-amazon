<?php
/**
 * Amazon strategy for Opauth
 * based on http://login.amazon.com/website
 * 
 * More information on Opauth: http://opauth.org
 * 
 * @copyright    Copyright � 2013 Omlet Ltd.
 * @link	 http://www.omlet.co.uk/
 * @link         http://opauth.org
 * @license      MIT License
 */

class AmazonStrategy extends OpauthStrategy{
	
	/**
	 * Compulsory config keys, listed as unassociative arrays
	 * eg. array('app_id', 'app_secret');
	 */
	public $expects = array('client_id', 'client_secret');
	
	/**
	 * Optional config keys with respective default values, listed as associative arrays
	 * eg. array('scope' => 'email');
	 */
    public $optionals = array(
        'redirect_uri', 'scope'
    ); 
    
	public $defaults = array(
		'redirect_uri' => '{complete_url_to_strategy}int_callback',
        'scope' => 'profile'
	);

	/**
	 * Auth request
     * Redirects the user agent to the amazon login screen.
     * 
     * @author Thomas Coulter-Brophy
	 */
	public function request(){

        $url = 'https://www.amazon.com/ap/oa?client_id=' . 
                urlencode($this->strategy['client_id']) . 
                '&scope=' . urlencode($this->strategy['scope']) . 
                '&response_type=code&redirect_uri=' . 
                urlencode($this->strategy['redirect_uri']);

        header('Location: ' . $url);
	}
	
     /**
     * Internal callback
     * Called by Amazon after a successful authentication.
     * http://path-to-opauth/amazon/int_callback
     * 
     * @author Thomas Coulter-Brophy
     * 
     */
	public function int_callback(){
        
        //check that the authorization code is present, i.e. that the authorization was a success.
		if (array_key_exists('code', $_REQUEST) && !empty($_REQUEST['code'])){
			
            $postdata = http_build_query(array(  'grant_type'    =>  'authorization_code',
                                'code'          =>  $_REQUEST['code'],
                                'redirect_uri'  =>  $this->strategy['redirect_uri'],
                                'client_id'     =>  $this->strategy['client_id'],
                                'client_secret' =>  $this->strategy['client_secret'] ));
            
            //get an access token, used to read the profile of the user
            $curl = curl_init('https://api.amazon.com/auth/o2/token');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/x-www-form-urlencoded', 'Content-length: ' . strlen($postdata)));
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
                        

            $response = curl_exec($curl);
            curl_close($curl);
            $results = json_decode($response);
            
            
            //if $results is empty our request for an access token failed, so give an error, and dump the data we do have.
			if (!empty($results)){
                
                //$this->me uses the access token and actually reads the profile data
				$me = $this->me($results->access_token);

				$this->auth = array(
					'provider' => 'Amazon',
					'uid' => $me->user_id,
					'info' => array(
						'name' => $me->name
					),
					'credentials' => array(
						'access_token' => $results->access_token,
						'expires_in' => $results->expires_in,
                        'token_type' => $results->token_type,
                        'refresh_token' => $results->refresh_token
                    ),
					'raw' => $me
				);
				
				if (!empty($me->email)) $this->auth['info']['email'] = $me->email;
                if (!empty($me->postal_code)) $this->auth['info']['postal_code'] = $me->postal_code;
				
				$this->callback();
			}
			else{
				$error = array(
					'provider' => 'Amazon',
					'code' => 'access_token_error',
					'message' => 'Failed when attempting to obtain access token',
					'raw' => $headers
				);

				$this->errorCallback($error);
			}
		}
		else{
			$error = array(
				'provider' => 'Amazon',
				'code' => $_GET['error'],
				'message' => $_GET['error_description'],
				'raw' => $_GET
			);
			
			$this->errorCallback($error);
		}
	}
	
	/**
	 * Queries Amazon for user info
     * 
     * @author Thomas Coulter-Brophy
	 * @param string $access_token 
	 * @return array Parsed JSON results
	 */
	private function me($access_token){
		$curl = curl_init('https://api.amazon.com/user/profile');
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: bearer ' . $access_token));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
 
        $response = curl_exec($curl);
        
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $response_header = substr($response, 0, $header_size);
        $response_body = substr($response, $header_size);
        
        if (!empty($response_body)){
			return json_decode($response_body);
		}
		else{
			$error = array(
				'provider' => 'Amazon',
				'code' => 'me_error',
				'message' => 'Failed when attempting to query for user information',
				'raw' => array(
					'response' => $response_body,
					'headers' => $response_headers
				)
			);

			$this->errorCallback($error);
		}
	}
}
