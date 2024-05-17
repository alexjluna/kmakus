<?php

namespace Drupal\redsys_button\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class to configure the Redsys Button parameters.
 */
class RedsysButtonConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['redsys_button.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'redsys_button_config_form';
  }

  /**
   * Builds the configuration form.
   *
   * @param array $form
   *   The form definition array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The modified form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('redsys_button.settings');

    $form['environment'] = [
      '#type' => 'select',
      '#title' => $this->t('Environment'),
      '#default_value' => $config->get('environment'),
      '#options' => [
        'test' => $this->t('Test'),
        'live' => $this->t('Live'),
      ],
      '#description' => $this->t('Select the payment gateway environment.'),
    ];

    $form['url_test'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Test URL'),
      '#default_value' => $config->get('url_test'),
      '#description' => $this->t('The URL for the test environment of the payment gateway.'),
    ];

    $form['url_live'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Live URL'),
      '#default_value' => $config->get('url_live'),
      '#description' => $this->t('The URL for the live environment of the payment gateway.'),
    ];

    $form['signatureversion'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Signature Version'),
      '#default_value' => $config->get('signatureversion'),
      '#description' => $this->t('The version of the signature algorithm used.'),
    ];

    $form['merchant_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Commercial Code'),
      '#default_value' => $config->get('merchant_code'),
      '#required' => TRUE,
    ];

    $form['merchant_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret key of trade'),
      '#default_value' => $config->get('merchant_key'),
      '#required' => TRUE,
    ];

    $form['terminal'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Terminal'),
      '#default_value' => $config->get('terminal'),
      '#description' => $this->t('If you doubt, leave it on 001'),
      '#required' => TRUE,
    ];

    $form['currency'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Currency'),
      '#default_value' => $config->get('currency', '978'),
      '#description' => $this->t('978 for Euros, 840 for Dollars, etc.'),
    ];

    $form['transaction_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Transaction Type'),
      '#default_value' => $config->get('transaction_type', '0'),
      '#description' => $this->t('The type of transaction to be processed.'),
    ];

    $form['language'] = [
      '#type' => 'select',
      '#title' => $this->t('Language'),
      '#default_value' => $config->get('language'),
      '#options' => [
        '001' => $this->t('Spanish'),
        '002' => $this->t('English'),
        '003' => $this->t('Catalan'),
        '004' => $this->t('French'),
        '005' => $this->t('German'),
        '006' => $this->t('Dutch'),
        '007' => $this->t('Italian'),
        '008' => $this->t('Swedish'),
        '009' => $this->t('Portuguese'),
        '010' => $this->t('Valencian'),
        '011' => $this->t('Polish'),
        '012' => $this->t('Galician'),
        '013' => $this->t('Basque'),
      ],
      '#description' => $this->t('Select the language for the payment gateway.'),
    ];

    $form['notification_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Notification Email'),
      '#default_value' => $config->get('notification_email'),
      '#description' => $this->t('Email address to receive payment notifications.'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Handles the submission of the configuration form.
   *
   * @param array &$form
   *   The form definition array for configuration.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('redsys_button.settings')
      ->set('environment', $form_state->getValue('environment'))
      ->set('url_test', $form_state->getValue('url_test'))
      ->set('url_live', $form_state->getValue('url_live'))
      ->set('signatureversion', $form_state->getValue('signatureversion'))
      ->set('merchant_code', $form_state->getValue('merchant_code'))
      ->set('merchant_key', $form_state->getValue('merchant_key'))
      ->set('terminal', $form_state->getValue('terminal'))
      ->set('currency', $form_state->getValue('currency'))
      ->set('transaction_type', $form_state->getValue('transaction_type'))
      ->set('language', $form_state->getValue('language'))
      ->set('notification_email', $form_state->getValue('notification_email'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
