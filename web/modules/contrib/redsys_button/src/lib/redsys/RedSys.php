<?php

namespace Drupal\redsys_button\lib\redsys;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\redsys_button\lib\redsys\Exception\RedSysException;
use Drupal\redsys_button\lib\redsys\Messages\RedSysMessages;
use Drupal\redsys_button\lib\redsys\Utils\Utils;
use Drupal\redsys_button\lib\redsys\Utils\Validators;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * RedSys class.
 *
 * Manages payments through this payment gateway.
 */
class RedSys {

  use StringTranslationTrait;

  /**
   * The signature of the transaction.
   *
   * @var string
   */
  protected $signature;

  /**
   * The version of the RedSys API being used.
   *
   * @var string
   */
  protected $version;

  /**
   * Parameters for the RedSys transaction.
   *
   * @var array
   */
  protected $parameters;

  /**
   * The environment URL for the transaction (test or live).
   *
   * @var string
   */
  protected $environment;

  /**
   * The XML environment URL for SOAP transactions (test or live).
   *
   * @var string
   */
  protected $environmentXml;

  /**
   * The name of the form for RedSys transactions.
   *
   * @var string
   */
  protected $nameForm;

  /**
   * The ID of the form for RedSys transactions.
   *
   * @var string
   */
  protected $idForm;

  /**
   * The name of the submit button for RedSys transactions.
   *
   * @var string
   */
  protected $nameSubmit;

  /**
   * The ID of the submit button for RedSys transactions.
   *
   * @var string
   */
  protected $idSubmit;

  /**
   * The value (label) of the submit button for RedSys transactions.
   *
   * @var string
   */
  protected $valueSubmit;

  /**
   * Inline CSS styles for the submit button.
   *
   * @var string
   */
  protected $styleSubmit;

  /**
   * CSS class(es) for the submit button.
   *
   * @var string
   */
  protected $classSubmit;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The merchant's secret key.
   *
   * @var string
   */
  protected $merchantKey;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
    $config = $this->configFactory->get('redsys_button.settings');
    $this->setEnvironment($config->get('environment'));
    $this->parameters = [];
    $this->setMerchantCode($config->get('merchant_code'));
    $this->merchantKey = $config->get('merchant_key');
    $this->setCurrency($config->get('currency') ?? 978);
    $this->setTransactionType($config->get('transaction_type') ?? '0');
    $terminal = $config->get('terminal') ?? 1;
    $this->setTerminal(is_numeric($terminal) ? (int) $terminal : 1);
    $this->setLanguage($config->get('language') ?? '001');
    $this->setMethod($config->get('payment_method') ?? 'C');
    $this->version = 'HMAC_SHA256_V1';
    $this->setIdentifier($config->get('identifier') ?? 'REQUIRED');

    // Default values for the form, can be customized further if needed.
    $this->setNameForm('redsys_form');
    $this->setIdForm('redsys_form');
    $this->setAttributesSubmit('btn_submit', 'btn_submit', $this->t('Send'), '', '');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * Sets the DS_MERCHANT_IDENTIFIER used for recurrent purchases.
   *
   * @param string $value
   *   This parameter will be used to handle the reference associated with the
   *   card data.
   *
   * @throws Drupal\redsys_button\lib\redsys\Exception\RedSysException
   */
  public function setIdentifier(string $value) {
    if (Validators::isEmpty($value)) {
      throw new RedSysException('Please add value');
    }
    else {
      $this->parameters['DS_MERCHANT_IDENTIFIER'] = $value;
    }
  }

  /**
   * Indicates if additional screens should be shown.
   *
   * @param bool $flat
   *   If TRUE is passed, no additional screens will be shown.
   *
   * @throws Drupal\redsys_button\lib\redsys\Exception\RedSysException
   */
  public function setMerchantDirectPayment(bool $flat) {
    if (!is_bool($flat)) {
      throw new RedSysException('Please set true or false');
    }
    else {
      $this->parameters['DS_MERCHANT_DIRECTPAYMENT'] = $flat;
    }
  }

  /**
   * Sets the amount to be charged, using the dot as decimal separator.
   *
   * @param float $amount
   *   The amount to charge.
   *
   * @throws Drupal\redsys_button\lib\Exception\RedSysException
   */
  public function setAmount(float $amount) {
    if ($amount < 0) {
      throw new RedSysException('Amount must be greater than or equal to 0.');
    }
    else {
      $amount = intval(strval($amount * 100));
      $this->parameters['DS_MERCHANT_AMOUNT'] = $amount;
    }
  }

