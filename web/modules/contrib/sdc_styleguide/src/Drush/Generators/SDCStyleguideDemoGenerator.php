<?php

namespace Drupal\sdc_styleguide\Drush\Generators;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\sdc\ComponentPluginManager;
use DrupalCodeGenerator\Asset\AssetCollection;
use DrupalCodeGenerator\Attribute\Generator;
use DrupalCodeGenerator\Command\BaseGenerator;
use DrupalCodeGenerator\GeneratorType;
use Drush\Commands\AutowireTrait;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Generates a custom demo for one of the available SDCs on site.
 */
#[Generator(
  name: 'sdc_styleguide:demo',
  description: 'Generates a SDC Styleguide demo',
  aliases: ['sdcs-demo'],
  templatePath: __DIR__,
  type: GeneratorType::MODULE_COMPONENT,
)]
class SDCStyleguideDemoGenerator extends BaseGenerator {

  use AutowireTrait;
  use StringTranslationTrait;

  /**
   * Inject dependencies into the Generator.
   */
  public function __construct(
    #[Autowire(service: 'plugin.manager.sdc')]
    protected readonly ComponentPluginManager $componentPluginManager,
    protected readonly FileSystemInterface $fileSystem,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function generate(array &$vars, AssetCollection $assets): void {
    $ir = $this->createInterviewer($vars);
    $definitions = $this->componentPluginManager->getAllComponents();
    $components = [];
    $componentOptions = [];
    foreach ($definitions as $componentDefinition) {
      $definition = $componentDefinition->getPluginDefinition();
      $option = "{$definition['name']} ({$componentDefinition->getPluginId()})";
      $components[$option] = [
        'name' => $definition['name'],
        'machineName' => $definition['machineName'],
        'path' => $definition['path'],
        'properties' => $definition['props']['properties'] ?? [],
        'required' => $definition['props']['required'] ?? NULL,
        'slots' => $definition['slots'] ?? NULL,
        'type' => $definition['extension_type'],
      ];
      $componentOptions[] = $option;
    }
    sort($componentOptions);

    // Get the component to build the demo for.
    $choice = $ir->choice($this->t('For what SDC do you want to create a demo?'), $componentOptions);
    $selectedComponent = $components[$componentOptions[$choice]];
    $this->io()->title($this->t('New demo for @demo_name', [
      '@demo_name' => $selectedComponent['name'],
    ]));

    // Forces a demo name.
    $demoFilename = NULL;
    $demoMachineName = NULL;
    $ir->ask($this->t('How would you like to name your demo?'), NULL, function ($name) use ($selectedComponent, &$demoMachineName, &$demoFilename) {
      if (!$name) {
        throw new \Exception($this->t('Please set a value.'));
      }

      // Confirms a demo with the same name on component folder does not exist.
      $demoMachineName = preg_replace('/[^a-z0-9]/', '_', strtolower($name));
      $demoFilename = "{$selectedComponent['path']}/{$selectedComponent['machineName']}.demo.{$demoMachineName}.yml";
      if (file_exists($demoFilename)) {
        throw new \Exception($this->t('A demo with that name already exists. Please use a different name.'));
      }
    });

    // Prepares demo general structure.
    $demo = [
      'name' => $demoMachineName,
      'description' => $ir->ask($this->t('Please add a description for your demo. (Optional)')) ?? '',
      'properties' => [],
      'slots' => [],
    ];

    // Fills property values.
    if (isset($selectedComponent['properties'])) {
      foreach ($selectedComponent['properties'] as $name => $property) {
        $required = $selectedComponent['required'];
        $demo['properties'][$name] = $ir->ask("Please set the {$property['title']} value. ({$property['description']})", NULL, function ($value) use ($name, $required) {
          if (in_array($name, $required) && !$value) {
            throw new \Exception($this->t('This property is required. Please set a value.'));
          }

          // @TODO: Check for type validation. (bool, number, string, attributes).
          return $value ?? '';
        });
      }
    }

    // Fills demos.
    if (isset($selectedComponent['slots'])) {
      foreach ($selectedComponent['slots'] as $name => $slot) {
        // @TODO: Ask the user if they want to use Drupal stuff (Nodes, Media, Views, Another SDC Demo) or if they want
        // to use a free form string.
        $demo['slots'][$name] = $ir->ask("Please set the {$slot['title']} value. ({$slot['description']})") ?? '';
      }
    }

    // Writes file.
    $this->fileSystem->saveData(Yaml::encode($demo), $demoFilename, FileSystemInterface::EXISTS_ERROR);
  }
}
