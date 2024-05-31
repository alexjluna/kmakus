<?php

namespace Drupal\youtube_plus;

use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * A YoutubePlus service class.
 */
class YouTubePlusService {

  const CHANNEL_ID = "field_ytp_channel_id";
  // Channel title.
  const CHANNEL_NAME = "name";
  // Channel description.
  const CHANNEL_DESCRIPTION = "description";
  // Channel custom URL.
  const CHANNEL_CUSTOM_URL = "field_ytp_custom_url";
  // Channel URL.
  const CHANNEL_URL = "field_ytp_url";
  // Thumbnail default size.
  const CHANNEL_THUMBNAIL_DEFAULT = "field_ytp_thumbnail_default";
  // Thumbnail medium size.
  const CHANNEL_THUMBNAIL_MEDIUM = "field_ytp_thumbnail_medium";
  // Thumbnail high size.
  const CHANNEL_THUMBNAIL_HIGH = "field_ytp_thumbnail_high";
  // PlayList uploads (default created)
  const CHANNEL_PLAYLIST_ID = "field_ytp_playlist_id";
  // Upload videos count.
  const CHANNEL_ITEM_COUNT = "field_ytp_item_count";

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
   * Logger object.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * Youtube Plus Commands constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   Logger factory object.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration object factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    LoggerChannelFactoryInterface $logger,
    ConfigFactoryInterface $configFactory,
    EntityTypeManagerInterface $entity_type_manager
    ) {
    $this->entityManager = $entity_type_manager;
    $this->config = $configFactory->getEditable('youtube_plus.settings');
    $this->logger = $logger;
  }

  /**
   * Imports all existing channels.
   */
  public function importAll() {
    // Channels.
    $entity_channels = $this->entityManager->getStorage('youtube_plus_channel')->loadMultiple();

    foreach ($entity_channels as $channel) {
      $this->importChannel($channel);
    }
    \Drupal::messenger()->addMessage("YouTube Plus importation run successfuly.", 'status');
  }

  /**
   * Import a channel.
   */
  public function importChannel($channel) {
    $youtube_utils = new YouTubePlusUtils();
    $termStorage = $this->entityManager->getStorage('taxonomy_term');

    $channelID = $channel->getId();
    $channel_info = $youtube_utils->getChannelInfo($channelID);
    if ($channel_info instanceof \Exception) {
      \Drupal::messenger()->addMessage("There was an error importing from Youtube. Did you enable the Youtube Data API?", 'error');
      return;
    }
    if (!$youtube_utils->getChannelInfo($channelID)->getItems()) {
      \Drupal::messenger()->addMessage('No videos found in channel ' . $channelID, 'error');
      return;
    }
    $youtube_channel = $youtube_utils->getChannelInfo($channelID)->getItems()[0];
    $channel_snippets = $youtube_channel->getSnippet();

    $channel_data = [
      'vid' => "ytp_channels",
      // ID.
      self::CHANNEL_ID => $youtube_channel->getId(),
      // Channel title.
      self::CHANNEL_NAME => $channel_snippets->getTitle(),
      // Channel description.
      self::CHANNEL_DESCRIPTION => $channel_snippets->getDescription(),
      // Channel custom URL.
      self::CHANNEL_CUSTOM_URL => $channel_snippets->getCustomUrl(),
      // Channel URL.
      self::CHANNEL_URL => (!empty($channel_snippets->getCustomUrl()) ?
        "https://www.youtube.com/user/" . $channel_snippets->getCustomUrl() :
        "https://www.youtube.com/channel/" . $youtube_channel->getId()),
      // Thumbnail default size.
      self::CHANNEL_THUMBNAIL_DEFAULT => $channel_snippets->getThumbnails()->getDefault()->getURL(),
      // Thumbnail medium size.
      self::CHANNEL_THUMBNAIL_MEDIUM => $channel_snippets->getThumbnails()->getMedium()->getURL(),
      // Thumbnail high size.
      self::CHANNEL_THUMBNAIL_HIGH => $channel_snippets->getThumbnails()->getHigh()->getURL(),
      // PlayList uploads (default created)
      self::CHANNEL_PLAYLIST_ID => $youtube_channel->getContentDetails()->getRelatedPlaylists()->getUploads(),
      // Upload videos count.
      self::CHANNEL_ITEM_COUNT => $youtube_channel->getStatistics()->getVideoCount(),
    ];

    $channel_term_id = $this->entityGet($termStorage, 'ytp_channels', array_slice($channel_data, 0, 2), self::CHANNEL_ID);
    // Check if channel already exists.
    if ($channel_term_id) {
      $this->entityUpdate($termStorage, $channel_term_id, $channel_data);
    }
    else {
      // Create term.
      $channel_term_id = $this->entityCreate($termStorage, "ytp_channels", $channel_data);
    }

    // Uploads Playlist item.
    // @todo CRIAR UM noo método para os vídeos + 1 para as playlists.
    $youtube_uploads_playlist_items = $youtube_utils->getPlaylistItems($channel_data["field_ytp_playlist_id"]);

    foreach ($youtube_uploads_playlist_items as $playlist_item) {

      $publishedat_date = strtotime($playlist_item["snippet"]["publishedAt"]);

      $playlist_item_data = [
      // Video ID.
        "field_ytp_video_id" => $playlist_item["snippet"]["resourceId"]["videoId"],
      // Video title.
        "title" => $playlist_item["snippet"]["title"],
      // Video description.
        "field_ytp_description" => $playlist_item["snippet"]["description"] ?? '',
      // Channel taxonomy term.
        "field_ytp_channel" => $channel_term_id,
      // Playlist URL.
        "field_ytp_url" => "https://www.youtube.com/watch?v=" . $playlist_item["snippet"]["resourceId"]["videoId"],
      // Publishing date.
        "field_ytp_published" => $publishedat_date,
      // Thumbnail default size.
        "field_ytp_thumbnail_default" => $playlist_item["snippet"]["thumbnails"]["default"]["url"] ?? '',
      // Thumbnail medium size.
        "field_ytp_thumbnail_medium" => $playlist_item["snippet"]["thumbnails"]["medium"]["url"] ?? '',
      // Thumbnail high size.
        "field_ytp_thumbnail_high" => $playlist_item["snippet"]["thumbnails"]["high"]["url"] ?? '',
      ];

      // Thumbnail standard size.
      if (isset($playlist_item["snippet"]["thumbnails"]["standard"]["url"])) {
        $playlist_item_data["field_ytp_thumbnail_standard"] = $playlist_item["snippet"]["thumbnails"]["standard"]["url"];
      }
      // Thumbnail maxres size.
      if (isset($playlist_item["snippet"]["thumbnails"]["maxres"]["url"])) {
        $playlist_item_data["field_ytp_thumbnail_maxres"] = $playlist_item["snippet"]["thumbnails"]["maxres"]["url"];
      }

      // Check if playlist item already exists.
      if ($playlist_item_node_id = $this->nodeGet(["field_ytp_video_id" => $playlist_item_data["field_ytp_video_id"]])) {
        // Update item.
        unset($playlist_item_data["field_ytp_published"]);
        $this->nodeUpdate($playlist_item_node_id, $playlist_item_data);
      }
      else {
        // Create item.
        $playlist_item_node_id = $this->nodeCreate($playlist_item_data);
      }

    }

    // Playlists.
    $youtube_playlists = $youtube_utils->getChannelPlaylists($channel_data["field_ytp_channel_id"]);

    foreach ($youtube_playlists as $playlist) {

      $playlist_data = [
        "vid" => "ytp_playlists",
      // Playlist ID.
        "field_ytp_playlist_id" => $playlist["id"],
      // Playlist title.
        "name" => $playlist["snippet"]["title"],
      // Playlist description.
        "description" => $playlist["snippet"]["description"],
      // Channel taxonomy term.
        "field_ytp_channel" => $channel_term_id,
      // Playlist URL.
        "field_ytp_url" => "https://www.youtube.com/playlist?list=" . $playlist["id"],
      // Upload videos count.
        "field_ytp_item_count" => $playlist["contentDetails"]["itemCount"],
      // Thumbnail default size.
        "field_ytp_thumbnail_default" => $playlist["snippet"]["thumbnails"]["default"]["url"] ?? '',
      // Thumbnail medium size.
        "field_ytp_thumbnail_medium" => $playlist["snippet"]["thumbnails"]["medium"]["url"] ?? '',
      // Thumbnail high size.
        "field_ytp_thumbnail_high" => $playlist["snippet"]["thumbnails"]["high"]["url"] ?? '',
      ];
      $termStorage = $this->entityManager->getStorage('taxonomy_term');
      // Check if playlist already exists.
      if ($playlist_term_id = $this->entityGet(
        $termStorage,
        'ytp_playlists',
        array_slice($playlist_data, 0, 2),
        "field_ytp_playlist_id"
      )) {
        // Update term.
        $this->entityUpdate($termStorage, $playlist_term_id, $playlist_data);
      }
      else {
        // Create term.
        $playlist_term_id = $this->entityCreate($termStorage, "ytp_playlists", $playlist_data);
        if (empty($playlist_term_id)) {
          \Drupal::messenger()->addMessage('Playlist cannot be created: ' . json_encode($playlist_data), 'error');
          return;
        }
      }

      // Playlist items.
      $youtube_playlist_items = $youtube_utils->getPlaylistItems($playlist_data["field_ytp_playlist_id"]);

      foreach ($youtube_playlist_items as $playlist_item) {
        $publishedat_date = strtotime($playlist_item["snippet"]["publishedAt"]);

        // Check if playlist already exists.
        $playlist_item_data = [
        // Video ID.
          "field_ytp_video_id" => $playlist_item["snippet"]["resourceId"]["videoId"],
        // Video title.
          "title" => $playlist_item["snippet"]["title"],
        // Video description.
          "field_ytp_description" => $playlist_item["snippet"]["description"],
        // Channel taxonomy term.
          "field_ytp_channel" => $channel_term_id,
        // Playlist URL.
          "field_ytp_url" => "https://www.youtube.com/watch?v=" . $playlist_item["snippet"]["resourceId"]["videoId"],
        // Publishing date.
          "field_ytp_published" => $publishedat_date,
        // Thumbnail default size.
          "field_ytp_thumbnail_default" => $playlist_item["snippet"]["thumbnails"]["default"]["url"] ?? '',
        // Thumbnail medium size.
          "field_ytp_thumbnail_medium" => $playlist_item["snippet"]["thumbnails"]["medium"]["url"] ?? '',
        // Thumbnail high size.
          "field_ytp_thumbnail_high" => $playlist_item["snippet"]["thumbnails"]["high"]["url"] ?? '',
        // Thumbnail standard size.
          "field_ytp_thumbnail_standard" => $playlist_item["snippet"]["thumbnails"]["standard"]["url"] ?? '',
        // Thumbnail maxres size.
          "field_ytp_thumbnail_maxres" => $playlist_item["snippet"]["thumbnails"]["maxres"]["url"] ?? '',
        ];
        // Check if playlist item already exists. Note that video_id may be empty.
        if ($playlist_item_node_id = $this->nodeGet(["field_ytp_video_id" => $playlist_item["snippet"]["resourceId"]["videoId"]])) {
          $node_video = Node::load($playlist_item_node_id);
          // Check if already exists on field category (avoid duplicate)
          $existing_playlists = array_column($node_video->get("field_ytp_playlists")->getValue(), "target_id");
          if (!in_array($playlist_term_id, $existing_playlists)) {
            $existing_playlists[] = $playlist_term_id;
            $playlist_item_data["field_ytp_playlists"] = $existing_playlists;
          }

          // Update item.
          unset($playlist_item_data["field_ytp_published"]);
          $this->nodeUpdate($playlist_item_node_id, $playlist_item_data);
        }
        else {
          // Playlist taxonomy term.
          $playlist_data["field_ytp_playlists"] = ["target_id" => $playlist_term_id];
          // Create item.
          $playlist_item_node_id = $this->nodeCreate($playlist_item_data);
        }
      }
    }

    \Drupal::messenger()->addMessage("YouTube Plus import ended successfully.", 'success');

  }

  /**
   * Rollback all imported contents.
   */
  public function rollbackChannel($channel) {
    var_dump($channel->id());
    $channel_term_id = \Drupal::entityQuery('taxonomy_term')
      ->accessCheck(TRUE)
      ->condition('vid', 'ytp_channels')
      ->condition(self::CHANNEL_ID, $channel->id())
      ->execute();
    $channel_term_id = reset($channel_term_id);

    $videos = \Drupal::entityQuery('node')
      ->accessCheck(TRUE)
      ->condition('type', 'ytp_video')
      ->condition('field_ytp_channel', $channel_term_id)
      ->execute();
    foreach ($videos as $nid) {
      Node::load($nid)->delete();
    }

    $playlists = \Drupal::entityQuery('taxonomy_term')
      ->accessCheck(TRUE)
      ->condition('vid', 'ytp_playlists')
      ->execute();
    foreach ($playlists as $tid) {
      Term::load($tid)->delete();
    }

    $channels = \Drupal::entityQuery('taxonomy_term')
      ->accessCheck(TRUE)
      ->condition('vid', 'ytp_channels')
      ->condition('field_ytp_channel', $channel_term_id)
      ->execute();
    foreach ($channels as $tid) {
      Term::load($tid)->delete();
    }

    \Drupal::messenger()->addMessage("YouTube Plus rollback ended successfully.", 'success');

  }

  /**
   * Rollback all imported contents.
   */
  public function rollBack() {
    $videos = \Drupal::entityQuery('node')
      ->accessCheck(TRUE)
      ->condition('type', 'ytp_video')
      ->execute();
    foreach ($videos as $nid) {
      Node::load($nid)->delete();
    }
    \Drupal::messenger()->addMessage("YouTube Plus videos content deleted.", 'success');

    $playlists = \Drupal::entityQuery('taxonomy_term')
      ->accessCheck(TRUE)
      ->condition('vid', 'ytp_playlists')
      ->execute();
    foreach ($playlists as $tid) {
      Term::load($tid)->delete();
    }
    \Drupal::messenger()->addMessage("YouTube Plus playlists deleted.", 'success');

    $channels = \Drupal::entityQuery('taxonomy_term')
      ->accessCheck(TRUE)
      ->condition('vid', 'ytp_channels')
      ->execute();
    foreach ($channels as $tid) {
      Term::load($tid)->delete();
    }
    \Drupal::messenger()->addMessage("YouTube Plus channels deleted.", 'success');

    \Drupal::messenger()->addMessage("YouTube Plus rollback ended successfully.", 'success');

  }

  /**
   * Get term by properties.
   *
   * @param $entityStorage
   *   Entity Storage of the entity to get.
   * @param string $bundle
   *   Bundle of the entity.
   * @param array $entity_data
   *   Entity data.
   * @param string $field
   *   Field to load by.
   *
   * @return int
   *   Term ID or 0 if none.
   */
  private function entityGet($entityStorage, $bundle, array $entity_data = [], $field = NULL) {
    if (!in_array($bundle, $entity_data) || empty($entity_data[$field])) {
      return 0;
    }

    $terms = $entityStorage->loadByProperties($entity_data);

    $term = reset($terms);

    return !empty($term) ? $term->id() : 0;
  }

  /**
   * Create term.
   *
   * @param $entityStorage
   *   Entity Storage of the entity to get.
   * @param string $bundle
   *   Bundle of the entity.
   * @param array $entity_data
   *   Entity data.
   *
   * @return int
   *   Term ID or 0 if none.
   */
  private function entityCreate($entityStorage, $bundle, array $entity_data = []) {

    if (empty($bundle) || empty($entity_data) || !in_array($bundle, $entity_data)) {
      return 0;
    }
    $ytp_entity = $entityStorage->create($entity_data);
    $ytp_entity->save();

    return !empty($ytp_entity->id()) ? $ytp_entity->id() : 0;
  }

  /**
   * Update term.
   *
   * @param $entityStorage
   *   Entity Storage of the entity to get.
   * @param int $id
   *   Term ID.
   * @param array $entity_data
   *   Entity data.
   *
   * @return int
   *   Term ID or 0 if none.
   */
  private function entityUpdate($entityStorage, $id = NULL, array $entity_data = []) {

    if (empty($id) || empty($entity_data)) {
      return 0;
    }
    $ytp_entity = $entityStorage->load($id);
    $update = FALSE;
    unset($entity_data['vid']);
    foreach ($entity_data as $key => $value) {
      $current = '';
      $current_value = $ytp_entity->get($key)->getValue() ?? '';
      if (empty($current_value)) {
        $current = '';
      }
      elseif (is_string($current_value)) {
        $current = $current_value;
      }
      elseif (isset($current_value[0]['uri'])) {
        $current = $current_value[0]['uri'];
      }
      elseif (isset($current_value[0]['value'])) {
        $current = $current_value[0]['value'];
      }
      elseif (isset($current_value[0]['target_id'])) {
        $current = $current_value[0]['target_id'];
      }
      if ($current != $value) {
        $ytp_entity->set($key, $value);
        $update = TRUE;
      }
    }

    if ($update) {
      $ytp_entity->save();
    }
    return $id;
  }

  /**
   * Get node by properties.
   *
   * @param array $node_data
   *   Node properties.
   *
   * @return int
   *   Node ID or 0 if none.
   */
  private function nodeGet(array $node_data = []) {
    if (empty($node_data)) {
      return 0;
    }

    $properties = [];
    $properties['type'] = 'ytp_video';
    $properties['status'] = 1;

    $properties = array_merge($properties, $node_data);

    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties($properties);
    $node = reset($nodes);

    return !empty($node) ? $node->id() : 0;
  }

  /**
   * Create node.
   *
   * @param array $node_data
   *   Node data to be set.
   *
   * @return int
   *   Node ID or 0 if none.
   */
  private function nodeCreate(array $node_data = []) {

    if (empty($node_data)) {
      return 0;
    }

    $properties = [];
    $properties['type'] = 'ytp_video';
    $properties['status'] = 1;

    $properties = array_merge($properties, $node_data);

    $node = Node::create($properties);
    $node->save();

    return !empty($node->id()) ? $node->id() : 0;
  }

  /**
   * Update node.
   *
   * @param int $nid
   *   Node ID.
   * @param array $node_data
   *   Node data to be set.
   *
   * @return int
   *   Node ID or 0 if none.
   */
  private function nodeUpdate($nid = NULL, array $node_data = []) {

    if (empty($nid) || empty($node_data)) {
      return 0;
    }

    $node = Node::load($nid);
    $update = FALSE;
    foreach ($node_data as $key => $value) {
      $current = '';
      $current_value = $node->get($key)->getValue() ?? '';
      if (empty($current_value)) {
        $current = '';
      }
      elseif (is_string($current_value)) {
        $current = $current_value;
      }
      elseif (isset($current_value[0]['uri'])) {
        $current = $current_value[0]['uri'];
      }
      elseif (isset($current_value[0]['value'])) {
        $current = $current_value[0]['value'];
      }
      elseif (isset($current_value[0]['target_id'])) {
        $current = $current_value[0]['target_id'];
      }

      if ($current != $value) {
        $node->set($key, $value);
        $update = TRUE;
      }
    }

     if ($update) {
      $node->save();
    }
    return $nid;
  }

}
