<?php

namespace Drupal\views_kanban_demo\EventSubscriber;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views_kanban_demo\Event\KanbanNodeInsertEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Logs the creation of a new node.
 */
class KanbanNodeInsertSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * KabanEventSubscriber constructor.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The message to send notification.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   Date formatter service.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   Logger Service.
   */
  public function __construct(protected MessengerInterface $messenger, protected DateFormatterInterface $dateFormatter, LoggerChannelFactory $loggerFactory) {
    $this->loggerFactory = $loggerFactory->get('event_subscriber_kanban');
  }

  /**
   * Log the creation of a new node.
   *
   * @param \Drupal\views_kanban_demo\Event\KanbanNodeInsertEvent $event
   *   Event.
   */
  public function onKanbanNodeInsert(KanbanNodeInsertEvent $event) {
    // @todo send mail notification when ticket have change status.
    $entity = $event->getEntity();
    $this->loggerFactory->notice(
      'New @type: @title. Created by: @owner',
      [
        '@type' => $entity->getType(),
        '@title' => $entity->label(),
        '@owner' => $entity->getOwner()->getDisplayName(),
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KanbanNodeInsertEvent::KANBAN_NODE_INSERT][] = ['onKanbanNodeInsert'];
    return $events;
  }

}
