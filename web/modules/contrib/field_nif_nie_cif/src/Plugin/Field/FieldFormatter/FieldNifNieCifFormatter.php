<?php

namespace Drupal\field_nif_nie_cif\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation for the 'nif_nie_cif' formatter.
 *
 * @FieldFormatter(
 *   id = "nif_nie_cif_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "nif_nie_cif"
 *   }
 * )
 */
class FieldNifNieCifFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      // Render each element as a string.
      $elements[$delta] = [
        '#markup' => $this->t('@type: @number', [
          '@type' => $item->type,
          '@number' => $item->number,
        ],
        ),
      ];
    }

    return $elements;
  }

}
