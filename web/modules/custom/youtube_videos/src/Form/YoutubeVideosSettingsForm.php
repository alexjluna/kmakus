<?php

/**
 * @file
 * Contains \Drupal\youtube_videos\Form\YoutubeVideosSettingsForm.
 */

namespace Drupal\youtube_videos\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class YoutubeVideosSettingsForm.
 *
 * Configure settings for the YouTube Videos module.
 */
class YoutubeVideosSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'youtube_videos_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['youtube_videos.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('youtube_videos.settings');

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('YouTube API Key'),
      '#default_value' => $config->get('api_key'),
      '#required' => TRUE,
    ];

    $form['channel_ids'] = [
      '#type' => 'textarea',
      '#title' => $this->t('YouTube Channel IDs'),
      '#default_value' => $config->get('channel_ids'),
      '#description' => $this->t('Enter multiple YouTube Channel IDs separated by commas.'),
      '#required' => TRUE,
    ];

    $form['max_results'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of Videos to Fetch'),
      '#default_value' => $config->get('max_results') ?: 6,
      '#min' => 1,
      '#max' => 50,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('youtube_videos.settings')
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('channel_ids', $form_state->getValue('channel_ids'))
      ->set('max_results', $form_state->getValue('max_results'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