  /**
   * Sets the total sum to be charged in case of recurrent payments.
   *
   * @param float $sumTotal
   *   The amount to charge.
   *
   * @throws Drupal\redsys_button\Exception\RedSysException
   */
  public function setSumTotal(float $sumTotal) {
    if ($sumTotal < 0) {
      throw new RedSysException('Sum total must be greater than or equal to 0.');
    }
    else {
      $sumTotal = intval(strval($sumTotal * 100));
      $this->parameters['DS_MERCHANT_SUMTOTAL'] = $sumTotal;
    }
  }

  /**
   * Sets the order number.
   *
   * @param string $order
   *   The order number.
   *
   * @throws Drupal\redsys_button\lib\redsys\Exception\RedSysException
   */
  public function setOrder(string $order) {
    $order = trim($order);
    if (!Validators::isValidOrder($order)) {
      throw new RedSysException('Order id must be a 4 digit string at least, maximum 12 characters.');
    }
    else {
      $this->parameters['DS_MERCHANT_ORDER'] = $order;
    }
  }

  /**
   * Returns the assigned order number.
   *
   * @return string
   *   The order number.
   */
  public function getOrder() {
    return $this->parameters['DS_MERCHANT_ORDER'];
  }

  /**
   * Sets the FUC code of the store.
   *
   * @param string|null $fuc
   *   The FUC code of the commerce. If null or empty, the FUC is not set.
   */
  public function setMerchantCode(?string $fuc) {
    if (!empty($fuc)) {
      $this->parameters['DS_MERCHANT_MERCHANTCODE'] = $fuc;
    }
  }

  /**
   * Sets the ISO-4217 currency code to use.
   *
   * @param int $currency
   *   The ISO code to use.
   *   By default, it is set to 978 => Euro.
   *
   * @see https://en.wikipedia.org/wiki/ISO_4217
   *
   * @throws Drupal\redsys_button\lib\redsys\Exception\RedSysException
   */
  public function setCurrency(int $currency = 978) {
    if (!preg_match('/^[0-9]{3}$/', $currency)) {
      throw new RedSysException('Currency is not valid');
    }
    else {
      $this->parameters['DS_MERCHANT_CURRENCY'] = $currency;
    }
  }

  /**
   * Indicates the type of transaction.
   *
   * @param string $transaction
   *   The type of transaction being performed.
   *   Its default value is 0 - Authorization.
   *
   * @throws Drupal\redsys_button\lib\redsys\Exception\RedSysException
   */
  public function setTransactionType(string $transaction) {
    if (Validators::isEmpty($transaction)) {
      throw new RedSysException('Please add transaction type');
    }
    else {
      $this->parameters['DS_MERCHANT_TRANSACTIONTYPE'] = $transaction;
    }
  }

  /**
   * Sets the terminal number assigned by your bank.
   *
   * @param int $terminal
   *   The terminal number.
   *
   * @throws Drupal\redsys_button\lib\redsys\Exception\RedSysException
   */
  public function setTerminal(int $terminal) {
    if (intval($terminal) === 0) {
      throw new RedSysException('Terminal is not valid.');
    }
    else {
      $this->parameters['DS_MERCHANT_TERMINAL'] = $terminal;
    }
  }

  /**
   * Sets the URL to which the gateway will send the result of the operation.
   *
   * @param string $url
   *   The complete URL.
   *
   * @throws Drupal\redsys_button\lib\redsys\Exception\RedSysException
   */
  public function setNotification(string $url = '') {
    if (!Validators::isValidUrl($url)) {
      throw new RedSysException('Invalid notification url.');
    }
    else {
      $this->parameters['DS_MERCHANT_MERCHANTURL'] = $url;
    }
  }

  /**
   * Sets the URL to which the user will be redirected in case of successful.
   *
   * @param string $url
   *   The complete redirection URL.
   */
  public function setUrlOk(string $url = '') {
    if (!Validators::isValidUrl($url)) {
      throw new RedSysException('Invalid ok url.');
    }
    else {
      $this->parameters['DS_MERCHANT_URLOK'] = $url;
    }
  }

