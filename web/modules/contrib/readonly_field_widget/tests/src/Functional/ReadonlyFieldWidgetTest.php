<?php

namespace Drupal\Tests\readonly_field_widget\Functional;

use Behat\Mink\Element\NodeElement;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Tests Readonly Field Widget basic behaviour.
 *
 * @group readonly_field_widget
 */
class ReadonlyFieldWidgetTest extends WebDriverTestBase {


  /**
   * {@inheritdoc}
   */
  protected static $modules = ['readonly_field_widget_test'];

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = TRUE;

  /**
   * An admin user for testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $admin;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();

    $this->createContentType(['name' => 'page', 'type' => 'page']);
    $this->createContentType(['name' => 'article', 'type' => 'article']);

    $tags_vocab = Vocabulary::create(['vid' => 'tags', 'name' => 'tags']);
    $tags_vocab->save();

    $this->admin = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($this->admin);

    $page = $this->getSession()->getPage();

    // Add an article reference field.
    $this->drupalGet('/admin/structure/types/manage/page/fields/add-field');
    if (version_compare(\Drupal::VERSION, '10', '<')) {
      $page->selectFieldOption('Add a new field', 'Content');
    }
    else {
      $page->fillField('new_storage_type', 'reference');
      if (version_compare(\Drupal::VERSION, '10.3', '>=')) {
        $page->pressButton('Continue');
      }
      else {
        $this->assertSession()->waitForElementVisible('css', '#field_ui\:entity_reference\:node');
      }
      $page->fillField('field_ui:entity_reference:node', 'field_ui:entity_reference:node');
    }
    $page->fillField('Label', 'article reference');
    $this->assertSession()->waitForElementVisible('css', '.machine-name-value');
    if (version_compare(\Drupal::VERSION, '10', '>=')) {
      $page->pressButton('Continue');
    }
    else {
      $page->pressButton('Save and continue');
      $page->pressButton('Save field settings');
    }
    $page->checkField('article');
    $this->assertSession()->assertWaitOnAjaxRequest(1000);
    $page->pressButton('Save settings');

    $this->drupalGet('/admin/structure/types/manage/page/form-display');
    $page->fillField('Plugin for article reference', 'readonly_field_widget');
    $this->assertSession()->assertWaitOnAjaxRequest(1000);
    $this->assertSession()->waitForElementVisible('named', [
      'button',
      'field_article_reference_settings_edit',
    ])->press();
    $this->assertSession()->waitForElementVisible('named', ['select', 'Format']);
    $page->selectFieldOption('Format', 'Rendered entity');
    $this->assertSession()->assertWaitOnAjaxRequest(1000);
    $page->checkField('Show Description');
    $page->pressButton('Update');
    $this->assertSession()->assertWaitOnAjaxRequest(1000);
    $page->pressButton('Save');

    // Add a taxonomy term reference field.
    $this->drupalGet('/admin/structure/types/manage/page/fields/add-field');
    if (version_compare(\Drupal::VERSION, '10', '<')) {
      $page->selectFieldOption('Add a new field', 'Taxonomy term');
    }
    else {
      $page->fillField('new_storage_type', 'reference');
      if (version_compare(\Drupal::VERSION, '10.3', '>=')) {
        $page->pressButton('Continue');
      }
      else {
        $this->assertSession()->waitForElementVisible('css', '#field_ui\:entity_reference\:taxonomy_term');
      }
      $page->fillField('field_ui:entity_reference:taxonomy_term', 'field_ui:entity_reference:taxonomy_term');
    }
    $page->fillField('Label', 'term reference');
    $this->assertSession()->waitForElementVisible('css', '.machine-name-value');
    if (version_compare(\Drupal::VERSION, '10', '>=')) {
      $page->pressButton('Continue');
    }
    else {
      $page->pressButton('Save and continue');
      $page->pressButton('Save field settings');
    }
    $page->checkField('tags');
    $this->assertSession()->assertWaitOnAjaxRequest(1000);
    $page->pressButton('Save settings');

    $this->drupalGet('/admin/structure/types/manage/page/form-display');
    $page->fillField('Plugin for term reference', 'readonly_field_widget');
    $this->assertSession()->assertWaitOnAjaxRequest(1000);
    $page->pressButton('Save');

    // Add a simple text field.
    $this->drupalGet('/admin/structure/types/manage/page/fields/add-field');
    if (version_compare(\Drupal::VERSION, '10', '<')) {
      $page->selectFieldOption('Add a new field', 'Text (plain)');
    }
    else {
      $page->fillField('new_storage_type', 'plain_text');
      if (version_compare(\Drupal::VERSION, '10.3', '>=')) {
        $page->pressButton('Continue');
      }
      else {
        $this->assertSession()->waitForElementVisible('css', '#string');
      }
      $page->fillField('string', 'string');
    }
    $page->fillField('Label', 'some plain text');
    $this->assertSession()->waitForElementVisible('css', '.machine-name-value');
    if (version_compare(\Drupal::VERSION, '10', '>=')) {
      $page->pressButton('Continue');
    }
    else {
      $page->pressButton('Save and continue');
      $page->pressButton('Save field settings');
    }
    $page->pressButton('Save settings');

    $this->drupalGet('/admin/structure/types/manage/page/form-display');
    $page->fillField('Plugin for some plain text', 'readonly_field_widget');
    $this->assertSession()->assertWaitOnAjaxRequest(1000);
    $page->pressButton('Save');

    // Add a second text field.
    $this->drupalGet('/admin/structure/types/manage/page/fields/add-field');
    if (version_compare(\Drupal::VERSION, '10', '<')) {
      $page->selectFieldOption('Add a new field', 'Text (plain)');
    }
    else {
      $page->fillField('new_storage_type', 'plain_text');
      if (version_compare(\Drupal::VERSION, '10.3', '>=')) {
        $page->pressButton('Continue');
      }
      else {
        $this->assertSession()->waitForElementVisible('css', '#string');
      }
      $page->fillField('string', 'string');
    }
    $page->fillField('Label', 'restricted text');
    $this->assertSession()->waitForElementVisible('css', '.machine-name-value');
    if (version_compare(\Drupal::VERSION, '10', '>=')) {
      $page->pressButton('Continue');
    }
    else {
      $page->pressButton('Save and continue');
      $page->pressButton('Save field settings');
    }
    $page->pressButton('Save settings');

    $this->drupalGet('/admin/structure/types/manage/page/form-display');
    $page->fillField('Plugin for restricted text', 'readonly_field_widget');
    $this->assertSession()->assertWaitOnAjaxRequest(1000);
    $page->pressButton('Save');

    // Set the title to be read-only.
    $page->fillField('Plugin for Title', 'readonly_field_widget');
    $this->assertSession()->assertWaitOnAjaxRequest(1000);
    $page->pressButton('Save');
  }

  /**
   * Test that the widget still works when default values are set up.
   */
  public function testDefaultValues() {

    // Make article field required.
    $this->drupalGet('/admin/structure/types/manage/page/fields/node.page.field_article_reference');
    $page = $this->getSession()->getPage();
    $page->checkField('Required field');
    $page->pressButton('Save settings');
    $this->assertSession()->pageTextContains('Saved article reference configuration.');

    // Set title to regular text field.
    $this->drupalGet('/admin/structure/types/manage/page/form-display');
    $page->fillField('Plugin for Title', 'string_textfield');
    $this->assertSession()->assertWaitOnAjaxRequest(1000);

    // Set the article ref field to options select dropdown.
    $page->fillField('Plugin for article reference', 'options_select');
    $this->assertSession()->assertWaitOnAjaxRequest(1000);
    $page->pressButton('Save');
    $this->assertSession()->pageTextContains('Your settings have been saved.');
    $this->assertSession()->fieldValueEquals('Plugin for article reference', 'options_select');

    // Set default value of article field to a test article node.
    $article = $this->createNode([
      'type' => 'article',
      'title' => "article {$this->randomMachineName()}",
      'status' => 1,
    ]);
    $article->save();
    $this->drupalGet('/admin/structure/types/manage/page/fields/node.page.field_article_reference');

    // Expand default value area 10.1+.
    if ($page->findField('Set default value')) {
      $page->checkField('Set default value');
    }

    $page->selectFieldOption('article reference', $article->id());
    $page->pressButton('Save settings');
    $this->assertSession()->pageTextContains('Saved article reference configuration.');

    // Set widget back to readonly.
    $this->drupalGet('/admin/structure/types/manage/page/form-display');
    $page->fillField('Plugin for article reference', 'readonly_field_widget');
    $this->assertSession()->assertWaitOnAjaxRequest(1000);
    $page->pressButton('Save');

    // Should see our test article in the default values widget.
    $this->drupalGet('/admin/structure/types/manage/page/fields/node.page.field_article_reference');

    // Expand default value area 10.1+.
    if ($page->findField('Set default value')) {
      $page->checkField('Set default value');
    }
    $this->assertSession()->pageTextContains($article->label());
    $this->assertTrue($page->findField('Required field')->isChecked());
    $page->pressButton('Save settings');
    $this->assertSession()->pageTextContains('Saved article reference configuration.');

    $this->drupalGet('/node/add/page');
    $this->assertSession()->pageTextContains($article->label());
    $new_title = $this->randomMachineName();
    $page->fillField('Title', $new_title);
    $page->pressButton('Save');
    $this->assertSession()->pageTextContains("page $new_title has been created.");
    $this->assertSession()->pageTextContains($article->label());
  }

