<?php

namespace Drupal\sumup;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sumup\SumUpOAuth2Service;
use Drupal\encrypt\EncryptService;
use Drupal\encrypt\Entity\EncryptionProfile;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure settings for sumup application registered account.
 */
class SumUpAuthForm extends ConfigFormBase {
    /**
     * @var \Drupal\encrypt\EncryptService
     */
    protected $encryption_service;

    /**
     * @var \Drupal\encrypt\Entity\EncryptionProfile
     */
    protected $encryption_profile;

    /**
     * @var \Drupal\sumup\SumUpOAuth2Service
     */
    protected $sumup_auth_service;

    /**
     * Class constructor.
     */
    public function __construct(EncryptService $encryption_service, EncryptionProfile $encryption_profile, SumUpOAuth2Service $sumup_auth_service) {
        $this->encryption_service = $encryption_service;
        $this->encryption_profile = $encryption_profile;
        $this->sumup_auth_service = $sumup_auth_service;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
        // Instantiates this form class.
        return new static(
            // Load the service required to construct this class.
            $container->get('encryption'),
            $container->get('encryption_profile'),
            $container->get('sumup.oauth2_authenticate')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'sumup_registered_app_settings';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
            'sumup.registered_app_settings'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config('sumup.registered_app_settings');

        $encryption_profile_id = $this->encryption_profile;
        $profile = $encryption_profile->load($encryption_profile_id);

        $scope_opts = array(
            'Transactions History' => 'transactions.history',
            'User App Settings' => 'user.app-settings',
            'User Profile (readonly)' => 'user.profile_readonly',
            'User Profile' => 'user.profile',
            'User Subaccounts' => 'user.subaccounts',
            'User Payout Settings' => 'user.payout-settings',
            'Products' => 'products',
            'Payments' => 'payments',
            'Payment Instruments' => 'payment_instruments'
        );

        $form['sumup_client_id'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Client ID'),
            '#description' => $this->t('Client ID supplied when registering your app.'),
            '#default_value' => $config->get('sumup_client_id'),
            '#maxlength' => '255',
            '#required' => true
        );

        $form['sumup_client_secret'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Client Secret'),
            '#description' => $this->t('Client secret key supplied when registering app.'),
            '#default_value' => $config->get('sumup_client_secret'),
            '#maxlength' => '255',
            '#required' => true
        );

        $form['sumup_redirect_uri'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Redirect Uri'),
            '#description' => $this->t('Redirect after authorization.'),
            '#default_value' => $config->get('sumup_redirect_uri'),
            '#maxlength' => '255',
            '#required' => true
        );

        $form['sumup_key_encryption_setting'] = array(
            '#type' => 'entity_autocomplete',
            '#title' => $this->t('Encryption Profile'),
            '#description' => $this->t('Associate an encryption profile to safely store your keys.'),
            '#target_type' => 'encryption_profile',
            '#selection_handler' => 'default',
            '#default_value' => ($config->get('sumup_key_encryption_setting')) ? $profile : NULL,
            '#size' => 30,
            '#maxlength' => 1024,
        );

        $form['sumup_application_scopes'] = array(
            '#type' => 'select',
            '#title' => $this->t('Application Scopes'),
            '#empty_value' => $this->t('--Select App Scopes--'),
            '#description' => $this->t('Add permission scopes to be granted for the application.'),
            '#default_value' => $config->get('sumup_application_scopes'),
            '#multiple' => true
        );

        $form['actions']['oauth_request'] = array(
            '#type' => 'button',
            '#value' => $this->t('Authorize'),
            '#submit' => array('process_oauth')
        );

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        parent::submitForm($form, $form_state);
    }

    /**
     * Generate authorization key process.
     */
    protected function process_oauth() {
        $encryption_service = $this->encryption_service;
        $sumup = $this->sumup_auth_service;
        // set field before processing authentication request.
        $sumup->requestScopeAccess();
        return;
    }
}