<?php

namespace Drupal\sumup;

use GuzzleHttp\Client;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\Serializer\Serializer;

/**
 * Implements OAuth2 authentication process.
 */
class SumUpOAuth2Service {
    use StringTranslationTrait;

    /**
     * @var \Drupal\Core\Config\ConfigFactoryInterface
     */
    protected $config_factory;

    /**
     * @var \GuzzleHttp\Client
     */
    protected $http_client;

    /**
     * @var \Drupal\Core\State\StateInterface
     */
    protected $state_system;

    public function __construct(ConfigFactoryInterface $config_factory, Client $http_client, StateInterface $state_system){
        $this->config_factory = $config_factory;
        $this->http_client = $http_client;
        $this->state_system = $state_system;
    }

    /**
     * Request scope access confirmation.
     */
    public function requestScopeAccess() {
        
        $config = $this->config_factory->get('sumup.registered_app_settings');
        $client = $this->http_client;
        // send client_id using base64 encoding.
        $client_id = base64_encode($config->get('sumup_client_id'));
        $redirect_uri = $config->get('sumup_redirect_uri');
        $scopes = $config->get('sumup_application_scopes');

        /** response_type, client_id, redirect_uri, scope, state */
        $opts = array(
            'response_type' => 'code',
            'client_id' => $client_id,
            'redirect_uri' => $redirect_uri,
            'scope' => $scopes
        );

        $response = $client->request('GET', 'https://api.sumup.com/authorize', $opts);
        
        if($response->getStatusCode() != 200) {
            \Drupal::logger('sumup', print_r('OAuth2 Flow Response Status: ' . $response->getStatusCode(), true));
        }

        $payload = json_decode($response->getBody()->getContents(), true);
        
        return print_r($payload, true);
    }

    /**
     * Request using client credentials flow authorization
     */
    public function clientCredentialsFlow() {
        $config = $this->config_factory->get('sumup.registered_app_settings');
         // load the named encryption profile
        $encryption_profile_id = $config->get('sumup_key_encryption_setting');
        $encryption_profile = EncryptionProfile::load($encryption_profile_id);
        $encryption_service = \Drupal::service('encryption');

        $client = $this->http_client;

        // send using base64 encoding
        $client_id = base64_encode($config->get('sumup_client_id'));
        // decode/ send using base64
        $client_secret = base64_encode($encryption_service->decrypt($config->get('sumup_client_secret'), $encryption_profile));

        $redirect_uri = $config->get('sumup_redirect_uri');
        $scopes = $config->get('sumup_application_scopes');

        /** grant_type, client_id, client_secret */
        $post_data = array(
            'grant_type' => 'client_credentials',
            'client_id' => $client_id,
            'client_secret' => $client_secret,
        );
        
        $response = $client->request('POST', 'https://api.sumup.com/token', [
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'form_params' => $post_data,
            'http_errors' => false
        ]);
        
        if($response->getStatusCode() != 200) {
            \Drupal::logger('sumup', print_r('Client Credentials Flow Response Status: ' . $response->getStatusCode(), true));
        }

        $payload = json_decode($response->getBody()->getContents(), true);
        if(isset($payload["error_message"])) {
            $msg = $payload["error_message"];
        } else {
            $msg = print_r($payload, true);
        }

        return $msg;
    
    }

    /**
     * Request access token  with authorization code. 
     * 
     * @var integer
     */
    public function requestToken($code) {
        $config = $this->config_factory('sumup.registered_app_settings');
        $client = $this->http_client;

        // load the named encryption profile
        $encryption_profile_id = $config->get('sumup_key_encryption_setting');
        $encryption_profile = EncryptionProfile::load($encryption_profile_id);
        $encryption_service = \Drupal::service('encryption');

        $client_id = base64_encode($config->get('sumup_client_id'));
        $client_secret = base64_encode($encryption_service->decrypt($config->get('sumup_client_secret'), $encryption_profile));
        $redirect_uri = $config->get('sumup_redirect_uri');

        /** grant_type, client_id, client_secret, redirect_uri, code */
        $post_data = array(
            'grant_type' => 'authorization_code',
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri' => $redirect_uri,
            'code' => $code
        );

        $response = $client->request('POST','https://api.sumup.com/token', [
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'form_params' => $post_data,
            'http_errors' => false
        ]);
        
        if($response->getStatusCode() != 200) {
            \Drupal::logger('sumup', print_r('Response Status: ' . $response->getStatusCode(), true));
            return;
        }

        $payload = json_decode($response->getBody(), true);
        
        return $payload;
    }

    /**
     * Refresh token process
     */
    public function refreshAccessToken() {
       
        $state = $this->state_system;
        $expire_time = $state->get('sumup.token_timestamp');
        $now = time();

        // if we have not hit expired time why call for a refresh token.
        if($expire_time >= $now) {
            return;
        }

        $config = $this->config_factory('sumup.registered_app_settings');
        $client = $this->http_client;

        // load the named encryption profile
        $encryption_profile_id = $config->get('sumup_key_encryption_setting');
        $encryption_profile = EncryptionProfile::load($encryption_profile_id);
        $encryption_service = \Drupal::service('encryption');

        $client_id = base64_encode($config->get('sumup_client_id'));
        $client_secret = base64_encode($encryption_service->decrypt($config->get('sumup_client_secret'), $encryption_profile));
        $redirect_uri = $config->get('sumup_redirect_uri');

        $refresh_token = $state->get('sumup.refresh_token');

        if(!$refresh_token) {
            return;
        }

        /** grant_type, client_id, client_secret, redirect_uri, code */
        $post_data = array(
            'grant_type' => 'refresh_token',
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'refresh_token' => $refresh_token
        );

        $response = $client->request('POST','https://api.sumup.com/token', [
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'form_params' => $post_data
        ]);
        
        if($response->getStatusCode() != 200) {
            \Drupal::logger('sumup', print_r('Refresh Token Response Status: ' . $response->getStatusCode(), true));
            return;
        }
        /** access_token, token_type: Bearer, expires_in, refresh_token, scope */
        $payload = json_decode($response->getBody()->getContents(), true);
        if(isset($payload["error_message"])) {
            return $payload["error_message"];
        }
        
        $this->saveAccessToken($payload);

        return;

    }

    public function validateResponse() {}

    /**
     * Save token access to state api.
     * 
     * @param array $payload
     */
    public function saveAccessToken($payload) {
        $state = $this->state_system;
        $expire_time = time() + $payload["expires_in"];

        $state->setMultiple([
            'sumup.access_token' => (isset($payload["access_token"])) ? $payload["access_token"] : null,
            'sumup.token_type' => (isset($payload["token_type"])) ? $payload["token_type"] : null,
            'sumup.expires_in' => (isset($payload["expires_in"])) ? $payload["expires_in"] : null,
            'sumup.refresh_token' => (isset($payload["refresh_token"])) ? $payload["refresh_token"] : null,
            'sumup.token_timestamp' => $expire_time
        ]);
    }
}