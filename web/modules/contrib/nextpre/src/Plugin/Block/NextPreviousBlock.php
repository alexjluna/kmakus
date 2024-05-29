<?php

namespace Drupal\nextpre\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides a 'Next Previous' block.
 *
 * @Block(
 *   id = "next_previous_block",
 *   admin_label = @Translation("Next Previous link"),
 *   category = @Translation("Blocks")
 * )
 */
class NextPreviousBlock extends BlockBase implements BlockPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a NextPreviousBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $node_types = node_type_get_names();
    $form['content_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Content Types'),
      '#empty_option' => $this->t('-None-'),
      '#options' => $node_types,
      '#default_value' => $this->configuration['content_type'] ?? '',
      '#required' => TRUE,
    ];
    $form['previous_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Previous text'),
      '#description' => $this->t('Add your previous button name'),
      '#default_value' => $this->configuration['previous_text'] ?? '',
      '#required' => TRUE,
    ];
    $form['next_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Next text'),
      '#description' => $this->t('Add your next button name'),
      '#default_value' => $this->configuration['next_text'] ?? '',
      '#required' => TRUE,
    ];
    $form['previouslink_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Previous link class'),
      '#description' => $this->t('Add your class in previous link'),
      '#default_value' => $this->configuration['previouslink_class'] ?? '',
    ];
    $form['nextlink_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Next link class'),
      '#description' => $this->t('Add your class in next link'),
      '#default_value' => $this->configuration['nextlink_class'] ?? '',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->configuration['content_type'] = $form_state->getValue('content_type');
    $this->configuration['previous_text'] = $values['previous_text'];
    $this->configuration['next_text'] = $values['next_text'];
    $this->configuration['previouslink_class'] = $values['previouslink_class'];
    $this->configuration['nextlink_class'] = $values['nextlink_class'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $link = [];

    // Get the created time of the current node.
    $node = $this->routeMatch->getParameter('node');

    if ($node instanceof NodeInterface && $node->getType() == $this->configuration['content_type']) {

      $prev = $this->generatePrevious($node);
      if (!empty($prev)) {
        $link['prev'] = $prev;
      }

      $next = $this->generateNext($node);
      if (!empty($next)) {
        $link['next'] = $next;
      }
    }
    return $link;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // Get the created time of the current node.
    $node = $this->routeMatch->getParameter('node');
    if (!empty($node) && $node instanceof NodeInterface) {
      // If there is node add its cachetag.
      return Cache::mergeTags(parent::getCacheTags(), ['node:*']);
    }
    else {
      // Return default tags instead.
      return parent::getCacheTags();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

  /**
   * Lookup the previous node,youngest node which is still older than the node.
   *
   * @param string $node
   *   Show current page node id.
   *
   * @return array
   *   A render array for a previous node.
   */
  private function generatePrevious($node) {
    return $this->generateNextPrevious($node, 'prev');
  }

  /**
   * Lookup the next node,oldest node which is still younger than the node.
   *
   * @param string $node
   *   Show current page node id.
   *
   * @return array
   *   A render array for a next node.
   */
  private function generateNext($node) {
    return $this->generateNextPrevious($node, 'next');
  }

  const DIRECTION__NEXT = 'next';

  /**
   * Lookup the next or previous node.
   *
   * @param string $node
   *   Get current page node id.
   * @param string $direction
   *   Default value is "next" and other value come from
   *   generatePrevious() and generatePrevious().
   *
   * @return array
   *   Find the alias of the next node.
   */
  private function generateNextPrevious($node, $direction = self::DIRECTION__NEXT) {
    $comparison_opperator = '>';
    $sort = 'ASC';
    $display_text = $this->configuration['next_text'];
    $class = $this->configuration['nextlink_class'] ? $this->configuration['nextlink_class'] : 'btn';
    $current_nid = $node->id();
    $current_langcode = $node->get('langcode')->value;

    if ($direction === 'prev') {
      $comparison_opperator = '<';
      $sort = 'DESC';
      $display_text = $this->configuration['previous_text'];
      $class = $this->configuration['previouslink_class'] ? $this->configuration['previouslink_class'] : 'btn';
    }

    // Lookup 1 node younger (or older) than the current node.
    $query = $this->entityTypeManager->getStorage('node');
    $query_result = $query->getQuery();
    $next = $query_result->condition('nid', $current_nid, $comparison_opperator)
      ->condition('type', $this->configuration['content_type'])
      ->condition('status', 1)
      ->condition('langcode', $current_langcode)
      ->sort('nid', $sort)
      ->range(0, 1)
      ->accessCheck(TRUE)
      ->execute();

    // If this is not the youngest (or oldest) node.
    if (!empty($next) && is_array($next)) {
      $next = array_values($next);
      $next = $next[0];

      // Find the alias of the next node.
      $nid = $next;
      $url = Url::fromRoute('entity.node.canonical', ['node' => $nid], []);
      $link = Link::fromTextAndUrl($display_text, Url::fromUri('internal:/' . $url->getInternalPath()));
      $link = $link->toRenderable();
      $link['#attributes'] = ['class' => ['nextpre__btn', $class]];
      return $link;
    }
    return '';
  }

}
