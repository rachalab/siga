<?php

namespace Drupal\pluginformalter\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides the Form alter plugin manager.
 */
class FormAlterManager extends DefaultPluginManager {

  use FormAlterManagerTrait;

  /**
   * Constructs a new FormAlterManager object.
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
    parent::__construct('Plugin/FormAlter', $namespaces, $module_handler, 'Drupal\pluginformalter\Plugin\FormAlterInterface', 'Drupal\pluginformalter\Annotation\FormAlter');

    $this->alterInfo('pluginformalter_form_alter_info');
    $this->setCacheBackend($cache_backend, 'pluginformalter_form_alter_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $options) {
    $plugins = [];
    foreach ($this->getDefinitions() as $plugin_id => $definition) {
      if (
        (isset($definition['base_form_id']) && isset($options['base_form_id']) && $this->matches($options['base_form_id'], $definition['base_form_id'])) ||
        (isset($definition['form_id']) && isset($options['form_id']) && $this->matches($options['form_id'], $definition['form_id']))
      ) {
        $plugins[$plugin_id] = $this->createInstance($plugin_id, $options);
      }
    }
    uasort($plugins, [$this, 'sort']);

    return $plugins;
  }

}
