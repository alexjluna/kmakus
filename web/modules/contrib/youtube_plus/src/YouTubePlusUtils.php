<?php

namespace Drupal\youtube_plus;

/**
 * A YoutubePlus utility class.
 */
class YouTubePlusUtils {

  /**
   * Google API client.
   *
   * @var \Google_Client
   */
  protected $client;

  /**
   * Google YouTube Service.
   *
   * @var \Google_Service_YouTube
   */
  protected $youtube;

  /**
   * Drupal Config data.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Youtube Plus Commands constructor.
   */
  public function __construct() {
    $this->config = \Drupal::service('config.factory')->getEditable('youtube_plus.settings');
    $this->client = new \Google_Client();
    $this->client->setDeveloperKey($this->config->get('api_key'));

    // Define service object for making API requests.
    $this->youtube = new \Google_Service_YouTube($this->client);

  }

  /**
   * Get channel info.
   */
  public function getChannelInfo($channel_id = NULL) {
    $queryParams = [
      'id' => $channel_id,
    ];

    try {
      return $this->youtube->channels->listChannels('snippet,contentDetails,statistics', $queryParams);
    }
    catch (\Exception $e) {
      return $e;
    }
  }

  /**
   * Get channel by url.
   */
  public function getChannelByCustomUrl($custom_urls = NULL) {
    $queryParams = [
      'forUsername' => $custom_urls,
    ];

    try {
      return $this->youtube->channels->listChannels('snippet,contentDetails,statistics', $queryParams);
    }
    catch (\Exception $e) {
      return $e;
    }
  }

  /**
   * Get channels playlists.
   */
  public function getChannelPlaylists($channel_id = NULL) {

    if (empty($channel_id)) {
      return FALSE;
    }

    $playlists = [];
    $totalResults = 0;
    $pageToken = "";

    do {
      $queryParams = [
        'channelId' => $channel_id,
        'maxResults' => 50,
        'pageToken' => $pageToken,
      ];

      try {
        $youtube_playlists = $this->youtube->playlists->listPlaylists('snippet,contentDetails', $queryParams);
      }
      catch (\Exception $e) {
        return $e;
      }

      $totalResults = $youtube_playlists->pageInfo->totalResults;
      $pageToken = $youtube_playlists->nextPageToken;

      foreach ($youtube_playlists->items as $playlist) {
        $playlists[] = $playlist;
      }

    } while ($totalResults > count($playlists));

    return $playlists;
  }

  /**
   * Get Playlist Items.
   */
  public function getPlaylistItems($playlist_id = NULL) {

    if (empty($playlist_id)) {
      return FALSE;
    }

    $playlist_items = [];
    $totalResults = 0;
    $pageToken = "";

    do {
      $queryParams = [
        'maxResults' => 50,
        'playlistId' => $playlist_id,
        'pageToken' => $pageToken,
      ];

      try {
        $youtube_playlist_items = $this->youtube->playlistItems->listPlaylistItems('snippet', $queryParams);
      }
      catch (\Exception $e) {
        return $e;
      }

      $totalResults = $youtube_playlist_items->pageInfo->totalResults;
      $pageToken = $youtube_playlist_items->nextPageToken;

      foreach ($youtube_playlist_items->items as $playlist_item) {
        $playlist_items[] = $playlist_item;
      }

    } while ($totalResults > count($playlist_items));

    return $playlist_items;
  }

}
