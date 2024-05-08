<?php

namespace Drupal\commerce_product;

use Drupal\commerce_product\EventSubscriber\VariationFieldComponentSubscriber;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Swap field rendered when layout builder module is on.
 */
class CommerceProductServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Get list of modules.
    $modules = $container->getParameter('container.modules');

    // Check if there is layout builder and swap field renderer service.
    if (isset($modules['layout_builder'])) {
      $definition = $container->getDefinition('commerce_product.variation_field_renderer');
      $definition->setClass(ProductVariationFieldRendererLayoutBuilder::class)
        ->addArgument(new Reference('entity_display.repository'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    // Register new service only when layout builder module is enabled.
    $modules = $container->getParameter('container.modules');
    if (isset($modules['layout_builder'])) {
      $container->register('commerce_product.variation_field_component_subscriber')
        ->setClass(VariationFieldComponentSubscriber::class)
        ->addTag('event_subscriber');
    }
  }

}
