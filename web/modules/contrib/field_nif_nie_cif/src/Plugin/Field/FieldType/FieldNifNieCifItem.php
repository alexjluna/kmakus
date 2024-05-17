<?php

namespace Drupal\field_nif_nie_cif\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'nif_nie_cif' field type.
 *
 * @FieldType(
 *   id = "nif_nie_cif",
 *   label = @Translation("NIF NIE CIF"),
 *   description = @Translation("A field containing a NIF, NIE or CIF."),
 *   default_widget = "nif_nie_cif_default",
 *   default_formatter = "nif_nie_cif_default"
 * )
 */
class FieldNifNieCifItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'type' => [
          'type' => 'char',
          'length' => 3,
        ],
        'number' => [
          'type' => 'varchar',
          'length' => 255,
        ],
      ],
      'indexes' => [
        'number' => ['number'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('number')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];

    $properties['type'] = DataDefinition::create('string')
      ->setLabel(t('Type'))
      ->setDescription(t('The type of identifier (NIF, NIE, CIF).'))
      ->setRequired(TRUE);

    $properties['number'] = DataDefinition::create('string')
      ->setLabel(t('Number'))
      ->setDescription(t('The identifier number.'))
      ->setRequired(TRUE);

    return $properties;
  }

}