  /**
   * Sets the URL to which the user will be redirected in case of failed.
   *
   * @param string $url
   *   The complete redirection URL.
   */
  public function setUrlKo(string $url = '') {
    if (!Validators::isValidUrl($url)) {
      throw new RedSysException('Invalid ko url.');
    }
    else {
      $this->parameters['DS_MERCHANT_URLKO'] = $url;
    }
  }

  /**
   * Sets the specific version of the algorithm that is being used for signing.
   *
   * @param string $version
   *   The algorithm version.
   */
  public function setVersion(string $version) {
    if (Validators::isEmpty($version)) {
      throw new RedSysException('Please add version.');
    }
    else {
      $this->version = $version;
    }
  }

  /**
   * Gets the set version of the algorithm.
   *
   * @return string
   *   The algorithm version.
   */
  public function getVersion() {
    return $this->version;
  }

  /**
   * Gets the generated signature from getMerchantSignature().
   *
   * @return string
   *   The signature.
   */
  public function getMerchantSignature() {
    return $this->signature;
  }

  /**
   * Sets the environment to either production or development.
   *
   * @param string $environment
   *   Indicate 'test' or 'live' depending on whether it is development.
   *
   * @throws Drupal\redsys_button\lib\redsys\Exception\RedSysException
   */
  public function setEnvironment($environment = 'test') {
    $environment = trim($environment);
    if ($environment === 'live') {
      // Production.
      $this->environment = 'https://sis.redsys.es/sis/realizarPago/utf-8';
      $this->environmentXml = 'https://sis.redsys.es/sis/services/SerClsWSEntrada?wsdl';
    }
    elseif ($environment === 'test') {
      // Development.
      $this->environment = 'https://sis-t.redsys.es:25443/sis/realizarPago/utf-8';
      $this->environmentXml = 'https://sis-t.redsys.es:25443/sis/services/SerClsWSEntrada?wsdl';
    }
    else {
      throw new RedSysException('Add test or live');
    }
  }

  /**
   * Gets the type of environment we are using.
   *
   * @return string
   *   The environment URL in use.
   */
  public function getEnvironment() {
    return $this->environment;
  }

  /**
   * Gets the type of environment we are using for the SOAP service.
   *
   * @return string
   *   The environment URL in use.
   */
  public function getEnvironmentXml() {
    return $this->environmentXml;
  }

  /**
   * Sets the language for the payment gateway.
   *
   * @param string $languageCode
   *   The language code in use.
   *
   * @throws Drupal\redsys_button\lib\redsys\Exception\RedSysException
   */
  public function setLanguage(string $languageCode) {
    if (!Validators::isValidLangcode($languageCode)) {
      throw new RedSysException('Invalid language code');
    }
    else {
      $this->parameters['DS_MERCHANT_CONSUMERLANGUAGE'] = trim($languageCode);
    }
  }

  /**
   * Allows sending data that will be included in the return from the gateway.
   *
   * @param string $merchantdata
   *   Data to include in the request.
   *
   * @throws Drupal\redsys_button\lib\redsys\Exception\RedSysException
   */
  public function setMerchantData(string $merchantdata = '') {
    if (Validators::isEmpty($merchantdata)) {
      throw new RedSysException('Add merchant data');
    }
    else {
      $this->parameters['DS_MERCHANT_MERCHANTDATA'] = trim($merchantdata);
    }
  }

  /**
   * Sets the name of the purchased product.
   *
   * @param string $description
   *   Product description.
   *
   * @throws Drupal\redsys_button\lib\redsys\Exception\RedSysException
   */
  public function setProductDescription(string $description = '') {
    $this->parameters['DS_MERCHANT_PRODUCTDESCRIPTION'] = trim($description);
  }

  /**
   * Sets the name of the store's owner.
   *
   * @param string $titular
   *   The owner's name (e.g., Alex J. Luna).
   *
   * @throws Drupal\redsys_button\lib\redsys\Exception\RedSysException
   */
  public function setTitular(string $titular = '') {
    if (Validators::isEmpty($titular)) {
      throw new RedSysException('Add name for the user');
    }
    else {
      $this->parameters['DS_MERCHANT_TITULAR'] = trim($titular);
    }
  }

  /**
   * Sets the name of the store.
   *
   * @param string $tradename
   *   The store name.
   *
   * @throws Drupal\redsys_button\lib\redsys\Exception\RedSysException
   */
  public function setTradeName(string $tradename = '') {
    if (Validators::isEmpty($tradename)) {
      throw new RedSysException('Add name for the trade name');
    }
    else {
      $this->parameters['DS_MERCHANT_MERCHANTNAME'] = trim($tradename);
    }
  }

