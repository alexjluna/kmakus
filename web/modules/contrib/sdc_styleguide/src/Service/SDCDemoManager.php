<?php

namespace Drupal\sdc_styleguide\Service;

use Drupal\Core\Serialization\Yaml;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\sdc\ComponentPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;

class SDCDemoManager {

  use StringTranslationTrait;

  /**
   * The controller constructor.
   */
  public function __construct(
    private readonly ComponentPluginManager $pluginManagerSdc,
  ) {}

  /**
   *  Gets a list of all the available SDC on site and their corresponding demos
   *  if available.
   *
   * @return array
   *  The list of demos grouped by Group and Component.
   */
  public function getDemos() {
    $componentDemos = [];
    $ungroupedIndex = $this->t('Ungrouped')->render();
    foreach ($this->pluginManagerSdc->getAllComponents() as $component) {
      $definition = $component->getPluginDefinition();
      $group = $definition['group'] ?? $ungroupedIndex;
      $componentId = $component->getPluginId();

      if (!isset($componentDemos[$group])) {
        $componentDemos[$group] = [];
      }

      // Adds component.
      $componentDemos[$group][$componentId] = [
        'name' => $definition['name'],
        'demos' => [],
      ];
      $demos = &$componentDemos[$group][$componentId]['demos'];

      // Finds the demos for the current component and adds them to the explorer.
      $finder = new Finder();
      $component_name = $definition['machineName'];
      $finder->in($definition['path'])->files()->name("{$component_name}.demo.*.yml");
      foreach ($finder as $file) {
        $key = str_replace(['.yml', "{$component_name}.demo."], '', $file->getFilename());
        $contents = $file->getContents();
        $demo_data = Yaml::decode($contents);
        $demos[$key] = $demo_data;
      }

      // Sorts by component readable name.
      uasort($demos, fn ($a, $b) => strcmp($a['name'], $b['name']));
    }

    foreach ($componentDemos as &$group) {
      uasort($group, fn($a, $b) => strcmp($a['name'], $b['name']));
    }
    ksort($componentDemos);
    return $componentDemos;
  }


}
