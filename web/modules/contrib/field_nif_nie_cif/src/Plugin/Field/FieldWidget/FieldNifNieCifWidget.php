<?php

namespace Drupal\field_nif_nie_cif\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field_nif_nie_cif\Helper\IdentificationHelper;

/**
 * A widget for the NIF/NIE/CIF field.
 *
 * @FieldWidget(
 *   id = "nif_nie_cif_default",
 *   label = @Translation("NIF NIE CIF select"),
 *   field_types = {
 *     "nif_nie_cif"
 *   }
 * )
 */
class FieldNifNieCifWidget extends WidgetBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose Type'),
      '#options' => [
        'NIF' => $this->t('NIF'),
        'NIE' => $this->t('NIE'),
        'CIF' => $this->t('CIF'),
      ],
      '#default_value' => $items[$delta]->type ?? NULL,
    ];

    $element['number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Identification Number'),
      '#default_value' => $items[$delta]->number ?? NULL,
      '#maxlength' => 255,
      '#element_validate' => [
        [$this, 'validateNifNieCif'],
      ],
      '#attributes' => [
        'data-identification-number' => 'edit-identification-number-' . $delta,
      ],
      '#attached' => [
        'library' => [
          'field_nif_nie_cif/input_filter',
        ],
      ],
    ];
    $element['#after_build'][] = [$this, 'afterBuild'];

    return $element;
  }

  /**
   * Validates the identification number based on the selected type.
   *
   * @param array $element
   *   The form element to validate.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param array $form
   *   The form structure.
   */
  public function validateNifNieCif(&$element, FormStateInterface $form_state, &$form) {
    $parents = array_slice($element['#parents'], 0, -1);
    $field_values = $form_state->getValue($parents);
    $type = $field_values['type'] ?? NULL;
    $number = $field_values['number'] ?? NULL;
    if (empty($type) || empty($number)) {
      return;
    }
    $isValid = FALSE;
    switch ($type) {
      case 'NIF':
        $isValid = IdentificationHelper::validateNif($number);
        break;

      case 'NIE':
        $isValid = IdentificationHelper::validateNie($number);
        break;

      case 'CIF':
        $isValid = IdentificationHelper::validateCif($number);
        break;
    }
    if (!$isValid) {
      $form_state->setErrorByName(implode('][', $parents) . '][number', $this->t('The identification number provided is not valid.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function afterBuild(array $element, FormStateInterface $form_state) {
    $element['#form_id'] = 'field_nif_nie_cif_form';

    return $element;
  }

}
