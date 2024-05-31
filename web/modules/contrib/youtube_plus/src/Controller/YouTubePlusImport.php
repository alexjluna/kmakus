<?php

namespace Drupal\youtube_plus\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\youtube_plus\YouTubePlusService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Youtube Controller to execute import and rollback of entities.
 */
class YouTubePlusImport extends ControllerBase {

  /**
   * The youtube_plus service.
   *
   * @var \Drupal\youtube_plus\YouTubePlusService
   */
  protected $youtubePlus;

  /**
   * {@inheritdoc}
   */
  public function __construct(YouTubePlusService $youtube_plus) {
    $this->youtubePlus = $youtube_plus;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('youtube_plus.actions'),
    );
  }

  /**
   * Imports all existing channels.
   */
  public function rollback($channel) {
    $this->youtubePlus->rollbackChannel($channel);
    return new RedirectResponse('/admin/config/services/youtube_plus');
  }

  /**
   * Import a channel.
   */
  public function run($channel) {
    $this->youtubePlus->importChannel($channel);
    return new RedirectResponse('/admin/config/services/youtube_plus');
  }

}
