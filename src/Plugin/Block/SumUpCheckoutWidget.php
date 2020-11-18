<?php
namespace Drupal\sumup\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * SumUpCheckoutWidget Block.
 * 
 * @Block {
 *  id = "sumup_checkout_widget_block"
 *  admin_label = @Translation("Sumup Checkout Widget"),
 * }
 */
class SumUpCheckoutWidget extends BlockBase implements ContainerFactoryPluginInterface {

    /**
     * Construct.
     * 
     */
    public function __construct(array $configuration, $plugin_id, $plugin_definition) {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition
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
                    'sumup.gateway',
                    'sumup.card'
                ]
            ]
        ];
    }
}