  /**
   * Sets the payment method.
   *
   * @param string $method
   *   The payment method to use. Values:
   *     - T = Payment with Card + iupay
   *     - R = Payment by Transfer
   *     - D = Direct Debit
   *     - C = Only Card (will display only the form for card data)
   *   The default is C.
   *
   * @throws Drupal\redsys_button\lib\redsys\Exception\RedSysException
   */
  public function setMethod(string $method) {
    if (Validators::isEmpty($method)) {
      throw new RedSysException('Add payment method');
    }
    else {
      $this->parameters['DS_MERCHANT_PAYMETHODS'] = trim($method);
    }
  }

  /**
   * Sets the customer's card number.
   *
   * @param string $pan
   *   Card number. Its length depends on the type of card.
   *
   * @throws Drupal\redsys_button\lib\redsys\Exception\RedSysException
   */
  public function setPan(string $pan = '') {
    if (intval($pan) == 0) {
      throw new RedSysException('Pan not valid');
    }
    else {
      $this->parameters['DS_MERCHANT_PAN'] = $pan;
    }
  }

  /**
   * Sets the credit card's expiration date.
   *
   * @param string $expirydate
   *   The card's expiration date.
   *   Its format is YYMM, where YY are the last two digits of the year
   *   and MM are the two digits of the month.
   *
   * @throws Drupal\redsys_button\lib\redsys\Exception\RedSysException
   */
  public function setExpiryDate(string $expirydate = '') {
    if (!Validators::isExpiryDate($expirydate)) {
      throw new RedSysException('Expire date is not valid');
    }
    else {
      $this->parameters['DS_MERCHANT_EXPIRYDATE'] = $expirydate;
    }
  }

  /**
   * Sets the CVV2 code of the card.
   *
   * @param int $cvv2
   *   The card's CVV2 code.
   *
   * @throws Drupal\redsys_button\lib\redsys\Exception\RedSysException
   */
  public function setCvv(int $cvv2 = 0) {
    if (intval($cvv2) == 0) {
      throw new RedSysException('CVV2 is not valid');
    }
    else {
      $this->parameters['DS_MERCHANT_CVV2'] = $cvv2;
    }
  }

  /**
   * Returns an array with all the assigned parameters.
   *
   * @return array
   *   The parameters assigned so far.
   */
  public function getParameters() {
    return $this->parameters;
  }

  /**
   * Returns a string with all the parameters in XML format.
   *
   * @return string
   *   A string with the assigned parameters so far.
   */
  public function getParametersXml() {
    $xml = '<DATOSENTRADA>';
    foreach ($this->parameters as $key => $value) {
      $xml .= '<' . $key . '>' . $value . '</' . $key . '>';
    }
    $xml .= '</DATOSENTRADA>';
    return $xml;
  }

  /**
   * Assigns a name to the data submission form.
   *
   * @param string $name
   *   The form's name.
   */
  public function setNameForm(string $name) {
    $this->nameForm = $name;
  }

  /**
   * Retrieves the name assigned to the data submission form.
   *
   * @return string
   *   The form's name.
   */
  public function getNameForm() {
    return $this->nameForm;
  }

  /**
   * Sets the ID for the data submission form.
   *
   * @param string $id
   *   The form's ID value.
   */
  public function setIdForm(string $id) {
    $this->idForm = $id;
  }

  /**
   * Assigns various values to the form's submit button.
   *
   * @param string $name
   *   The button's name (MANDATORY).
   * @param string $id
   *   The button's identifier (MANDATORY).
   * @param string $value
   *   The button's display text (MANDATORY).
   * @param string $style
   *   Inline CSS.
   * @param string $cssClass
   *   Class for the button.
   *
   * @throws Drupal\redsys_button\lib\redsys\Exception\RedSysException
   */
  public function setAttributesSubmit(string $name, string $id, string $value, string $style, string $cssClass) {
    // The first 3 parameters are mandatory.
    if (Validators::isEmpty($name) || Validators::isEmpty($id) || Validators::isEmpty($value)) {
      throw new RedSysException('Parameters name, id, and value are required');
    }
    else {
      $this->nameSubmit = $name;
      $this->idSubmit = $id;
      $this->valueSubmit = $value;
      $this->styleSubmit = $style;
      $this->classSubmit = $cssClass;
    }
  }

