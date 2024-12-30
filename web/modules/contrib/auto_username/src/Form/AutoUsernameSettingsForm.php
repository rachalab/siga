<?php

namespace Drupal\auto_username\Form;

use Drupal\auto_username\AutoUsernameOptions;
use Drupal\auto_username\AutoUsernamePunctuationOptions;
use Drupal\auto_username\AutoUsernameUtilities;
use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for configuring auto-generated usernames.
 *
 * @package Drupal\auto_username\Form
 */
class AutoUsernameSettingsForm extends ConfigFormBase {

  /**
   * The current account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Drupal\Core\Extension\ModuleHandler definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * The AutoUsernameUtilities service.
   *
   * @var \Drupal\auto_username\AutoUsernameUtilities
   */
  protected $autoUsernameUtilities;

  /**
   * Class constructor.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    AccountInterface $account,
    ModuleHandlerInterface $module_handler,
    AutoUsernameUtilities $auto_username_utilities,
  ) {
    parent::__construct($config_factory);
    $this->account = $account;
    $this->moduleHandler = $module_handler;
    $this->autoUsernameUtilities = $auto_username_utilities;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
    // Load the service required to construct this class.
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('module_handler'),
      $container->get('auto_username.utilities')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'auto_username_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'auto_username.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('auto_username.settings');

    $form = [];

    $form['aun_general_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('General settings'),
      '#open' => TRUE,
    ];

    $form['aun_general_settings']['aun_pattern'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Pattern for username'),
      '#description' => $this->t('Enter the pattern for usernames.  You may use any of the tokens listed below.'),
      '#default_value' => $config->get('aun_pattern'),
    ];

    $form['aun_general_settings']['token_help'] = [
      '#title' => $this->t('Replacement patterns'),
      '#type' => 'details',
      '#open' => TRUE,
      '#description' => $this->t('Note that fields that are not present in the user registration form will get replaced with an empty string when the account is created.  That is rarely desirable.'),
    ];

    $form['aun_general_settings']['token_help']['help'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['user'],
      '#global_types' => NULL,
    ];

    // Other module configuration.
    $form['aun_other_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Other settings'),
      '#open' => TRUE,
      '#collapsed' => FALSE,
    ];

    if ($this->account->hasPermission('use PHP for username patterns')) {
      $form['aun_other_settings']['aun_php'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Evaluate PHP in pattern.'),
        '#description' => $this->t('If this box is checked, the pattern will be executed as PHP code after token substitution has taken place.  You must surround the PHP code in &lt;?php and ?&gt; tags.  Token replacement will take place before PHP code execution.  Note that $account is available and can be used by your code.'),
        '#default_value' => $config->get('aun_php'),
      ];
    }

    $form['aun_other_settings']['aun_update_on_edit'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Update on user edit'),
      '#description' => $this->t("If this box is checked, the username will be reset any time the user's profile is updated.  That can help to enforce a username format, but may result in a user's login name changing unexpectedly.  It is best used in conjunction with an alternative login mechanism, such as OpenID or an email address."),
      '#default_value' => $config->get('aun_update_on_edit'),
    ];

    $form['aun_other_settings']['aun_separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Separator'),
      '#size' => 1,
      '#maxlength' => 1,
      '#default_value' => $config->get('aun_separator'),
      '#description' => $this->t('Character used to separate words in titles. This will replace any spaces and punctuation characters.'),
    ];

    $form['aun_other_settings']['aun_case'] = [
      '#type' => 'radios',
      '#title' => $this->t('Character case'),
      '#default_value' => $config->get('aun_case'),
      '#options' => [
        AutoUsernameOptions::CASE_LEAVE_AS_IS => $this->t('Leave case the same as source token values.'),
        AutoUsernameOptions::CASE_LOWER => $this->t('Change to lower case'),
      ],
    ];

    $max_length = $this->autoUsernameUtilities->autoUsernameGetSchemaNameMaxlength();

    $form['aun_other_settings']['aun_max_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum alias length'),
      '#size' => 3,
      '#default_value' => $config->get('aun_max_length'),
      '#min' => 1,
      '#max' => $max_length,
      '#description' => $this->t('Maximum length of aliases to generate. @max is the maximum possible length.', ['@max' => $max_length]),
    ];
    $form['aun_other_settings']['aun_max_component_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum component length'),
      '#size' => 3,
      '#default_value' => $config->get('aun_max_component_length'),
      '#min' => 1,
      '#max' => $max_length,
      '#description' => $this->t('Maximum text length of any component in the username (e.g., [user:mail]). @max is the maximum possible length.', ['@max' => $max_length]),
    ];

    $form['aun_other_settings']['aun_transliterate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Transliterate prior to creating username'),
      '#default_value' => $config->get('aun_transliterate') && $this->moduleHandler->moduleExists('transliteration'),
      '#description' => $this->t('When a pattern includes certain characters (such as those with accents) should auto_username attempt to transliterate them into the ASCII-96 alphabet? Transliteration is handled by the Transliteration module.'),
      '#access' => $this->moduleHandler->moduleExists('transliteration'),
    ];

    $form['aun_other_settings']['aun_reduce_ascii'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Reduce strings to letters and numbers'),
      '#default_value' => $config->get('aun_reduce_ascii'),
      '#description' => $this->t('Filters the new username to only letters and numbers found in the ASCII-96 set.'),
    ];

    $form['aun_other_settings']['aun_replace_whitespace'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Replace whitespace with separator.'),
      '#default_value' => $config->get('aun_replace_whitespace'),
      '#description' => $this->t('Replace all whitespace in tokens with the separator character specified below.  Note that this will affect the tokens themselves, not the pattern specified above.  To avoid spaces entirely, ensure that the pattern above contains no spaces.'),
    ];

    $form['aun_other_settings']['aun_ignore_words'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Strings to Remove'),
      '#default_value' => $config->get('aun_ignore_words'),
      '#description' => $this->t('Words to strip out of the username, separated by commas. Do not use this to remove punctuation.'),
      '#wysiwyg' => FALSE,
    ];

    $form['punctuation'] = [
      '#type' => 'details',
      '#title' => $this->t('Punctuation'),
      '#open' => TRUE,
    ];

    $punctuation = $this->autoUsernameUtilities->autoUsernamePunctuationChars();
    foreach ($punctuation as $name => $details) {
      $details['default'] = AutoUsernamePunctuationOptions::REMOVE;
      if ($details['value'] == $config->get('aun_separator')) {
        $details['default'] = AutoUsernamePunctuationOptions::REPLACE;
      }
      $form['punctuation']['aun_punctuation_' . $name] = [
        '#type' => 'select',
        '#title' => $details['name'] . ' (<code>' . Html::escape($details['value']) . '</code>)',
        '#default_value' => $config->get('aun_punctuation_' . $name),
        '#options' => [
          AutoUsernamePunctuationOptions::REMOVE => $this->t('Remove'),
          AutoUsernamePunctuationOptions::REPLACE => $this->t('Replace by separator'),
          AutoUsernamePunctuationOptions::DO_NOTHING => $this->t('No action (do not replace)'),
        ],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Validate auto_username_settings_form form submissions.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Perform a basic check for HTML characters in the strings to remove field.
    if (strip_tags($form_state->getValue('aun_ignore_words')) != $form_state->getValue('aun_ignore_words')) {
      $form_state->setErrorByName('aun_ignore_words', $this->t('The <em>Strings to remove</em> field must not contain HTML. Make sure to disable any WYSIWYG editors for this field.'));
    }

    // Validate that the separator is not set to be removed.
    // This isn't really all that bad so warn, but still allow them to save the
    // value.
    $separator = $form_state->getValue('aun_separator');
    $punctuation = $this->autoUsernameUtilities->autoUsernamePunctuationChars();
    foreach ($punctuation as $name => $details) {
      if ($details['value'] == $separator) {
        $action = $form_state->getValue('aun_punctuation_' . $name);
        if ($action == AutoUsernamePunctuationOptions::REMOVE) {
          $this->messenger()->addError($this->t('You have configured the @name to be the separator and to be removed when encountered in strings. You should probably set the action for @name to be "replace by separator".', ['@name' => $details['name']]));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('auto_username.settings');
    // Set values in variables.
    $config->set('aun_pattern', $form_state->getValues()['aun_pattern']);
    $config->set('aun_php', $form_state->getValues()['aun_php']);
    $config->set('aun_update_on_edit', $form_state->getValues()['aun_update_on_edit']);
    $config->set('aun_separator', $form_state->getValues()['aun_separator']);
    $config->set('aun_case', $form_state->getValues()['aun_case']);
    $config->set('aun_max_length', $form_state->getValues()['aun_max_length']);
    $config->set('aun_max_component_length', $form_state->getValues()['aun_max_component_length']);
    $config->set('aun_transliterate', $form_state->getValues()['aun_transliterate']);
    $config->set('aun_reduce_ascii', $form_state->getValues()['aun_reduce_ascii']);
    $config->set('aun_replace_whitespace', $form_state->getValues()['aun_replace_whitespace']);
    $config->set('aun_ignore_words', $form_state->getValues()['aun_ignore_words']);
    $punctuation = $this->autoUsernameUtilities->autoUsernamePunctuationChars();
    foreach ($punctuation as $name => $details) {
      $config->set('aun_punctuation_' . $name, $form_state->getValues()['aun_punctuation_' . $name]);
    }
    $config->save();
  }

}
