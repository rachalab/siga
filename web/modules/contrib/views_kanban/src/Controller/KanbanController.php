<?php

namespace Drupal\views_kanban\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\field_states\StatesTransitionInterface;
use Drupal\notifications_widget\Services\NotificationsWidgetServiceInterface;
use Drupal\notify_widget\NotifyWidgetApi;
use Drupal\pwa_firebase\FirebaseInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowState;
use Drupal\views\Views;
use Drupal\workflows\State;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class Kanban Controller.
 */
class KanbanController extends ControllerBase {

  /**
   * The firebase service.
   *
   * @var \Drupal\pwa_firebase\FirebaseInterface
   */
  protected ?FirebaseInterface $firebase;

  /**
   * The notifyWidget service.
   *
   * @var \Drupal\notify_widget\NotifyWidgetApi
   */
  protected ?NotifyWidgetApi $notifyWidget;

  /**
   * The notification Widget service.
   *
   * @var \Drupal\notifications_widget\Services\NotificationsWidgetServiceInterface
   */
  protected ?NotificationsWidgetServiceInterface $notificationsWidget;

  /**
   * The state and transition service.
   *
   * @var \Drupal\field_states\StatesTransitionInterface|null
   */
  protected ?StatesTransitionInterface $statesTransition;

  /**
   * {@inheritDoc}
   */
  public function __construct(protected DateFormatter $dateFormatter, protected MailManagerInterface $mailManager, protected RendererInterface $renderer, protected FileUrlGeneratorInterface $fileUrlGenerator, FirebaseInterface $firebase = NULL, NotifyWidgetApi $notify_widget = NULL, NotificationsWidgetServiceInterface $notifications_widget = NULL, StatesTransitionInterface $states_transition = NULL) {
    $this->firebase = $firebase;
    $this->notifyWidget = $notify_widget;
    $this->notificationsWidget = $notifications_widget;
    $this->statesTransition = $states_transition;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('date.formatter'),
      $container->get('plugin.manager.mail'),
      $container->get('renderer'),
      $container->get('file_url_generator'),
      $container->has('pwa_firebase.send') ? $container->get('pwa_firebase.send') : NULL,
      $container->has('notify_widget.api') ? $container->get('notify_widget.api') : NULL,
      $container->has('notifications_widget.logger') ? $container->get('notifications_widget.logger') : NULL,
      $container->has('field_states.transitions') ? $container->get('field_states.transitions') : NULL,
    );
  }

  /**
   * Update state.
   *
   * @param string $view_id
   *   View id.
   * @param string $display_id
   *   Display id.
   * @param int $entity_id
   *   Entity id.
   * @param string $state_value
   *   State value.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Return json.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateState($view_id, $display_id, $entity_id = 0, $state_value = '') {
    $message = NULL;
    $data = [
      'success' => FALSE,
      'message' => $message,
    ];
    if (!$state_value && !is_numeric($entity_id)) {
      return new JsonResponse($data);
    }
    $view = Views::getView($view_id);
    $handler = $view->getHandler($display_id, 'filter', 'type');
    $entity_type = !empty($handler['entity_type']) ? $handler['entity_type'] : 'user';
    $style_plugin = $view->display_handler->getPlugin('style');
    $status_field = $style_plugin->options["status_field"];
    $entity = $this->entityTypeManager()->getStorage($entity_type)
      ->load($entity_id);
    $message = $this->getHistoryMessage($entity, $status_field, $state_value);
    $statusName = $this->getStatusName($entity, $status_field, $state_value);
    // Support field states transitions.
    $fieldDefinition = $entity->getFieldDefinition($status_field);
    if ($fieldDefinition && $fieldDefinition->getType() == 'list_states') {
      $this->statesTransition
        ->setTransitionByField($entity, $status_field)
        ->transition($statusName["old"], $statusName["new"])
        ->applyTransition($entity);
    }
    if (!array_key_exists($state_value, $this->getAllowedValues($entity, $status_field))) {
      $data['message'] = $this->t(
        'New state @state is not a valid', ['@state' => $state_value]
      );
      return new JsonResponse($data);
    }
    $extractStatus = explode(':', $status_field);
    if (!empty($extractStatus[1])) {
      $status_field = $extractStatus[0];
    }
    // Save new status.
    $entity->set($status_field, $state_value);

    // Save history.
    $historyFieldName = $style_plugin->options["history_field"];
    if (!empty($historyFieldName) && $entity->hasField($historyFieldName)) {
      $historyType = $entity->get($historyFieldName)->getFieldDefinition()->getType();
      $historyValue = $this->dateFormatter->format(strtotime('now')) . ' ' . $message;
      if (in_array($historyType, ['double_field', 'triples_field'])) {
        $historyValue = [
          'first' => date('Y-m-d\TH:i:s'),
          'second' => $message,
        ];
        if ($historyType == 'triples_field') {
          $historyValue['second'] = $this->currentUser()->getDisplayName();
          $historyValue['third'] = $statusName["old"];
        }
      }
      $entity->$historyFieldName->appendItem($historyValue);
    }
    $this->moduleHandler()->alter('kanban_change_status', $entity, $view);
    $entity->save();
    $url = Url::fromRoute(implode('.', [
      'view',
      $view_id,
      $view->current_display,
    ]));
    // Send email, notification to assignor.
    if (!empty($style_plugin->options["send_email"]) ||
      !empty($style_plugin->options["send_notification"])) {
      $user = $this->entityTypeManager()->getStorage('user')
        ->load($this->currentUser()->id());
      $email = $user->getEmail();
      $name = $user->getDisplayName();
      $author_initial = implode('', array_map(function ($v) {
        return $v[0];
      },
        explode(' ', $name)));
      $assignValues[$uid = $entity->getOwnerID()] = $uid;
      $assign_field = $style_plugin->options["assign_field"];
      if (!empty($assign_field) && $entity->hasField($assign_field)) {
        $assignors = $entity->get($assign_field)->getValue();
        foreach ($assignors as $assignor) {
          $assignValues[$assignor['target_id']] = $assignor['target_id'];
        }
      }
      $author_avatar = '';
      if (!empty($user->user_picture) && !$user->user_picture->isEmpty()) {
        $avatarUri = $user->user_picture->entity->getFileUri();
        $thumbnail = $this->entityTypeManager()->getStorage('image_style')
          ->load('thumbnail');
        $thumbnailAvatar = $thumbnail->buildUri($avatarUri);
        if (!file_exists($thumbnailAvatar)) {
          $thumbnail->createDerivative($avatarUri, $thumbnailAvatar);
        }
        $author_avatar = $this->fileUrlGenerator->generateAbsoluteString($thumbnailAvatar);
      }
      $key = $view_id . '-' . $view->current_display;
      $link = $url->setOption('absolute', TRUE)
        ->setOption('query', ['kanbanTicket' => $entity_id])
        ->toString();
      foreach ($assignValues as $uid => $userID) {
        // Send Email.
        if (!empty($style_plugin->options["send_email"])) {
          $assignor = $this->entityTypeManager()->getStorage('user')->load($uid);
          if (empty($assignor)) {
            continue;
          }
          $to = $assignor->getEmail();

          // Set up email template.
          $body_data = [
            '#theme' => 'views_email_kanban',
            '#message' => $message,
            '#author_initial' => mb_strtoupper($author_initial),
            '#author_avatar' => $author_avatar,
            '#type' => $statusName['new'],
            '#author_name' => $name,
            '#title' => $this->t("Change status") . " - " . $entity->label(),
            '#assignator' => $assignor->getDisplayName(),
            '#btn_text' => $this->t('View'),
            '#link' => $link,
          ];
          $messageSend = [
            'id' => $key,
            'headers' => [
              'Content-type' => 'text/html; charset=UTF-8; format=flowed; delsp=yes',
              'Reply-to' => $name . '<' . $email . '>',
              'Return-Path' => $email,
              'Content-Transfer-Encoding' => '8Bit',
              'MIME-Version' => '1.0',
            ],
            'subject' => $view->getTitle() . ' ' . $this->config('system.site')->get('name'),
            'to' => $to,
            'body' => $this->renderer->render($body_data),
          ];
          $this->mailManager->getInstance([
            'module' => 'views_kanban',
            'key' => $key,
          ])->mail($messageSend);
        }
        // Send notification.
        if (!empty($style_plugin->options["send_notification"])) {
          $this->sendNotification($uid, $view->getTitle(), $message, $url, $entity);
        }
      }
    }
    $data = [
      'success' => TRUE,
      'message' => $message,
    ];
    return new JsonResponse($data);
  }

  /**
   * Send notification.
   *
   * {@inheritDoc}
   */
  public function sendNotification($uid, $title, $message, $url, $entity = NULL) {

    if ($this->moduleHandler()->moduleExists('notify_widget')) {
      $this->notifyWidget->send(
        'views_kanban',
        'warning',
        $title,
        $message,
        $uid,
        $this->fileUrlGenerator->transformRelative($url->toString()),
      );
    }
    if ($this->moduleHandler()->moduleExists('notificationswidget')) {
      $message = [
        'id' => $entity->id(),
        'bundle' => $entity->bundle(),
        'content' => $message,
        'content_link' => $url->toString(),
      ];
      $this->notificationsWidget->logNotification(
        $message,
        'create',
        $entity,
        $uid
      );
    }
    if ($this->moduleHandler()->moduleExists('pwa_firebase')) {
      $this->firebase->sendMessageToUser($uid, $title, $message, $url->toString());
    }

  }

  /**
   * Get the value allowed in the state field.
   *
   * @param object $entity
   *   Entity.
   * @param string $fieldName
   *   Field name.
   *
   * @return array
   *   Allow values.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getAllowedValues($entity, $fieldName) {
    $extractStatus = explode(':', $fieldName);
    if (!empty($extractStatus[1])) {
      // $fieldName = $extractStatus[0];
      $workflow_id = $extractStatus[1];
      $workflow = $this->entityTypeManager()->getStorage('workflow')
        ->load($workflow_id);
      return array_map([
        State::class,
        'labelCallback',
      ], $workflow->getTypePlugin()->getStates());
    }
    $statusFieldDefinition = $entity->get($fieldName)->getFieldDefinition();
    $statusFieldValues = $statusFieldDefinition->getSettings();
    $allowed_values = [];
    if (!empty($statusFieldValues["allowed_values"])) {
      $allowed_values = $statusFieldValues["allowed_values"];
    }
    elseif (!empty($statusFieldValues["workflow"])) {
      // phpcs:ignore
      $workflow_manager = \Drupal::service('plugin.manager.workflow');
      $workflow = $workflow_manager->createInstance($statusFieldValues["workflow"]);
      $states = $workflow->getStates();
      $allowed_values = array_map(function (WorkflowState $state) {
        return $state->getLabel();
      }, $states);
    }
    if (!empty($statusFieldValues["target_type"])) {
      $vid = current($statusFieldValues["handler_settings"]["target_bundles"]);
      $loadTermStatus = $this->entityTypeManager()->getStorage('taxonomy_term')
        ->loadTree($vid);
      foreach ($loadTermStatus as $term) {
        $allowed_values[$term->tid] = $term->name;
      }
    }
    return $allowed_values;
  }

  /**
   * Get message for log.
   *
   * @param object $entity
   *   Entity.
   * @param string $status_field
   *   Status field.
   * @param string $newStatus
   *   New status.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Array text history status.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getHistoryMessage($entity, $status_field, $newStatus) {
    $statusName = $this->getStatusName($entity, $status_field, $newStatus);
    return $this->t('@user change from @old to @new', [
      '@user' => $this->currentUser()->getDisplayName(),
      '@old' => $statusName['old'],
      '@new' => $statusName['new'],
    ]);
  }

  /**
   * Get status name.
   */
  protected function getStatusName($entity, $status_field, $newStatus) {
    $statusList = $this->getAllowedValues($entity, $status_field);
    $extractStatus = explode(':', $status_field);
    if (!empty($extractStatus[1])) {
      $status_field = $extractStatus[0];
    }
    $currentStatus = $entity->get($status_field)->getString();
    if (is_array($currentStatus)) {
      $currentStatus = current($currentStatus);
    }
    if (!empty($statusList[$currentStatus])) {
      $currentStatus = $statusList[$currentStatus];
    }
    if (!empty($statusList[$newStatus])) {
      $newStatus = $statusList[$newStatus];
    }
    return [
      'old' => $currentStatus,
      'new' => $newStatus,
    ];
  }

}
