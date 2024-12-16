<?php

namespace Drupal\views_kanban_demo\Event;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Wraps a node insertion demo event for event listeners.
 */
class KanbanNodeInsertEvent extends Event {

  const KANBAN_NODE_INSERT = 'event_subscriber_kanban.node.insert';

  /**
   * Constructs a node insertion demo event object.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   */
  public function __construct(protected EntityInterface $entity) {
  }

  /**
   * Get the inserted entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Return Entity.
   */
  public function getEntity() {
    // @todo send mail, notification to assignor.
    return $this->entity;
  }

}