  /**
   * Test field access on readonly fields.
   */
  public function testFieldAccess() {

    $assert = $this->assertSession();

    $test_string = $this->randomMachineName();
    $restricted_test_string = $this->randomMachineName();

    $article = $this->createNode([
      'type' => 'article',
      'title' => 'test-article',
    ]);

    $tag_term = Term::create(['vid' => 'tags', 'name' => 'test-tag']);
    $tag_term->save();

    $page = $this->createNode([
      'type' => 'page',
      'field_some_plain_text' => [['value' => $test_string]],
      'field_restricted_text' => [['value' => $restricted_test_string]],
      'field_article_reference' => $article,
      'field_term_reference' => $tag_term,
    ]);

    // As an admin, verify the widgets are readonly.
    $this->drupalLogin($this->admin);
    $this->drupalGet('node/' . $page->id() . '/edit');

    // Test the title field shows with a label.
    $field_wrapper = $assert->elementExists('css', '#edit-title-wrapper');
    $assert->elementNotExists('css', 'input', $field_wrapper);
    $assert->elementNotExists('css', 'a', $field_wrapper);
    $this->assertFieldWrapperContainsString('Title', $field_wrapper);
    $this->assertFieldWrapperContainsString($page->label(), $field_wrapper);

    $field_wrapper = $assert->elementExists('css', '#edit-field-some-plain-text-wrapper');
    $this->assertFieldWrapperContainsString($test_string, $field_wrapper);
    $assert->elementNotExists('css', 'input', $field_wrapper);

    // This shouldn't be editable by admin, but they can view it.
    $field_wrapper = $assert->elementExists('css', '#edit-field-restricted-text-wrapper');
    $this->assertFieldWrapperContainsString($restricted_test_string, $field_wrapper);
    $assert->elementNotExists('css', 'input', $field_wrapper);

    $field_wrapper = $assert->elementExists('css', '#edit-field-article-reference-wrapper');
    $this->assertFieldWrapperContainsString('test-article', $field_wrapper);
    $title_element = $assert->elementExists('css', 'h2 a span', $field_wrapper);
    $this->assertEquals($title_element->getText(), 'test-article');
    $assert->elementNotExists('css', 'input', $field_wrapper);
    $assert->elementNotExists('css', 'select', $field_wrapper);

    $field_wrapper = $assert->elementExists('css', '#edit-field-term-reference-wrapper');
    $this->assertFieldWrapperContainsString('test-tag', $field_wrapper);
    $title_element = $assert->elementExists('css', 'div:nth-child(2) a', $field_wrapper);
    $this->assertEquals($title_element->getText(), 'test-tag');
    $assert->elementNotExists('css', 'input', $field_wrapper);
    $assert->elementNotExists('css', 'select', $field_wrapper);

    // Create a regular who can update page nodes.
    $user = $this->createUser(['edit any page content']);
    $this->drupalLogin($user);
    $this->drupalGet('node/' . $page->id() . '/edit');
    $field_wrapper = $assert->elementExists('css', '#edit-field-some-plain-text-wrapper');
    $this->assertFieldWrapperContainsString($test_string, $field_wrapper);
    $assert->elementNotExists('css', 'input', $field_wrapper);

    // This field is restricted via hooks in readonly_field_widget_test.module.
    $assert->elementNotExists('css', '#edit-field-restricted-text-wrapper');
    $this->assertSession()->responseNotContains($restricted_test_string);

    $field_wrapper = $assert->elementExists('css', '#edit-field-article-reference-wrapper');
    $this->assertFieldWrapperContainsString('test-article', $field_wrapper);
    $title_element = $assert->elementExists('css', 'h2 a span', $field_wrapper);
    $this->assertEquals($title_element->getText(), 'test-article');
    $assert->elementNotExists('css', 'input', $field_wrapper);
    $assert->elementNotExists('css', 'select', $field_wrapper);

    $field_wrapper = $assert->elementExists('css', '#edit-field-term-reference-wrapper');
    $this->assertFieldWrapperContainsString('test-tag', $field_wrapper);
    $title_element = $assert->elementExists('css', 'div:nth-child(2) a', $field_wrapper);
    $this->assertEquals($title_element->getText(), 'test-tag');
    $assert->elementNotExists('css', 'input', $field_wrapper);
    $assert->elementNotExists('css', 'select', $field_wrapper);
  }

  /**
   * Check if the field widget wrapper contains the passed in string.
   */
  private function assertFieldWrapperContainsString($string, NodeElement $element) {
    $this->assertTrue((bool) preg_match('/' . $string . '/', $element->getHtml()), "field wrapper contains '" . $string . "'");
  }

}
