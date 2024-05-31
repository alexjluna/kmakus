<?php

namespace Drupal\youtube_plus\Controller;

use Drupal\Core\Url;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of youtube_plus entities.
 */
class YouTubePlusListBuilder extends ConfigEntityListBuilder {

  const NAME = 'name';
  const CHANNELID = 'channel_id';

  /**
   * Constructs the table header.
   *
   * @return array
   *   Table header
   */
  public function buildHeader() {
    $header[self::NAME] = $this->t('Channel Name');
    $header[self::CHANNELID] = $this->t('Channel ID');
    return $header + parent::buildHeader();

  }

  /**
   * Constructs the table rows.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Webform content creator entity.
   *
   * @return \Drupal\Core\Entity\EntityListBuilder
   *   A render array structure of fields for this entity.
   */
  public function buildRow(EntityInterface $entity) {
    $row[self::NAME] = $entity->getName();
    $row[self::CHANNELID] = $entity->getId();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity, $type = 'edit') {
    $operations = parent::getDefaultOperations($entity);

    $operations['edit'] = [
      'title' => $this->t('Edit'),
      'weight' => 0,
      'url' => Url::fromRoute('entity.youtube_plus_channel.edit_form', ['youtube_plus_channel' => $entity->id()]),
    ];

    $operations['import'] = [
      'title' => $this->t('Import'),
      'weight' => 0,
      'url' => Url::fromUri('base:admin/config/services/youtube_plus/run/' . $entity->id(), ['absolute' => TRUE]),
    ];

    $operations['rollback'] = [
      'title' => $this->t('Rollback'),
      'weight' => 0,
      'url' => Url::fromUri('base:admin/config/services/youtube_plus/rollback/' . $entity->id(), ['absolute' => TRUE]),
    ];

    $operations['delete'] = [
      'title' => $this->t('Delete'),
      'weight' => 0,
      'url' => Url::fromRoute('entity.youtube_plus_channel.delete_form', ['youtube_plus_channel' => $entity->id()]),
    ];

    return $operations;
  }

}
