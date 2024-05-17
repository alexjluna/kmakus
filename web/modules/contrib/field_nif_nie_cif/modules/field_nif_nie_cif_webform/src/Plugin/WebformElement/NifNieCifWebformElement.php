<?php

namespace Drupal\field_nif_nie_cif_webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\field_nif_nie_cif\Helper\IdentificationHelper;
use Drupal\webform\Plugin\WebformElement\TextBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a webform element for entering a Spanish NIF, NIE, or CIF.
 *
 * @WebformElement(
 *   id = "nif_nie_cif",
 *   label = @Translation("NIF/NIE/CIF"),
 *   description = @Translation("Provides a webform element for entering a Spanish NIF, NIE, or CIF."),
 *   category = @Translation("Custom elements"),
 * )
 */
class NifNieCifWebformElement extends TextBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return [
      'title' => '',
      'default_value' => '',
      'description' => '',
      'placeholder' => '',
      'required' => FALSE,
    ] + parent::getDefaultProperties();
  }

  /**
   * Prepares a #type 'textfield' render element for input.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   * @param \Drupal\webform\WebformSubmissionInterface|null $webform_submission
   *   The current webform submission.
   *
   * @return array
   *   The prepared form element.
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    $element['#type'] = 'textfield';
    $element['#attributes']['data-identification-number'] = 'true';
    $element['#attached']['library'][] = 'field_nif_nie_cif/input_filter';
    $element['#element_validate'][] = [$this, 'validate'];
    return parent::prepare($element, $webform_submission);
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $value = $element['#value'];
    $isValid = IdentificationHelper::validateNif($value) || IdentificationHelper::validateNie($value) || IdentificationHelper::validateCif($value);

    if (!$isValid) {
      $form_state->setError($element, $this->t('The identification number provided is not valid.'));
    }
  }

}
