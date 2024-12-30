<?php

namespace Drupal\auto_username;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Utility\Token;

/**
 * Provides utility functions for handling auto-generated usernames.
 */
class AutoUsernameUtilities {
  use StringTranslationTrait;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected EntityFieldManagerInterface $entityFieldManager;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected Token $tokenService;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $databaseConnection;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $cache;

  /**
   * Constructs the AutoUsernameUtilities service.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Utility\Token $token_service
   *   The token service.
   * @param \Drupal\Core\Database\Connection $database_connection
   *   The database connection service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   */
  public function __construct(
    EntityFieldManagerInterface $entity_field_manager,
    LanguageManagerInterface $language_manager,
    ModuleHandlerInterface $module_handler,
    ConfigFactoryInterface $config_factory,
    Token $token_service,
    Connection $database_connection,
    CacheBackendInterface $cache,
    TranslationInterface $string_translation,
  ) {
    $this->entityFieldManager = $entity_field_manager;
    $this->languageManager = $language_manager;
    $this->cache = $cache;
    $this->moduleHandler = $module_handler;
    $this->config = $config_factory->get('auto_username.settings');
    $this->tokenService = $token_service;
    $this->databaseConnection = $database_connection;
    $this->cache = $cache;
    $this->stringTranslation = $string_translation;
  }

  /**
   * Fetch the maximum length of the {users}.name field from the schema.
   *
   * @return array
   *   An integer of the maximum username length allowed by the database.
   */
  public function autoUsernameGetSchemaNameMaxlength() {
    $maxlength = &drupal_static(__FUNCTION__);
    if (!isset($maxlength)) {
      $field_definitions = $this->entityFieldManager->getFieldDefinitions('user', 'user');
      $name_settings = $field_definitions['name']->getItemDefinition()->getSettings();
      $maxlength = $name_settings['max_length'] ?? 255;
    }
    return $maxlength;
  }

