<?php

namespace Drupal\sumup\Form;

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
    public function __construct(EncryptService $encryption_service, SumUpOAuth2Service $sumup_auth_service) {
        $this->encryption_service = $encryption_service;
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
        $encryption_profile = $this->encryption_profile;
        $encryption_profile_id = $config->get('sumup_key_encryption_setting');
        $profile = EncryptionProfile::load($encryption_profile_id);
        $encryption_service = $this->encryption_service;

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
            '#default_value' => ($config->get('sumup_client_secret')) ? $encryption_service->decrypt($config->get('sumup_client_secret'), $profile) : NULL,
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
            '#required' => true
        );

        $form['sumup_application_scopes'] = array(
            '#type' => 'select',
            '#title' => $this->t('Application Scopes'),
            '#empty_value' => $this->t('--Select App Scopes--'),
            '#options' => $scope_opts,
            '#description' => $this->t('Add permission scopes to be granted for the application.'),
            '#default_value' => $config->get('sumup_application_scopes'),
            '#multiple' => true
        );

        $form['sumup_client_credentials_flow'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Use Client Credentials Flow Authentication'),
            '#default_value' => $config->get('sumup_client_credentials_flow'),
            '#return_value' => true
        );

        $form['actions']['oauth_request'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Authorize'),
            '#submit' => array('::process_oauth'),
            '#disabled' => ($form_state->isSubmitted()) ? true : false
        );

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        // load the named encryption profile
        $encryption_profile_id = $form_state->getValue('sumup_key_encryption_setting');
        $encryption_profile = EncryptionProfile::load($encryption_profile_id);
        $encryption_service = \Drupal::service('encryption');

        // save the configured form settings.
        $this->configFactory
        ->getEditable('sumup.registered_app_settings')
        ->set('sumup_client_id',$form_state->getValue('sumup_client_id'))
        ->set('sumup_client_secret',$encryption_service->encrypt($form_state->getValue('sumup_client_secret'), $encryption_profile))
        ->set('sumup_redirect_uri',$form_state->getValue('sumup_redirect_uri'))
        ->set('sumup_key_encryption_setting', $form_state->getValue('sumup_key_encryption_setting'))
        ->set('sumup_application_scopes', $form_state->getValue('sumup_application_scopes'))
        ->set('sumup_client_credentials_flow', $form_state->getValue('sumup_client_credentials_flow'))
        ->save();
    
        parent::submitForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
        parent::validateForm($form, $form_state);
    }

    /**
     * Generate authorization key process.
     */
    public function process_oauth() {
        $config = $this->config('sumup.registered_app_settings');
        $encryption_service = $this->encryption_service;
        $sumup = $this->sumup_auth_service;
        // set field before processing authentication request.
        $access_flow = $config->get('sumup_client_credentials_flow');

        if(!$access_flow) {
          $payload = $sumup->requestScopeAccess();  
        } else {
            $payload = $sumup->clientCredentialsFlow();
        }

        $messenger = \Drupal::messenger();
        $messenger->addMessage($payload);
        
        return;
    }
}