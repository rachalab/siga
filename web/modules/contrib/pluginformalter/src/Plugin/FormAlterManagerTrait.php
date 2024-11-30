<?php

namespace Drupal\pluginformalter\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Trait Form Alter Manager Trait.
 *
 * @package Drupal\pluginformalter\Plugin
 */
trait FormAlterManagerTrait {

  /**
   * Sort plugins by weight.
   *
   * @param \Drupal\Component\Plugin\PluginInspectionInterface $a
   *   A Form Alter plugin.
   * @param \Drupal\Component\Plugin\PluginInspectionInterface $b
   *   A Form Alter plugin.
   *
   * @return int
   *   The sorting result.
   */
  protected function sort(PluginInspectionInterface $a, PluginInspectionInterface $b) {
    $a_definition = $a->getPluginDefinition();
    $b_definition = $b->getPluginDefinition();
    if ($a_definition['weight'] == $b_definition['weight']) {
      return 0;
    }
    return ($a_definition['weight'] < $b_definition['weight']) ? -1 : 1;
  }

  /**
   * Matches an id amongst an array of ids.
   *
   * @param string $id
   *  The id.
   * @param array $ids
   *  The ids array.
   *
   * @return bool|int
   *   The matching result.
   */
  protected function matches($id, array $ids) {
    foreach ($ids as $plugin_id) {
      $pattern = '/^' . str_replace('*', '.*', $plugin_id) . '$/';
      if (preg_match($pattern, $id)) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
