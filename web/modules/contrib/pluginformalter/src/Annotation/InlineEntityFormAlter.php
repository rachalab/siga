<?php

namespace Drupal\pluginformalter\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Inline Entity Form alter item annotation object.
 *
 * @see \Drupal\pluginformalter\Plugin\InlineEntityFormAlterManager
 * @see plugin_api
 *
 * @Annotation
 */
class InlineEntityFormAlter extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The type of form we are going to alter.
   *
   * Allowed values are the following.
   * - entity_form
   * - reference_form
   * - table_fields.
   *
   * @var string
   */
  public $type;

  /**
   * The type of the referenced entities.
   *
   * @var string
   */
  public $entity_type;

  /**
   * The bundle of the referenced entity.
   *
   * @var string
   */
  public $bundle;

  /**
   * The name of the reference field on which IEF is operating.
   *
   * @var string
   */
  public $field_name;

  /**
   * Bundles allowed on the reference field.
   *
   * @var string
   */
  public $allowed_bundles;

  /**
   * The type of the parent entity.
   *
   * @var string
   */
  public $parent_entity_type;

  /**
   * The bundle of the parent entity.
   *
   * @var string
   */
  public $parent_bundle;

  /**
   * The plugin weight which affects the alterations queue.
   *
   * @var int
   */
  public $weight = 0;

}
