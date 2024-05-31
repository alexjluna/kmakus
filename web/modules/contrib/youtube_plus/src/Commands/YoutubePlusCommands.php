<?php

namespace Drupal\youtube_plus\Commands;

use Drush\Commands\DrushCommands;
use Drupal\youtube_plus\YouTubePlusService;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * A Drush commandfile.
 */
class YoutubePlusCommands extends DrushCommands {

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The youtube_plus manager.
   *
   * @var \Drupal\youtube_plus\YouTubePlusService
   */
  protected $youtubePlus;

  /**
   * Youtube Plus Commands constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration object factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\youtube_plus\YouTubePlusService $youtubePlus
   *   The youtube_plus manager.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    EntityTypeManagerInterface $entity_type_manager,
    YouTubePlusService $youtubePlus
    ) {
    $this->entityManager = $entity_type_manager;
    $this->config = $configFactory->getEditable('youtube_plus.settings');
    $this->youtubeplus = $youtubePlus;
  }

  /**
   * Run YouTube Plus importation.
   *
   * @usage drush youtubeplus-run
   *   Run YouTube Plus importation
   * @validate-module-enabled youtube_plus
   *
   * @command youtubeplus:run
   * @aliases ytp-run,youtubeplus-run
   */
  public function run() {
    $this->output()->writeln('YouTube Plus importation started.');
    $entity_channels = $this->entityManager->getStorage('youtube_plus_channel')->loadMultiple();
    foreach ($entity_channels as $channel) {
      $this->youtubeplus->importChannel($channel);
    }
    $this->output()->writeln('YouTube Plus importation run successfuly.');
  }

  /**
   * Rollback YouTube Plus imported content.
   *
   * @usage drush youtubeplus-rollback
   *   Rollback YouTube Plus imported content.
   * @validate-module-enabled youtube_plus
   *
   * @command youtubeplus:rollback
   * @aliases ytp-rb,youtubeplus-rollback
   */
  public function rollback() {
    $this->output()->writeln('YouTube Plus rollback started.');
    $this->youtubeplus->rollback();
    $this->output()->writeln('YouTube Plus rollback run successfuly.');

  }

}
