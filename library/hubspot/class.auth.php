<?php
/**
* Copyright 2013 HubSpot, Inc.
*
*   Licensed under the Apache License, Version 2.0 (the
* "License"); you may not use this file except in compliance
* with the License.
*   You may obtain a copy of the License at
*
*       http://www.apache.org/licenses/LICENSE-2.0
*
*   Unless required by applicable law or agreed to in writing,
* software distributed under the License is distributed on an
* "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND,
* either express or implied.  See the License for the specific
* language governing permissions and limitations under the
* License.
*/
require_once('class.baseclient.php');

if ( !class_exists('HubSpot_Auth') ) :
class HubSpot_Auth extends HubSpot_Baseclient{

	protected $API_PATH = 'auth';
	protected $API_VERSION = 'v1';

	 /**
     *  Refresh oAuth Token
     *
     */
    public function refreshOAuthToken ( $refresh_token, $client_id ) {
        $endpoint = 'refresh';
        $data = array(
            'refresh_token' => $refresh_token,
            'client_id' => $client_id,
            'grant_type' => 'refresh_token'
        );
        $param_string = '&'.http_build_query($data,'','&');
        try{
            return json_decode($this->execute_post_request($this->get_auth_request_url($endpoint,null),$param_string));
        }
        catch(HubSpot_Exception $e){
            print_r("Unable to Refresh Token: ".$e);
        }
    } // function

} // class
endif;