  /**
   * Generates the HTML of the data submission form and submits it if necessary.
   *
   * @param bool $auto_submit
   *   If TRUE, the form is submitted automatically.
   *   Default is FALSE.
   *
   * @return string
   *   HTML of the form.
   */
  public function createForm(bool $auto_submit = FALSE) {
    $form = '
      <form action="' . $this->environment . '" method="post" id="' . $this->idForm . '" name="' . $this->nameForm . '">
        <input type="hidden" name="Ds_MerchantParameters" value="' . $this->generateMerchantParameters() . '"/>
        <input type="hidden" name="Ds_Signature" value="' . $this->signature . '"/>
        <input type="hidden" name="Ds_SignatureVersion" value="' . $this->version . '"/>
        <input type="submit" name="' . $this->nameSubmit . '" id="' . $this->idSubmit . '" value="' . $this->valueSubmit . '" ' . ($this->styleSubmit != '' ? ' style="' . $this->styleSubmit . '"' : '') . ' ' . ($this->classSubmit != '' ? ' class="' . $this->classSubmit . '"' : '') . '>
      </form>
    ';

    if ($auto_submit) {
      $form .= '<script>document.forms["' . $this->nameForm . '"].submit();</script>';
    }

    return $form;
  }

  /**
   * Executes the payment through SOAP.
   *
   * @param string $key
   *   The secret key for the payment.
   *
   * @return array
   *   Array with the response.
   */
  public function firePayment(string $key) {
    $xml = $this->buildXml($key);
    $client = new \SoapClient($this->getEnvironmentXml());
    $result = $client->trataPeticion([
      'datoEntrada' => $xml,
    ]);
    $response = Utils::xmlToArray($result->trataPeticionReturn);
    return $this->checkResponse($response, $key);
  }

  /**
   * Builds the XML string to be sent.
   *
   * @param string $key
   *   The secret key for building the XML.
   *
   * @return string
   *   Formatted XML string.
   */
  private function buildXml(string $key) {
    $datos = $this->getParametersXml();

    $xml = '<REQUEST>';
    $xml .= $datos;
    $xml .= '<DS_SIGNATUREVERSION>' . $this->getVersion() . '</DS_SIGNATUREVERSION>';
    $xml .= '<DS_SIGNATURE>' . $this->generateSignature($datos, $this->getOrder(), $key) . '</DS_SIGNATURE>';
    $xml .= '</REQUEST>';

    return $xml;
  }

  /**
   * Method getMerchantSecretKey.
   */
  public function getMerchantSecretKey() {
    $config = $this->configFactory->get('redsys_button.settings');
    return $config->get('merchant_key');
  }