  /**
   * Return an array of arrays for punctuation values.
   *
   * Returns an array of arrays for punctuation values keyed by a name,
   * including the value and a textual description.
   * Can and should be expanded to include "all" non text punctuation values.
   *
   * @return array
   *   An array of arrays for punctuation values keyed by a name, including the
   *   value and a textual description.
   */
  public function autoUsernamePunctuationChars() {
    $punctuation = &drupal_static(__FUNCTION__);
    $language = $this->languageManager->getCurrentLanguage()->getId();

    if (!isset($punctuation)) {
      $cid = 'auto_username:punctuation:' . $language;
      if ($cache = $this->cache->get($cid)) {
        $punctuation = $cache->data;
      }
      else {
        $punctuation                      = [];
        $punctuation['double_quotes']     = [
          'value' => '"',
          'name' => $this->t('Double quotation marks'),
        ];
        $punctuation['quotes']            = [
          'value' => '\'',
          'name' => $this->t("Single quotation marks (apostrophe)"),
        ];
        $punctuation['backtick']          = [
          'value' => '`',
          'name' => $this->t('Back tick'),
        ];
        $punctuation['comma']             = [
          'value' => ',',
          'name' => $this->t('Comma'),
        ];
        $punctuation['period']            = [
          'value' => '.',
          'name' => $this->t('Period'),
        ];
        $punctuation['hyphen']            = [
          'value' => '-',
          'name' => $this->t('Hyphen'),
        ];
        $punctuation['underscore']        = [
          'value' => '_',
          'name' => $this->t('Underscore'),
        ];
        $punctuation['colon']             = [
          'value' => ':',
          'name' => $this->t('Colon'),
        ];
        $punctuation['semicolon']         = [
          'value' => ';',
          'name' => $this->t('Semicolon'),
        ];
        $punctuation['pipe']              = [
          'value' => '|',
          'name' => $this->t('Vertical bar (pipe)'),
        ];
        $punctuation['left_curly']        = [
          'value' => '{',
          'name' => $this->t('Left curly bracket'),
        ];
        $punctuation['left_square']       = [
          'value' => '[',
          'name' => $this->t('Left square bracket'),
        ];
        $punctuation['right_curly']       = [
          'value' => '}',
          'name' => $this->t('Right curly bracket'),
        ];
        $punctuation['right_square']      = [
          'value' => ']',
          'name' => $this->t('Right square bracket'),
        ];
        $punctuation['plus']              = [
          'value' => '+',
          'name' => $this->t('Plus sign'),
        ];
        $punctuation['equal']             = [
          'value' => '=',
          'name' => $this->t('Equal sign'),
        ];
        $punctuation['asterisk']          = [
          'value' => '*',
          'name' => $this->t('Asterisk'),
        ];
        $punctuation['ampersand']         = [
          'value' => '&',
          'name' => $this->t('Ampersand'),
        ];
        $punctuation['percent']           = [
          'value' => '%',
          'name' => $this->t('Percent sign'),
        ];
        $punctuation['caret']             = [
          'value' => '^',
          'name' => $this->t('Caret'),
        ];
        $punctuation['dollar']            = [
          'value' => '$',
          'name' => $this->t('Dollar sign'),
        ];
        $punctuation['hash']              = [
          'value' => '#',
          'name' => $this->t('Number sign (pound sign, hash)'),
        ];
        $punctuation['at']                = [
          'value' => '@',
          'name' => $this->t('At sign'),
        ];
        $punctuation['exclamation']       = [
          'value' => '!',
          'name' => $this->t('Exclamation mark'),
        ];
        $punctuation['tilde']             = [
          'value' => '~',
          'name' => $this->t('Tilde'),
        ];
        $punctuation['left_parenthesis']  = [
          'value' => '(',
          'name' => $this->t('Left parenthesis'),
        ];
        $punctuation['right_parenthesis'] = [
          'value' => ')',
          'name' => $this->t('Right parenthesis'),
        ];
        $punctuation['question_mark']     = [
          'value' => '?',
          'name' => $this->t('Question mark'),
        ];
        $punctuation['less_than']         = [
          'value' => '<',
          'name' => $this->t('Less-than sign'),
        ];
        $punctuation['greater_than']      = [
          'value' => '>',
          'name' => $this->t('Greater-than sign'),
        ];
        $punctuation['slash']             = [
          'value' => '/',
          'name' => $this->t('Slash'),
        ];
        $punctuation['back_slash']        = [
          'value' => '\\',
          'name' => $this->t('Backslash'),
        ];

        // Allow modules to alter the punctuation list and cache the result.
        $this->moduleHandler->alter('autoUsernamePunctuationChars', $punctuation);
        $this->cache->set($cid, $punctuation);
      }
    }

    return $punctuation;
  }

  /**
   * Process an account, return its new username according to current pattern.
   *
   * @param object $account
   *   The user object to process.
   *
   * @return string
   *   The new name for the user object.
   */
  public function autoUsernamePatternprocessor($account) {
    $output = '';
    $pattern = $this->config->get('aun_pattern');

    if (trim($pattern)) {
      $pattern_array = explode('\n', trim($pattern));
      // Replace any tokens in the pattern. Uses callback option to clean
      // replacements. No sanitization.
      $output = $this->tokenService->replace($pattern, ['user' => $account], [
        'clear' => TRUE,
        'callback' => 'auto_username_clean_token_values',
      ]);
      // Check if the token replacement has not actually replaced any values. If
      // that is the case, then stop because we should not generate a name.
      // @see token_scan()
      $pattern_tokens_removed = preg_replace('/\[[^\s\]:]*:[^\s\]]*\]/', '', implode('\n', $pattern_array));
      if ($output === $pattern_tokens_removed) {
        return '';
      }
      if ($this->config->get('aun_php')) {
        $output = $this->autoUsernameEval($output, $account);
      }
    }
    return trim($output);
  }

