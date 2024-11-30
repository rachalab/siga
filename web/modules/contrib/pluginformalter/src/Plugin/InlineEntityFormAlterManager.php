<?php

namespace Drupal\pluginformalter\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides the Inline Entity Form alter plugin manager.
 */
class InlineEntityFormAlterManager extends DefaultPluginManager {

  use FormAlterManagerTrait;

  /**
   * Constructs a new InlineEntityFormAlterManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/FormAlter', $namespaces, $module_handler, 'Drupal\pluginformalter\Plugin\FormAlterInterface', 'Drupal\pluginformalter\Annotation\InlineEntityFormAlter');

    $this->alterInfo('pluginformalter_form_alter_info');
    $this->setCacheBackend($cache_backend, 'pluginformalter_form_alter_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $options) {
    $plugins = [];
    $properties = [
      'type',
      'entity_type',
      'parent_entity_type',
      'bundle',
    ];
    foreach ($this->getDefinitions() as $plugin_id => $definition) {
      if (isset($options['bundle']) && isset($definition['bundle']) && ($definition['bundle'] === '*')) {
        $definition['bundle'] = $options['bundle'];
      }
      
      $skip = FALSE;
      foreach ($properties as $property) {
        if (!$this->isValidProperty($options, $definition, $property)) {
          $skip = TRUE;
          break;
        }
      }
      
      if ($skip) {
        continue;
      }

      $configuration = array_intersect_assoc($options, $definition);
      if (
        (($options['type'] == 'table_fields') && !empty($configuration)) ||
        (count($configuration) == count($options))
      ) {
        $plugins[$plugin_id] = $this->createInstance($plugin_id, $configuration);
      }
    }
    uasort($plugins, [$this, 'sort']);

    return $plugins;
  }
  
  /**
   * Is valid property.
   *
   * @param array $options
   *   The options array.
   * @param array $definition
   *   The plugin definition array.
   * @param string $property
   *   The property name.
   *
   * @return bool
   *   A boolean indicating if the property is either valid or not.
   */
  protected function isValidProperty(array $options, array $definition, $property) {
    if (!isset($options[$property]) || !isset($definition[$property])) {
      return TRUE;
    }
    return ($options[$property] == $definition[$property]);
  }

}
