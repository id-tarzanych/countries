<?php

namespace Drupal\countries\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Defines country select list element.
 *
 * @FormElement("country")
 */
class Country extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#size' => 0,
      '#multiple' => FALSE,
      '#process' => [
        [$class, 'expandCountry'],
        ['\Drupal\Core\Render\Element\Select', 'processSelect'],
        ['\Drupal\Core\Render\Element\RenderElement', 'processAjaxForm'],
      ],
      '#element_validate' => [
        [$class, 'validateCountry'],
      ],
      '#theme' => 'select',
      '#theme_wrappers' => ['form_element'],
      // Filter based on enabled flag or continents to filter the options.
      // See countries_filter() for details.
      '#filters' => [],
      // If empty, the default list is the system country list, which is the
      // list of all enabled countries, which runs through
      // hook_countries_alter().
      // Otherwise, the module runs it's own country list based on the filters.
      '#options' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input = FALSE, FormStateInterface $form_state) {
    if ($input !== FALSE) {
      if (isset($element['#multiple']) && $element['#multiple']) {
        return (is_array($input)) ? array_values($input) : [];
      }
      else {
        return $input;
      }
    }
  }

  /**
   * Our process callback to expand the country FAPI element.
   */
  public static function expandCountry(&$element, FormStateInterface $form_state, &$complete_form) {
    /** @var \Drupal\countries\CountryStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('country');

    if (empty($element['#options'])) {
      if (empty($element['#filters'])) {
        $element['#filters']['enabled'] = COUNTRIES_ENABLED;
      }

      $element['#options'] = $storage->getCountries('name');
    }
    $element['#options'] = $storage->filterCountries($element['#options'], $element['#filters']);

    // Ensure that this is set in case '#hide_empty' is used.
    if (empty($element['#required']) && !isset($element['#empty_value'])) {
      $element['#empty_value'] = '';
    }

    // Adds a hidden element style for support of the continent-country widget.
    if (!empty($element['#hide_empty'])) {
      // Only return the element if it's not empty.
      $count = count($element['#options']);
      if (!$count || ($count == 1 && isset($element['#options'][$element['#empty_value']]))) {
        $element += [
          '#prefix' => '',
          '#suffix' => '',
        ];
        $element['#prefix'] .= '<div style="display: none;">';
        $element['#suffix'] = '</div>' . $element['#suffix'];
      }
    }

    return $element;
  }

  /**
   * Render API callback: Validates the managed_file element.
   */
  public static function validateCountry(&$element, FormStateInterface $form_state) {
    if (!isset($element['#cardinality'])) {
      return;
    }

    $values = [];
    if (!is_array($element['#value'])) {
      $element['#value'] = array_filter([$element['#value']]);
    }
    foreach (array_values($element['#value']) as $value) {
      $values[] = ['iso2' => $value];
    }
    if ($element['#cardinality'] >= 0 && count($values) > $element['#cardinality']) {
      $title = empty($element['#title']) ? t('Countries') : $element['#title'];
      $form_state->setError($element, t('%name field is restricted to %max countries.', ['%name' => $title, '%max' => $element['#cardinality']]));
    }
  }

}
