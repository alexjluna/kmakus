<?php declare(strict_types = 1);

namespace Drupal\sdc_styleguide\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sdc\ComponentPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Single Directory Components Styleguide form.
 */
final class SDCDemoForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'sdc_styleguide_demo_form';
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(protected ComponentPluginManager $componentPluginManager) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.sdc')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $componentId = NULL): array {
    $found = FALSE;
    foreach ($this->componentPluginManager->getAllComponents() as $component) {
      $definition = $component->getPluginDefinition();
      if ($componentId == $definition['id']) {
        $found = TRUE;
        break;
      }
    }

    // Error message when not found.
    if (!$found) {
      return [
        '#markup' => $this->t('The component @component does not exist.', [
          '@component' => new FormattableMarkup(
            '<strong>@componentId</strong>',
            ['@componentId' => $componentId],
          ),
        ]),
      ];
    }

    // Field mapping.
    $fapi_map = [
      'string' => 'textfield',
      'number' => 'number',
      'boolean' => 'checkbox',
    ];

    // Initial form setup.
    $form['component'] = [
      '#attributes' => [
        'id' => 'component-wrapper',
      ],
      '#tree' => TRUE,
      '#type' => 'container',
      'id' => [
        '#type' => 'value',
        '#value' => $componentId,
      ],
      'fields' => [],
      'slots' => [],
    ];

    // Gets each field based on the property.
    foreach ($definition['props']['properties'] as $field => $field_definition) {
      $settings = $definition['props']['properties'][$field];
      $form['component']['fields'][$field] = [
        '#required' => TRUE,
        '#type' => $fapi_map[$settings['type']],
        '#title' => $settings['title'],
      ];
    }

    // All available slots are set as text areas.
    foreach ($definition['slots'] as $id => $slot) {
      $form['component']['slots'][$id] = [
        '#description' => $slot['description'],
        '#title' => $slot['title'],
        '#type' => 'textarea',
      ];
    }

    // Wraps them in fieldsets if they have elements.
    if (!empty($form['component']['fields'])) {
      $form['component']['fields']['#type'] = 'fieldset';
      $form['component']['fields']['#title'] = $this->t('Properties');
    }
    if (!empty($form['component']['slots'])) {
      $form['component']['slots']['#type'] = 'fieldset';
      $form['component']['slots']['#title'] = $this->t('Slots');
    }

    // Submit button with AJAX support.
    $form['submit'] = [
      '#ajax' => [
        'callback' => '::onComponentSubmit',
        'event' => 'click',
        'wrapper' => 'result',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Verifying entry...'),
        ],
      ],
      '#attributes' => [
        'type' => 'button',
      ],
      '#type' => 'button',
      '#value' => 'submit',
    ];

    // Rendered result..
    $form['rendered_result'] = [
      '#attributes' => [
        'id' => 'result',
      ],
      '#type' => 'container',
    ];

    // Returns form if no values submitted yet.
    $submittedComponent = $form_state->getValue('component');
    if (empty($submittedComponent)) {
      return $form;
    }

    // Converts slots to inline templates.
    foreach ($submittedComponent['slots'] as &$slot) {
      $slot = [
        '#template' => $slot,
        '#type' => 'inline_template',
      ];
    }

    // Updates rendered result.
    $form['rendered_result']['component'] = [
      '#type' => 'component',
      '#component' => $submittedComponent['id'],
      '#props' => $submittedComponent['fields'],
      '#slots' => $submittedComponent['slots'],
    ];

    return $form;
  }

  /**
   * AJAX handler for when the component values are submitted.
   */
  public function onComponentSubmit(array &$form, FormStateInterface $form_state) {
    return $form['rendered_result'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
  }

}
