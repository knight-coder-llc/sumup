<?php

namespace Drupal\sumup;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\State\StateInterface;
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
     * @var \Symfony\Component\Serializer\Serializer
     */
    protected $serializer;

    /**
     * Constructor method for SumUpController.
     * 
     * @param
     */
    public function __construct(Client $http_client, RequestStack $request_stack, Serializer $serializer) {
        $this->http_client = $http_client;
        $this->request_stack = $request_stack;
        $this->serializer = $serializer;
    }   

    /**
     * request granted redirect
     */
    public function oauth_sumup_callback() {
        $request_stack = $this->request_stack;
        $request = $request_stack->getCurrentRequest();
        
        /**
         * code
         * state
         */
        $payload = $this->decode(
            $request->getContent(),
            $request->getContentType()
        );

        $headers = $request->headers->all();
        // $request->headers->get('Content-Type')

        /**
         * request access token
         * 
         * grant_type
         * client_id
         * client_secret
         * redirect_uri
         * code
         * 
         * function somefunction($code)
         * 
         * access_token
         * token_type
         * expires_in
         * refresh_token
         * 
         * request resources
         */
        return;
    }

    /**
     * {@inheritdoc}
     */
    public function decode($data, $format, array $context = []) {
        return $this->serializer->decode($data, $format);
    }
}