<?php

namespace Drupal\youtube_plus;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining an Simplenews Mailjet Subscription entity.
 */
interface YoutubePlusChannelInterface extends ConfigEntityInterface {

  /**
   * Returns the entity name.
   *
   * @return string
   *   The entity name.
   */
  public function getName();

  /**
   * Sets the entity name.
   *
   * @param string $name
   *   Node name.
   *
   * @return $this
   *   The Channel entity.
   */
  public function setName($name);

  /**
   * Returns the entity id.
   *
   * @return string
   *   The entity id.
   */
  public function getId();

  /**
   * Show a message accordingly to status, after creating/updating an entity.
   *
   * @param int $status
   *   Status int, returned after creating/updating an entity.
   */
  public function statusMessage($status);

}
