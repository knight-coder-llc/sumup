<?php

namespace Drupal\sumup;

use GuzzleHttp\Client;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

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

    public function __construct(ConfigFactoryInterface $config_factory, Client $http_client){
        $this->config_factory = $config_factory;
        $this->http_client = $http_client;
    }
    /**
     * Request scope access confirmation.
     */
    public function requestScopeAccess() {
        
        $config = $this->config_factory('sumup.registered_app_settings');
        $client = $this->http_client;

        $client_id = $config->get('sumup_client_id');
        $redirect_uri = $config->get('sumup_redirect_uri');
        $scope = $config->get('sumup_application_scopes');
        /**
         * response_type
         * client_id
         * redirect_uri
         * scope
         * state
         */
        $opts = array(
            'response_type' => 'code',
            'client_id' => $client_id,
            'redirect_uri' => $redirect_uri,
            'scope' => $scope
        );

        \Drupal::logger('sumup', print_r('Options: ' . $opts, true));

        // $response = $client->get('https://api.sumup.com/authorize', $opts);
        
        // if($response->getStatusCode() != 200) {
        //     \Drupal::logger('sumup', print_r('Response Status: ' . $response->getStatusCode(), true));
        //     return;
        // }

        // \Drupal::logger('sumup', print_r('Response Status: ' . $response->getStatusCode(), true));

    }

    public function requestToken() {}

    public function validateResponse() {}

    protected function saveAccessToken() {}
}