  /**
   * Evaluate php code and pass $account to it.
   */
  public function autoUsernameEval($code, $account) {
    ob_start();
    //phpcs:ignore Drupal.Functions.DiscouragedFunctions.Discouraged
    print eval('?>' . $code);
    $output = ob_get_contents();
    ob_end_clean();
    return $output;
  }

  /**
   * Generating Username value.
   *
   * Work out what the new username could be, calling api hooks where
   * applicable, and adding a number suffix if necessary.
   */
  public function autoUsernameGenerateUsername($account) {
    // Other modules may implement hook_auto_username_name($edit, $account) to
    // generate a username (return a string to be used as the username, NULL to
    // have auto_username generate it).
    $names = $this->moduleHandler->invokeAll('auto_username_name', [$account]);

    // Remove any empty entries.
    $names = array_filter($names);

    if (empty($names)) {
      // Default implementation of name generation.
      $new_name = $this->autoUsernamePatternprocessor($account);
    }
    else {
      // One would expect a single implementation of the hook, but if there
      // are multiples out there use the last one.
      $new_name = array_pop($names);
    }

    // If no new name was found, then either the hook hasn't been implemented,
    // or the aun_pattern hasn't been set yet. Therefore leave the username as
    // it is.
    if (empty($new_name)) {
      return $account->getDisplayName();
    }

    // Lets check if our name is used somewhere else, and append _1 if it is
    // eg:(chris_123). We do this regardless of whether hook has run, as we
    // can't assume the hook implementation will do this sanity check.
    $original_new_name = $new_name;
    $i = 0;
    do {
      $new_name = empty($i) ? $original_new_name : $original_new_name . '_' . $i;
      $found = $this->databaseConnection
        ->select('users_field_data', 'u')
        ->fields('u')
        ->condition('uid', $account->id(), '!=')
        ->condition('name', $new_name)
        ->execute()
        ->fetchAll();
      $i++;
    } while (!empty($found));

    return $new_name;
  }

