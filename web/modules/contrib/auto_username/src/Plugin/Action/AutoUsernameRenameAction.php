<?php

namespace Drupal\auto_username\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Auto username rename bulk action.
 *
 * @Action(
 *   id = "auto_username_rename_action",
 *   label = @Translation("Generate username(s) using the 'Auto Username' module"),
 *   type = "user",
 * )
 */
class AutoUsernameRenameAction extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($account = NULL) {
    // Rename the given user:
    if (!empty($account)) {
      auto_username_user_insert($account);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\user\UserInterface $object */
    $access = $object->status->access('edit', $account, TRUE)
      ->andIf($object->access('update', $account, TRUE));

    return $return_as_object ? $access : $access->isAllowed();
  }

}
