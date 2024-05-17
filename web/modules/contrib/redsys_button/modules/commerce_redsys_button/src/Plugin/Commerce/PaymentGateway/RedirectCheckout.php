<?php

namespace Drupal\commerce_redsys_button\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_price\Price;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\redsys_button\lib\redsys\RedSys;
use Drupal\redsys_button\lib\redsys\Utils\Utils;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the Drupal Commerce Redsys offsite redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "redsys_redirect_payment_checkout",
 *   label = @Translation("Redsys (Redirect to Redsys)"),
 *   display_label = @Translation("Redsys"),
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_redsys_button\PluginForm\RedsysPaymentForm",
 *   },
 *   payment_method_types = {"credit_card"},
 * )
 */
class RedirectCheckout extends OffsitePaymentGatewayBase {

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The RedSys service.
   *
   * @var \Drupal\redsys_button\lib\redsys\RedSys
   */
  protected $redSys;

  /**
   * The Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructor for RedirectCheckout.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_payment\PaymentTypeManager $payment_type_manager
   *   The payment type manager.
   * @param \Drupal\commerce_payment\PaymentMethodTypeManager $payment_method_type_manager
   *   The payment method type manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    PaymentTypeManager $payment_type_manager,
    PaymentMethodTypeManager $payment_method_type_manager,
    TimeInterface $time,
    LoggerInterface $logger,
    $configFactory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);
    $this->logger = $logger;
    $this->configFactory = $configFactory;
    $this->redSys = new RedSys($this->configFactory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_payment_type'),
      $container->get('plugin.manager.commerce_payment_method_type'),
      $container->get('datetime.time'),
      $container->get('logger.channel.commerce_redsys_button'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    $orderId = $order->id();
    if (empty($orderId)) {
      throw new PaymentGatewayException('Invoice id missing for this transaction.');
    }
    $this->logger->log('info', 'onReturn');
  }

  /**
   * {@inheritdoc}
   */
  public function onCancel(OrderInterface $order, Request $request) {
    $this->logger->info('The user canceled payment process for order %order_id', [
      '%order_id' => $order->id(),
    ]);
    parent::onCancel($order, $request);
  }

  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request) {
    if (!$request->getContent()) {
      throw new PaymentGatewayException('Response data from TPV missing, aborting.');
    }

    $this->processFeedback($request);
  }

  /**
   * Common response for all notifications, from Redsys.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentInterface|null
   *   The payment entity, or NULL in case of an exception.
   */
  private function processFeedback(Request $request) {
    $params = [
      'Ds_SignatureVersion' => $request->get('Ds_SignatureVersion'),
      'Ds_MerchantParameters' => $request->get('Ds_MerchantParameters'),
      'Ds_Signature' => $request->get('Ds_Signature'),
    ];
    if (empty($params['Ds_SignatureVersion']) || empty($params['Ds_MerchantParameters']) || empty($params['Ds_Signature'])) {
      throw new PaymentGatewayException('Bad feedback response, missing feedback parameter.');
    }
    $config = $this->configFactory->get('redsys_button.settings');
    $decodedParams = Utils::decodeParameters($params['Ds_MerchantParameters']);
    $order = Utils::jsonToArray($decodedParams)['Ds_Order'];
    $this->redSys->setOrder($order);
    $this->redSys->generateMerchantSignature($config->get('merchant_key'));
    $signatureCalc = $this->redSys->getMerchantSignature();
    if ($signatureCalc === $params['Ds_Signature']) {
      $dsResponse = Utils::jsonToArray($decodedParams)['Ds_Response'];
      if ($dsResponse == '0000') {
        $authcode = Utils::jsonToArray($decodedParams)['Ds_AuthorisationCode'];
        $amount = Utils::jsonToArray($decodedParams)['Ds_Amount'];
        $order = Utils::jsonToArray($decodedParams)['Ds_Order'];
        $currency = Utils::jsonToArray($decodedParams)['Ds_Currency'];
        $price = strval($amount / 100);
        $paymentStorage = $this->entityTypeManager->getStorage('commerce_payment');
        $payment = $paymentStorage->create([
          'state' => 'complete',
          'amount' => new Price($price, "€"),
          'currency_code' => $currency,
          'payment_gateway' => $this->getPluginId(),
          'order_id' => $order,
          'remote_id' => $authcode,
          'remote_state' => $dsResponse,
          'authorized' => $this->time->getRequestTime(),
        ]);
        $payment->save();
        \Drupal::messenger()->addStatus($this->t('The payment is received, thank you'));
        return $payment;
      }
    }
    else {
      \Drupal::messenger()->addError($this->t('No payment received, please try again or select a different payment method'));
    }
  }

}
