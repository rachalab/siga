<?php

namespace Drupal\pf_email\Form;

use Drupal\push_framework\Form\Settings as FrameworkSettings;

/**
 * Configure Push Framework Email settings.
 */
class Settings extends FrameworkSettings {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'pf_email_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['pf_email.settings'];
  }

}
