<?php

namespace Drupal\youtube_plus\Entity;

use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\youtube_plus\YoutubePlusChannelInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Theme Selector entity.
 *
 * @ConfigEntityType(
 *   id = "youtube_plus_channel",
 *   label = @Translation("Youtube Plus Channel"),
 *   handlers = {
 *    "list_builder" = "Drupal\youtube_plus\Controller\YouTubePlusListBuilder",
 *    "form" = {
 *       "add-form" = "Drupal\youtube_plus\Form\ChannelForm",
 *       "edit-form" = "Drupal\youtube_plus\Form\ChannelForm",
 *       "delete-form" = "Drupal\youtube_plus\Form\ChannelDeleteForm",
 *      },
 *    },
 *   config_prefix = "youtube_plus",
 *   admin_permission = "administrater youtube plus",
 *   entity_keys = {
 *     "name" = "name",
 *     "id" = "id",
 *     "type" = "type"
 *   },
 *   config_export = {
 *     "id",
 *     "name",
 *     "type"
 *   }
 * )
 */
class YoutubePlusChannelEntity extends ConfigEntityBase implements YoutubePlusChannelInterface {

  use StringTranslationTrait, MessengerTrait;

  /**
   * Theme Selector entity id.
   *
   * @var string
   */
  protected $id;

  /**
   * Theme Selector entity name.
   *
   * @var string
   */
  protected $name;

  /**
   * Theme Selector config type.
   *
   * @var int
   */
  protected $type;

  /**
   * Node name.
   *
   * @var string
   */

  /**
   * Returns the entity name.
   *
   * @return string
   *   The entity name.
   */
  public function getName() {
    return $this->get('name');
  }

  /**
   * Sets the entity name.
   *
   * @param string $name
   *   Node name.
   *
   * @return $this
   *   The Channel entity.
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * Returns the entity name.
   *
   * @return string
   *   The channel ID
   */
  public function getId() {
    return $this->get('id');
  }

  /**
   * Returns the config type.
   *
   * @return int
   *   The config type
   */
  public function getType() {
    return $this->get('type');
  }

  /**
   * Show a message accordingly to status, after creating/updating an entity.
   *
   * @param int $status
   *   Status int, returned after creating/updating an entity.
   */
  public function statusMessage($status) {
    if ($status) {
      $this->messenger()->addMessage($this->t('Saved the %label entity.', ['%label' => $this->getName()]));
    }
    else {
      $this->messenger()->addMessage($this->t('The %label entity was not saved.', ['%label' => $this->getName()]));
    }
  }

}