  /**
   * Verifies the signature returned by Redsys.
   *
   * @param array $postData
   *   Data received from the bank.
   * @param string $key
   *   The merchant's public key.
   *
   * @return bool
   *   TRUE if the data matches.
   *
   * @throws Drupal\redsys_button\lib\redsys\Exception\RedSysException
   */
  private function checkResponseSignature(array $postData, string $key) {
    if (!isset($postData)) {
      throw new RedSysException("Add data return of bank");
    }

    $cadena_con_tarjeta = $postData['Ds_Amount'] .
    $postData['Ds_Order'] .
    $postData['Ds_MerchantCode'] .
    $postData['Ds_Currency'] .
    $postData['Ds_Response'] .
    $postData['Ds_CardNumber'] .
    $postData['Ds_TransactionType'] .
    $postData['Ds_SecurePayment'];

    $cadena_sin_tarjeta = $postData['Ds_Amount'] .
    $postData['Ds_Order'] .
    $postData['Ds_MerchantCode'] .
    $postData['Ds_Currency'] .
    $postData['Ds_Response'] .
    $postData['Ds_TransactionType'] .
    $postData['Ds_SecurePayment'];

    if ($this->generateSignature($cadena_con_tarjeta, $postData['Ds_Order'], $key) != $postData['Ds_Signature']) {
      if ($this->generateSignature($cadena_sin_tarjeta, $postData['Ds_Order'], $key) == $postData['Ds_Signature']) {
        return TRUE;
      }
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Decodes and returns the parameters returned by the bank.
   *
   * @param array $postData
   *   Array with the bank's return data.
   * @param string $key
   *   The merchant's public key.
   *
   * @return array
   *   Array with the bank's response.
   *
   * @throws Drupal\redsys_button\lib\redsys\Exception\RedSysException
   */
  public function checkPaymentResponse(array $postData, string $key) {
    if (isset($postData)) {
      $parameters = $postData["Ds_MerchantParameters"];
      $signatureReceived = $postData["Ds_Signature"];
      $decodec = json_decode(Utils::decodeParameters($parameters), TRUE);
      $order = $decodec['Ds_Order'];

      $signature = $this->generateSignature($parameters, $order, $key);
      $signature = strtr($signature, '+/', '-_');
      if ($signature === $signatureReceived) {
        return $this->getResponse(0, $decodec);
      }
      else {
        return $this->getResponse('SIS041', $decodec, TRUE);
      }
    }
    else {
      throw new RedSysException("Error: Redsys response is empty");
    }
  }

  /**
   * Returns an array with the response of the XML request.
   *
   * @param array $response
   *   Array with the bank's response.
   * @param string $key
   *   The merchant's public key.
   *
   * @return array
   *   Response from the bank's request.
   */
  private function checkResponse(array $response, string $key) {

    if (!Validators::validCode($response)) {
      return $this->getResponse($this->getErrorCode($response), $this->getErrorCodeData($response), TRUE);
    }

    if (!$this->checkResponseSignature($response['OPERACION'], $key)) {
      return $this->getResponse('SIS0041', $response['OPERACION'], TRUE);
    }

    return $this->getResponse($response['CODIGO'], $response['OPERACION']);
  }

  /**
   * Formats the bank's response with additional parameters.
   *
   * @param string $code
   *   Error code provided by the bank.
   * @param array $response
   *   Array with the bank's response.
   * @param bool $error
   *   Indicates if the data issued by the bank contains an already.
   *
   * @return array
   *   Array with the response and additional data.
   */
  private function getResponse(string $code, array $response, bool $error = FALSE) {
    $response_default = [
      'error' => $error,
      'code'  => $code,
      'error_info' => RedSysMessages::getByCode($code),
    ];

    if (!$response) {
      if (!$response_default['code']) {
        $response_default['code'] = '9998';
      }
      return $response_default;
    }

    return array_merge($response_default, $response);
  }

  /**
   * Retrieves the error code from the received response.
   *
   * @param array $response
   *   Response received from the bank.
   *
   * @return string
   *   The error code.
   */
  private function getErrorCode(array $response) {
    $code = $response['CODIGO'];

    if (!is_numeric($code)) {
      return $code;
    }

    return $response['OPERACION']['Ds_Response'];
  }

  /**
   * Retrieves all data related to the error from the received response.
   *
   * @param array $response
   *   Response received from the bank.
   *
   * @return array
   *   Operation data.
   */
  private function getErrorCodeData(array $response) {
    $code = $response['CODIGO'];

    if (!is_numeric($code)) {
      return $response['RECIBIDO']['REQUEST']['DATOSENTRADA'];
    }

    return $response['OPERACION'];
  }

  /**
   * Encodes parameters in Base64.
   *
   * @return string
   *   Encoded string.
   */
  public function generateMerchantParameters() {
    // Convert the Array to Json.
    $json = Utils::arrayToJson($this->parameters);

    // Encode the Json in Base64.
    return Utils::encodeBase64($json);
  }

  /**
   * Generates the request signature.
   *
   * @param string $key
   *   The merchant's secret key.
   */
  public function generateMerchantSignature(string $key) {
    $key = Utils::decodeBase64($key);
    $merchant_parameter = $this->generateMerchantParameters();
    $key = Utils::encrypt3des($this->getOrder(), $key);
    $result = Utils::hmac256($merchant_parameter, $key);

    $this->signature = Utils::encodeBase64($result);
  }

  /**
   * Generates a signature from the received data from the gateway.
   *
   * @param string $datos
   *   Encoded data from the gateway.
   * @param string $order
   *   Order number returned by the gateway.
   * @param string $key
   *   The merchant's secret key.
   *
   * @return string
   *   Signature of the received parameters.
   */
  private function generateSignature(string $datos, string $order, string $key) {
    $key = Utils::decodeBase64($key);
    $key = Utils::encrypt3des($order, $key);
    $result = Utils::hmac256($datos, $key);
    return Utils::encodeBase64($result);
  }

}
