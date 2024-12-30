<?php

namespace Drupal\field_workflow_validation\EventSubscriber;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Event\EntityPresaveEvent;

/**
 * Validates required fields based on Workflow states before saving the entity.
 */
class EntityPresaveSubscriber implements EventSubscriberInterface {
  use StringTranslationTrait;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new EntityPresaveSubscriber.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager) {
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      EntityPresaveEvent::class => 'onPresave',
    ];
  }

  /**
   * Validates fields before saving the entity.
   *
   * @param \Drupal\Core\Event\EntityPresaveEvent $event
   *   The entity presave event.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Throws exception if a required field is empty.
   */
  public function onPresave(EntityPresaveEvent $event) {

dd($event);
    $entity = $event->getEntity();

    // Ensure the entity is a node and has a workflow state.
    if ($entity->getEntityTypeId() === 'node' && $entity->hasField('workflow')) {
      $workflow_state = $entity->get('workflow')->value;
      $field_definitions = $this->entityFieldManager->getFieldDefinitions('node', $entity->bundle());

      foreach ($field_definitions as $field_name => $field_definition) {
        // Get the required states for the field.
        $required_states = $field_definition->getThirdPartySetting('field_workflow_validation', 'required_states', []);
        if (in_array($workflow_state, $required_states, TRUE)) {
          $field_value = $entity->get($field_name)->getValue();
          if (empty($field_value)) {
            throw new EntityStorageException(sprintf('The field "%s" is required when the node is in the "%s" workflow state.', $field_definition->getLabel(), $workflow_state));
          }
        }
      }
    }
  }
}
