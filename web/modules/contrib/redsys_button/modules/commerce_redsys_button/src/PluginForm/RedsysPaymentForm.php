<?php

namespace Drupal\commerce_redsys_button\PluginForm;

use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\redsys_button\lib\redsys\RedSys;

/**
 * Provides a form for handling the offsite payment process with RedSys.
 */
class RedsysPaymentForm extends BasePaymentOffsiteForm {

  /**
   * Builds the offsite payment form.
   *
   * This method constructs the form used to redirect the user to the RedSys.
   * It sets necessary values for the transaction based on the configuration.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state interface.
   *
   * @return array
   *   The form array with the redirection data appended.
   *
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   Throws an exception if there is an error building the payment form.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $config = $this->getConfiguration();

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();

    try {
      $redSys = new RedSys(\Drupal::service('config.factory'));

      // Set necessary values for the transaction.
      $orderId = "0000" . $payment->getOrder()->id();
      $amount = $payment->getAmount()->getNumber();

      // URL to receive HTTP notification after payment.
      $merchant_url = $payment_gateway_plugin->getNotifyUrl()->toString();

      // Set RedSys parameters.
      $redSys->setMerchantCode($config['merchant_code']);
      $redSys->setCurrency($config['currency']);
      $redSys->setTransactionType($config['transaction_type']);
      $redSys->setTerminal((int) ($config['terminal'] ?? 1));
      $redSys->setNotification($merchant_url);
      $redSys->setUrlOk($form['#return_url']);
      $redSys->setUrlKo($form['#cancel_url']);
      $redSys->setAmount($amount);
      $redSys->setOrder($orderId);

      // Generate the merchant parameters and signature.
      $redSys->generateMerchantSignature($config['merchant_key']);
      $params = $redSys->generateMerchantParameters();
      $signature = $redSys->getMerchantSignature();

      $data = [
        'Ds_SignatureVersion' => $config['signatureversion'],
        'Ds_MerchantParameters' => $params,
        'Ds_Signature' => $signature,
      ];
    }
    catch (\Exception $exception) {
      throw new PaymentGatewayException('Error Building Payment form: ' . $exception->getMessage());
    }

    // Determine the appropriate redirect URL based on the environment mode.
    $redirect_url = $config['environment'] == 'test' ? $config['url_test'] : $config['url_live'];

    return $this->buildRedirectForm($form, $form_state, $redirect_url, $data, self::REDIRECT_POST);
  }

  /**
   * Retrieves the current payment gateway configuration combined.
   *
   * @return array
   *   The combined configuration array of the payment gateway plugin.
   */
  private function getConfiguration() {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $paymentGatewayPlugin */
    $paymentGatewayPlugin = $payment->getPaymentGateway()->getPlugin();

    // Retrieve the payment gateway configuration.
    $gatewayConfig = $paymentGatewayPlugin->getConfiguration();

    // Access the stored configuration from RedsysButtonConfigForm.
    $redsysButtonConfig = \Drupal::config('redsys_button.settings')->get();

    // Combine both configurations.
    return array_merge($gatewayConfig, $redsysButtonConfig);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_redsys_payment_form';
  }

}
