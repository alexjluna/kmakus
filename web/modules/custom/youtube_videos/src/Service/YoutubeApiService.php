<?php

/**
 * @file
 * Contains \Drupal\youtube_videos\Service\YoutubeApiService.
 */

namespace Drupal\youtube_videos\Service;

use GuzzleHttp\Client;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class YoutubeApiService.
 *
 * Service for fetching videos from YouTube.
 */
class YoutubeApiService {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The configuration object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructs a YoutubeApiService object.
   *
   * @param \GuzzleHttp\Client $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(Client $http_client, ConfigFactoryInterface $config_factory) {
    $this->httpClient = $http_client;
    $this->config = $config_factory->get('youtube_videos.settings');
  }

  /**
   * Fetches videos from a specific YouTube channel.
   *
   * @param string $channel_id
   *   The YouTube channel ID.
   * @param int $max_results
   *   The maximum number of videos to fetch.
   *
   * @return array
   *   An array of video items.
   */
  public function fetchVideos($channel_id, $max_results = 50) {
    $response = $this->httpClient->get('https://www.googleapis.com/youtube/v3/search', [
      'query' => [
        'key' => $this->config->get('api_key'),
        'channelId' => $channel_id,
        'part' => 'snippet',
        'order' => 'date',
        'maxResults' => $max_results,
        'type' => 'video',
      ],
    ]);

    $data = json_decode($response->getBody(), true);
    return $data['items'];
  }

  /**
   * Fetches random videos from multiple YouTube channels.
   *
   * @param string $channel_ids
   *   A comma-separated list of YouTube channel IDs.
   * @param int $total_videos
   *   The total number of videos to fetch.
   *
   * @return array
   *   An array of video items.
   */
  public function fetchRandomVideosFromMultipleChannels($channel_ids, $total_videos) {
    $videos = [];
    foreach (explode(',', $channel_ids) as $channel_id) {
      $channel_id = trim($channel_id);
      $videos = array_merge($videos, $this->fetchVideos($channel_id, $total_videos));
    }

    shuffle($videos);
    return array_slice($videos, 0, $total_videos);
  }
}
