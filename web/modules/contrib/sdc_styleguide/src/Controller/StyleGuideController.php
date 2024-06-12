<?php declare(strict_types = 1);

namespace Drupal\sdc_styleguide\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\sdc_styleguide\Form\SDCDemoForm;
use Drupal\sdc_styleguide\Service\SDCDemoManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Single Directory Components Styleguide routes.
 */
final class StyleGuideController extends ControllerBase {

  /**
   * The controller constructor.
   */
  public function __construct(
    private readonly SDCDemoManager $demoManager
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('sdc_styleguide.demo_manager')
    );
  }

  /**
   * Builds the welcome page.
   */
  public function welcome() {
    return [
      '#theme' => 'styleguide_welcome_message',
    ];
  }

  /**
   * Builds the response.
   */
  public function __invoke(): array {
    $componentDemos = $this->demoManager->getDemos();

    $build = [
      '#prefix' => '<div class="sdc-styleguide-explorer">',
      '#suffix' => '</div>',
    ];
    foreach ($componentDemos as $group => $componentsInGroup) {
      $build[$group] = [
        '#prefix' => '<div class="sdc-styleguide-explorer__group">',
        '#suffix' => '</div>',
        'heading' => [
          '#markup' => $group,
          '#prefix' => '<h2 class="sdc-styleguide-explorer__group-title">',
          '#suffix' => '</h2>',
        ],
        'items' => [],
      ];

      $items = &$build[$group]['items'];
      foreach ($componentsInGroup as $componentId => $component) {
        $items[$componentId] = [
          '#prefix' => '<div class="sdc-styleguide-explorer__component">',
          '#suffix' => '</div>',
          'heading' => [
            '#prefix' => '<h3 class="sdc-styleguide-explorer__component-title">',
            '#suffix' => '</h3>',
            'link' => Link::createFromRoute($component['name'], 'sdc_styleguide.form', [
              'componentId' => $componentId,
            ], ['attributes' => ['class' => ['sdc-styleguide-explorer__demo-link']]])
              ->toRenderable(),
          ],
          'items' => [
            '#prefix' => '<div class="sdc-styleguide-explorer__component-demos">',
            '#suffix' => '</div>',
            '#theme' => 'item_list',
            '#items' => [],
          ],
        ];
        $demos = &$items[$componentId]['items']['#items'];

        // Builds the demos.
        foreach ($component['demos'] as $demoId => $data) {
          $demos[$demoId] = Link::createFromRoute($data['name'], 'sdc_styleguide.viewer', [
            'group' => $group,
            'component' => $componentId,
            'demo' => $demoId,
          ], [
            'attributes' => [
              'class' => ['sdc-styleguide-explorer__demo-link'],
            ],
          ]);
        }
      }
    }

    _sdc_styleguide_page_variables([
      'sidebar' => $build,
      'content' => [
        '#theme' => 'styleguide_component_viewer',
        '#url' => Url::fromRoute('sdc_styleguide.welcome'),
      ],
    ]);
    return ['#markup' => ''];
  }

  public function view(string $group, string $component, string $demo) {
    $componentDemos = $this->demoManager->getDemos();
    $demo = $componentDemos[$group][$component]['demos'][$demo];
    return [
      '#type' => 'component',
      '#component' => $component,
      '#props' => $demo['properties'] ?? [],
      '#slots' => array_map(fn ($x) => ['#type' => 'inline_template', '#template' => $x], $demo['slots'] ?? []),
    ];
  }

  /**
   * Generates a SDC demo form.
   * @param string $componentId
   *    The id of the component to generate the form for.
   *
   * @return array
   *    The form render array.
   */
  public function form(string $componentId) {
    return $this->formBuilder()->getForm(SDCDemoForm::class, $componentId);
  }

}
