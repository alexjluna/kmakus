<?php

namespace Drupal\youtube_plus\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\youtube_plus\YouTubePlusUtils;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Configuration of Channel entity.
 */
class ChannelForm extends EntityForm {

  /**
   * Google YouTube Service.
   *
   * @var \Google_Service_YouTube
   */
  protected $youtube;

  /**
   * Configuration settings.
   *
   * @var any
   */
  protected $config;

  /**
   * Drupal\Core\Plugin\DefaultPluginManager definition.
   *
   * @var Drupal\Core\Entity\Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityQuery;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Class Constructor.
   */
  public function __construct(EntityTypeManagerInterface $entityQuery, ConfigFactoryInterface $config_factory, MessengerInterface $messenger) {
    $this->entityQuery = $entityQuery;
    $this->youtube = new YouTubePlusUtils();
    $this->config = $config_factory->getEditable('youtube_plus.settings');
    $this->messenger = $messenger;
  }

  /**
   * Class Create.
   *
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form = parent::form($form, $form_state);
    if (empty($this->config->get('api_key'))) {
      $this->messenger->addMessage($this->t('You first need to define a API key <a href="@link">here</a>.', ["@link" => Url::fromRoute('youtube_plus.settings')->toSTring()]), 'warning');
    }

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Channel Name'),
      '#required' => TRUE,
      '#default_value' => $this->entity->getName(),
    ];

    if ($this->entity->isNew()) {
      $form['type'] = [
        '#type' => 'radios',
        '#title' => $this->t('Configure by'),
        '#required' => TRUE,
        '#default_value' => 0,
        '#options' => [
          0 => $this->t('Id'),
          1 => $this->t('Url'),
        ],
      ];
    }
    $form['channel_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Channel Id'),
      '#required' => FALSE,
      '#default_value' => $this->entity->getId(),
      '#description' => $this->t('Example: <i>https://www.youtube.com/channel/<b>UCAJALvsCWz8Kh6wOySHUJAA</b></i>'),
    ];

    if ($this->entity->isNew()) {
      $form['channel_id']['#states'] = [
        'visible' => [
          ':input[name="type"]' => ['value' => 0],
        ],
      ];

      $form['channel_url'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Channel Url'),
        '#required' => FALSE,
        '#description' => $this->t('Example: <i>https://www.youtube.com/user/<b>DrupalAssociation</b></i>'),
        '#states' => [
          'visible' => [
            ':input[name="type"]' => ['value' => 1],
          ],
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $channel_id = $form_state->getValue('channel_id');
    $custom_url = $form_state->getValue('channel_url');

    // No API key.
    if (empty($this->config->get('api_key'))) {
      $form_state->setErrorByName('info', $this->t('You must set API Key before adding a channel.'));
    }

    if (strpos($channel_id, ' ') !== FALSE || strpos($custom_url, ' ') !== FALSE) {
      $form_state->setErrorByName('info', $this->t('Field must not contain spaces.'));
    }

    if (strpos($channel_id, 'http') !== FALSE || strpos($custom_url, 'http') !== FALSE) {
      $form_state->setErrorByName('info', $this->t('Field must not contain "https://www.youtube.com/user" or "https://www.youtube.com/channel".'));
    }

    // @todo validate if key already exist
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $channel_id = $form_state->getValue('channel_id');
    $custom_url = $form_state->getValue('channel_url');

    if (empty($channel_id)) {
      $channel_info = $this->youtube->getChannelByCustomURL($custom_url);
      $channel_id = $channel_info->getItems()[0]->getID();
    }
    $this->entity->set('id', $channel_id);
    $status = $this->entity->save();
    $this->entity->statusMessage($status);

    $form_state->setRedirectUrl(Url::fromRoute('entity.youtube_plus_channel.list'));

  }

}
