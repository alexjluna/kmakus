<?php

namespace Drupal\redsys_button\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\redsys_button\lib\redsys\RedSys;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a form for Redsys payments.
 */
class RedsysButtonForm extends FormBase {

  /**
   * The RedSys service.
   *
   * @var \Drupal\redsys_button\lib\redsys\RedSys
   */
  protected $redsys;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The configuration factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new RedsysButtonForm instance.
   *
   * @param \Drupal\redsys_button\lib\redsys\RedSys $redsys
   *   The RedSys service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Mail\MailManagerInterface $mailManager
   *   The mail manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(RedSys $redsys,
    MessengerInterface $messenger,
    MailManagerInterface $mailManager,
    ConfigFactoryInterface $configFactory,
    AccountInterface $current_user
  ) {
    $this->redsys = $redsys;
    $this->messenger = $messenger;
    $this->mailManager = $mailManager;
    $this->configFactory = $configFactory;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('redsys_button.redsys'),
      $container->get('messenger'),
      $container->get('plugin.manager.mail'),
      $container->get('config.factory'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'redsys_button_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $install_state = NULL) {
    $form['amount'] = [
      '#type' => 'number',
      '#placeholder' => $this->t('Enter the amount'),
      '#title' => $this->t('Enter the amount'),
      '#title_display' => 'invisible',
      '#step' => '0.0001',
      '#required' => TRUE,
    ];
    $form['email'] = [
      '#type' => 'email',
      '#placeholder' => $this->t('Your e-mail'),
      '#title' => $this->t('Your e-mail'),
      '#required' => TRUE,
      '#title_display' => 'invisible',
      '#attributes' => ['id' => 'email-input'],
    ];
    $form['description'] = [
      '#type' => 'textarea',
      '#placeholder' => $this->t('Write the payment concept, invoice number, etc.'),
      '#title' => $this->t('Write the payment concept, invoice number, etc.'),
      '#required' => TRUE,
      '#title_display' => 'invisible',
      '#attributes' => ['id' => 'description-input'],
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Pay'),
      '#button_type' => 'primary',
    ];
    $form['#attached']['library'][] = 'redsys_button/redsys_button_behavior';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $amountValue = $form_state->getValue('amount');
    $amount = round($amountValue, 2);
    $email = $form_state->getValue('email');
    $description = $form_state->getValue('description');
    $randomNumber = str_pad(mt_rand(0, 999999999999), 12, '0', STR_PAD_LEFT);
    $order = $randomNumber;
    $this->redsys->setAmount($amount);
    $this->redsys->setOrder($order);
    $this->redsys->setProductDescription($description);
    $this->redsys->generateMerchantSignature($this->redsys->getMerchantSecretKey());
    $redsysFormHtml = $this->redsys->createForm(TRUE);
    $response = new Response($redsysFormHtml);
    $form_state->setResponse($response);
    $this->sendNotificationEmails($email, $description, $amount);
  }

  /**
   * Sends the notification emails.
   */
  protected function sendNotificationEmails($customerEmail, $description, $amount) {
    $notificationEmail =
    $this->configFactory->get('redsys_button.settings')->get('notification_email');
    $langcode = $this->currentUser()->getPreferredLangcode();
    $params = [
      'description' => $description,
      'email' => $customerEmail,
      'amount' => $amount,
    ];

    // Send to customer.
    $this->mailManager->mail('redsys_button', 'payment_notification', $customerEmail, $langcode, $params);

    // Send to admin/notification email.
    if (!empty($notificationEmail)) {
      $this->mailManager->mail('redsys_button', 'payment_notification', $notificationEmail, $langcode, $params);
    }
  }

}
