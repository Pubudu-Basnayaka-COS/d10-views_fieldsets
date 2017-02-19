<?php

namespace Drupal\views_fieldsets\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views_fieldsets\RowFieldset;

/**
 * @ingroup views_field_handlers
 *
 * @ViewsField("fieldset")
 */
class Fieldset extends FieldPluginBase {

  /**
   *
   */
  static public function getWrapperTypes() {
    $types = &drupal_static(__METHOD__);
    if (!$types) {
      // @todo Get from hook_theme() definitions?
      $types = ['details' => 'details', 'fieldset' => 'fieldset', 'div' => 'div'];
    }

    return $types;
  }

  /**
   *
   */
  static public function isFieldsetView(ViewExecutable $view) {
    foreach ($view->field as $field) {
      if ($field instanceof self) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   *
   */
  static public function getAllFieldsets(ViewExecutable $view) {
    return array_filter($view->field, function($field) {
      return $field instanceof self;
    });
  }

  /**
   *
   */
  static public function replaceFieldsetHandlers(ViewExecutable $view, array &$fields, ResultRow $row) {
    $fieldsets = self::getAllFieldsets($view);

    // Replace Fieldsets.
    foreach ($fields as $name => $field) {
      if (isset($fieldsets[$name])) {
        $fields[$name] = new RowFieldset($field, $row);
      }
    }

    // Move Children.
    foreach ($fieldsets as $fieldset_name => $fieldset) {
      foreach ($fieldset->getChildren() as $child_name) {
        if (isset($fields[$child_name])) {
          $fields[$fieldset_name]->addChild($fields, $child_name);
        }
      }
    }

    return $fieldsets;
  }

  /**
   *
   */
  public function getChildren() {
    return $this->options['fields'];
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['fields'] = ['default' => []];
    $options['wrapper'] = ['default' => 'fieldset'];
    $options['legend'] = ['default' => ''];
    $options['classes'] = ['default' => ''];
    $options['collapsible'] = ['default' => TRUE];
    $options['collapsed'] = ['default' => FALSE];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    // $form['fields'] = [
    //   '#type' => 'value',
    //   '#value' => $this->options['fields'],
    // ];
    $form['fields'] = [
      '#type' => 'textfield',
      '#title' => 'DEBUG: FIELDS',
      '#default_value' => implode(', ', $this->options['fields']),
      '#description' => 'DEBUG: comma-separated list of field names in this fieldset.',
    ];

    $help_tokenized = t('With row tokens, eg. <code>{{ title }}</code>.');

    $form['wrapper'] = [
      '#type' => 'select',
      '#title' => t('Wrapper type'),
      '#options' => self::getWrapperTypes(),
      '#default_value' => $this->options['wrapper'],
      '#required' => TRUE,
    ];

    $form['legend'] = [
      '#type' => 'textfield',
      '#title' => t('Fieldset legend'),
      '#default_value' => $this->options['legend'],
      '#description' => $help_tokenized,
    ];

    $form['classes'] = [
      '#type' => 'textfield',
      '#title' => t('Wrapper classes'),
      '#default_value' => $this->options['classes'],
      '#description' => $help_tokenized . ' ' . t('Separate classes with DOUBLE SPACES. Single spaces and much else will be converted to valid class name.'),
    ];

    $form['collapsible'] = [
      '#type' => 'checkbox',
      '#title' => t('Collapsible'),
      '#default_value' => $this->options['collapsible'],
    ];

    $form['collapsed'] = [
      '#type' => 'checkbox',
      '#title' => t('Collapsed'),
      '#default_value' => $this->options['collapsed'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    $options = &$form_state->getValue('options');

    $fields = $options['fields'];
    $fields = array_filter(array_map('trim', explode(',', $fields)));
    $options['fields'] = $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    // This will be overridden in RowFieldset::render(), which is called by magic through $field->content.
    return '[' . implode('|', $this->getChildren()) . ']';
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Don't add this non-field to the query.
  }

  /**
   * {@inheritdoc}
   */
  protected function allowAdvancedRender() {
    return FALSE;
  }

}
