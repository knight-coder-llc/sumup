<?php

namespace Drupal\sumup\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\sumup\SumUpOAuth2Service;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for sumup module.
 */
class SumUpController extends ControllerBase {

    /**
     * @var \Guzzle\Client
     */
    protected $http_client;

    /**
     * @var Symfony\Component\HttpFoundation\RequestStack
     */
    protected $request_stack;

    /**
     * The serializer
     * 
     * @var \Symfony\Component\Serializer\Serializer
     */
    protected $serializer;

    /**
     * The state system.
     * 
     * @var Drupal\Core\State\StateInterface;
     */
    protected $state_system;

    /**
     * configuration settings.
     * 
     * @var Drupal\Core\Config\ConfigFactoryInterface;
     */
    protected $config_factory;

    /**
     * The sumup authorization service.
     * 
     * @var Drupal\sumup\SumUpOAuth2Service;
     */
    protected $sumup_service;

    /**
     * Constructor method for SumUpController.
     * 
     * @param
     */
    public function __construct(
        Client $http_client, 
        RequestStack $request_stack, 
        Serializer $serializer, 
        StateInterface $state_system, 
        ConfigFactoryInterface $config_factory, 
        SumUpOAuth2Service $sumup_service) {

        $this->config_factory = $config_factory->get('sumup.registered_app_settings');
        $this->http_client = $http_client;
        $this->request_stack = $request_stack;
        $this->serializer = $serializer;
        $this->state_system = $state_system;
        $this->sumup_service = $sumup_service;

    }   

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('http_client'),
            $container->get('request_stack'),
            $container->get('serializer'),
            $container->get('state'),
            $container->get('config.factory'),
            $container->get('sumup.oauth2_authenticate')
        );
    }

    /**
     * Authorization application callback.
     */
    public function oauth_sumup_callback() {

        $config = $this->config_factory;
        $request_stack = $this->request_stack;
        $request = $request_stack->getCurrentRequest();
        $sumup_service = $this->sumup_service;

        // $payload = $this->decode(
        //     $request->getContent(),
        //     $request->getContentType()
        // );

        $payload = json_decode($request->getBody()->getContents(), true);

        // request access token.
        /** Incoming: code, state, error */
        if(isset($payload["error"])) {
            try {
                $sumup_service->refreshAccessToken();
            } finally {
               \Drupal::logger('sumup', print_r('Error Status: ' . $payload["error"], true)); 
            }
            return;
        }

        if(isset($payload["code"])) {
            $code = $payload["code"];
            $sumup_service->requestToken($code);
            \Drupal::logger('sumup', 'Requested Token.'); 
        }
        
        // store the payload.
        /** Incoming: access_token, token_type, expires_in, refresh_token */
        if(isset($payload["access_token"])) {
            // encryption services?
            $sumup_service->saveAccessToken($payload);
            \Drupal::logger('sumup', 'Saved token to state.');   
        }

        return;
    }

    /**
     * {@inheritdoc}
     */
    public function decode($data, $format, array $context = []) {
        return $this->serializer->decode($data, $format);
    }
}