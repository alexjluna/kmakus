<?php

namespace Drupal\youtube_videos\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\youtube_videos\Service\YoutubeApiService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'YouTube Videos' Block.
 *
 * @Block(
 *   id = "youtube_videos_block",
 *   admin_label = @Translation("YouTube Videos Block"),
 * )
 */
class YoutubeVideosBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The YouTube API service.
   *
   * @var \Drupal\youtube_videos\Service\YoutubeApiService
   */
  protected $youtubeApiService;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new YoutubeVideosBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\youtube_videos\Service\YoutubeApiService $youtube_api_service
   *   The YouTube API service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, YoutubeApiService $youtube_api_service, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->youtubeApiService = $youtube_api_service;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('youtube_videos.youtube_api'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->configFactory->get('youtube_videos.settings');
    $channel_ids = $config->get('channel_ids');
    $max_results = $config->get('max_results');
    $videos = $this->youtubeApiService->fetchRandomVideosFromMultipleChannels($channel_ids, $max_results);

    return [
      '#theme' => 'youtube_videos_grid',
      '#videos' => $videos,
      '#attached' => [
        'library' => [
          'youtube_videos/youtube_videos_grid',
        ],
      ],
      // Configuración para desactivar la caché.
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }
}