  /**
   * Clean up a string segment to be used in a username.
   *
   * Performs the following possible alterations:
   * - Remove all HTML tags.
   * - Process the string through the transliteration module.
   * - Replace or remove punctuation with the separator character.
   * - Remove back-slashes.
   * - Replace non-ascii and non-numeric characters with the separator.
   * - Remove common words.
   * - Replace whitespace with the separator character.
   * - Trim duplicate, leading, and trailing separators.
   * - Convert to lower-case.
   * - Shorten to a desired length and logical position based on word
   * boundaries.
   *
   * @param string $string
   *   A string to clean.
   *
   * @return mixed|string
   *   The cleaned string.
   */
  public function autoUsernameCleanstring($string) {
    // Use the advanced drupal_static()pattern, since this is called very often.
    static $drupal_static_fast;
    if (!isset($drupal_static_fast)) {
      $drupal_static_fast['cache'] = &drupal_static(__FUNCTION__);
    }
    $cache = &$drupal_static_fast['cache'];

    // Generate and cache variables used in this function so that on the second
    // call to autoUsernameCleanstring() we focus on processing.
    if (!isset($cache)) {
      $cache = [
        'separator' => $this->config->get('aun_separator'),
        'transliterate' => $this->config->get('aun_transliterate') && $this->moduleHandler->moduleExists('transliteration'),
        'punctuation' => [],
        'reduce_ascii' => (bool) $this->config->get('aun_reduce_ascii'),
        'ignore_words_regex' => FALSE,
        'replace_whitespace' => (bool) $this->config->get('aun_replace_whitespace'),
        'lowercase' => (bool) $this->config->get('aun_case'),
        'maxlength' => min($this->config->get('aun_max_component_length'), self::autoUsernameGetSchemaNameMaxlength()),
      ];

      // Generate and cache the punctuation replacements for strtr().
      $punctuation = $this->autoUsernamePunctuationChars();
      foreach ($punctuation as $name => $details) {
        $action = $this->config->get('aun_punctuation_' . $name);
        switch ($action) {
          case AutoUsernamePunctuationOptions::REMOVE:
            $cache['punctuation'][$details['value']] = '';
            break;

          case AutoUsernamePunctuationOptions::REPLACE:
            $cache['punctuation'][$details['value']] = $cache['separator'];
            break;

          case AutoUsernamePunctuationOptions::DO_NOTHING:
            // Literally do nothing.
            break;
        }
      }

      // Generate and cache the ignored words regular expression.
      $ignore_words = $this->config->get('aun_ignore_words');
      $ignore_words_regex = preg_replace(
        ['/^[,\s]+|[,\s]+$/', '/[,\s]+/'],
        ['', '\b|\b'],
        $ignore_words
      );
      if ($ignore_words_regex) {
        $cache['ignore_words_regex'] = '\b' . $ignore_words_regex . '\b';
        if (function_exists('mb_eregi_replace')) {
          $cache['ignore_words_callback'] = 'mb_eregi_replace';
        }
        else {
          $cache['ignore_words_callback'] = 'preg_replace';
          $cache['ignore_words_regex'] = '/' . $cache['ignore_words_regex'] . '/i';
        }
      }
    }

    // Empty strings do not need any processing.
    if ($string === '' || $string === NULL) {
      return '';
    }

    // Remove all HTML tags from the string.
    $output = strip_tags(Html::decodeEntities($string));

    // Replace or drop punctuation based on user settings.
    $output = strtr($output, $cache['punctuation']);

    // Reduce strings to letters and numbers.
    if ($cache['reduce_ascii']) {
      $output = preg_replace('/[^a-zA-Z0-9\/]+/', $cache['separator'], $output);
    }

    // Get rid of words that are on the ignore list.
    if ($cache['ignore_words_regex']) {
      $words_removed = $cache['ignore_words_callback']($cache['ignore_words_regex'], '', $output);
      if (mb_strlen(trim($words_removed)) > 0) {
        $output = $words_removed;
      }
    }

    // Replace whitespace with the separator.
    if ($cache['replace_whitespace']) {
      $output = preg_replace('/\s+/', $cache['separator'], $output);
    }

    // Trim duplicates and remove trailing and leading separators.
    $output = $this->autoUsernameCleanSeparators($output, $cache['separator']);

    // Optionally convert to lower case.
    if ($cache['lowercase']) {
      $output = mb_strtolower($output);
    }

    // Shorten to a logical place based on word boundaries.
    $output = Unicode::truncate($output, $cache['maxlength'], TRUE);
    return $output;
  }

  /**
   * Trims duplicate, leading, and trailing separators from a string.
   *
   * @param string $string
   *   The string to clean separators from.
   * @param string $separator
   *   The separator to use when cleaning.
   *
   * @return mixed
   *   The cleaned version of the string.
   *
   * @see autoUsernameCleanSeparators()
   */
  public function autoUsernameCleanSeparators($string, $separator = NULL) {
    static $default_separator;

    if (!isset($separator)) {
      if (!isset($default_separator)) {
        $default_separator = $this->config->get('aun_separator');
      }
      $separator = $default_separator;
    }

    $output = $string;

    // Clean duplicate or trailing separators.
    if (strlen($separator)) {
      // Escape the separator.
      $separator_pattern = preg_quote($separator, '/');

      // Trim any leading or trailing separators.
      $output = preg_replace("/^$separator_pattern+|$separator_pattern+$/", '', $output);

      // Replace trailing separators around slashes.
      if ($separator !== '/') {
        $output = preg_replace("/$separator_pattern+\/|\/$separator_pattern+/", "/", $output);
      }

      // Replace multiple separators with a single one.
      $output = preg_replace("/$separator_pattern+/", $separator, $output);
    }

    return $output;
  }

}
