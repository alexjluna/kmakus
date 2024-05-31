<?php

namespace Drupal\youtube_plus\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class InstagramForm.
 */
class SettingsForm extends FormBase {

  /**
   * Configuration object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new InstagramForm object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MessengerInterface $messenger) {
    $this->config = $config_factory->getEditable('youtube_plus.settings');
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ytp_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#description' => $this->t('The API Key from the Google app on @url.', ["@url" => "https://console.developers.google.com/apis/credentials"]),
      '#required' => TRUE,
      '#default_value' => $this->config ? $this->config->get('api_key') : '',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // API key can't have spaces. That's as much as I know for now.
    if (strpos($form_state->getValue('api_key'), ' ') !== FALSE) {
      $form_state->setErrorByName('api_key', $this->t('API key must not contain spaces.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $api_key = $form_state->getValue('api_key');

    if ($this->config->set('api_key', $api_key)->save()) {
      $this->messenger->addMessage($this->t('API key saved successfully.'), 'status');
    }
    $form_state->setRedirectUrl(Url::fromRoute('entity.youtube_plus_channel.list'));
  }

}
