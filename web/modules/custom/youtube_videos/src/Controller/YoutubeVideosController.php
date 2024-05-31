<?php

/**
 * @file
 * Contains \Drupal\youtube_videos\Controller\YoutubeVideosController.
 */

namespace Drupal\youtube_videos\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\youtube_videos\Service\YoutubeApiService;

/**
 * Class YoutubeVideosController.
 *
 * Controller to fetch and display YouTube videos.
 */
class YoutubeVideosController extends ControllerBase {

  /**
   * The YouTube API service.
   *
   * @var \Drupal\youtube_videos\Service\YoutubeApiService
   */
  protected $youtubeApiService;

  /**
   * Constructs a YoutubeVideosController object.
   *
   * @param \Drupal\youtube_videos\Service\YoutubeApiService $youtube_api_service
   *   The YouTube API service.
   */
  public function __construct(YoutubeApiService $youtube_api_service) {
    $this->youtubeApiService = $youtube_api_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('youtube_videos.youtube_api')
    );
  }

  /**
   * Returns a render array for a page displaying YouTube videos.
   *
   * @return array
   *   A render array for the YouTube videos page.
   */
  public function content() {
    $config = $this->config('youtube_videos.settings');
    $channel_ids = $config->get('channel_ids');
    $max_results = $config->get('max_results');
    $videos = $this->youtubeApiService->fetchRandomVideosFromMultipleChannels($channel_ids, $max_results);

    $build = [
      '#theme' => 'youtube_videos_grid',
      '#videos' => $videos,
      '#attached' => [
        'library' => [
          'youtube_videos/youtube_videos_grid',
        ],
      ],
      '#cache' => [
        'max-age' => 0,
      ],
    ];

    return $build;
  }
}
