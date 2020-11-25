<?php
namespace Drupal\sumup\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * SumUpCheckoutWidget Block.
 * 
 * @Block (
 *  id = "sumup_checkout_widget_block",
 *  admin_label = @Translation("Sumup Checkout Widget"),
 *  category = @Translation("Sumup Block")
 * )
 */
class SumUpCheckoutWidget extends BlockBase implements ContainerFactoryPluginInterface {

    /**
     * @var \Drupal\Core\State\StateInterface
     */
    protected $state_system;

    /**
     * Construct.
     * 
     */
    public function __construct(array $configuration, $plugin_id, $plugin_definition, StateInterface $state_system) {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
        $this->state_system = $state_system;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->get('state')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function build() {
        return [
            '#markup' => '<div id="sumup-card"></div>',
            '#attached' => [
                'library' => [
                    'sumup/sumup.card'
                ],
                'drupalSettings' => [
                    'state_api' => [ 
                        'access_token' => $this->getAccessToken() 
                        ]
                ]
            ]
        ];
    }

    public function getAccessToken() {
        $state = $this->state_system;
        $access_token = $state->get('sumup.access_token');

        return $access_token;
    }
}