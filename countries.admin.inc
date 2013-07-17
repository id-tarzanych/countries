<?php

/**
 * @file
 * Admin page callbacks for the Countries module.
 */

/**
 * Menu callback; Displays a list of all countries.
 */
function countries_admin_overview() {
  global $language;
  $header = array();
  $header[] = array('data' => t('Name'), 'field' => 'c.name', 'sort' => 'asc');
  $header[] = array('data' => t('ISO alpha-2 code'), 'field' => 'c.iso2');
  $columns = variable_get('countries_admin_overview_columns',
    array(
      'iso3' => t('ISO alpha-3 code'),
      'numcode' => t('ISO numeric-3 code'),
      'continent' => t('Continent'),
      'official_name' => t('Official name'))
  );
  foreach ($columns as $key => $title) {
    $header[] = array('data' => $title, 'field' => 'c.' . $key);
  }
  $header[] = array('data' => t('Status'), 'field' => 'c.enabled');

  $header[] = array('data' => t('Operations'));
  $query = db_select('countries_country', 'c')->extend('PagerDefault')->extend('TableSort');

  $result = $query
    ->fields('c')
    ->orderByHeader($header)
    ->limit(50)
    ->execute();

  $destination = drupal_get_destination();

  // Show db and translated strings if supported.
  $i18n = (language_default('language') != $language->language)
      && module_exists('countries_i18n');

  $rows = array();
  // Note that additional id's are added for testing.
  foreach ($result as $country) {
    $row = array();
    $base_url = 'admin/config/regional/countries/' . $country->iso2;
    $name = country_property($country, 'name', array('sanitize' => 0));
    $row[] = l($name, $base_url, array('query' => $destination))
        . ($i18n ? '<br/><small>(' . check_plain($country->name) . ')</small>' : '');
    $row[] = array(
      'data' => country_property($country, 'iso2'),
      'id' => $country->iso2 . '-iso2',
    );
    foreach ($columns as $key => $title) {
      switch ($key) {
        case 'official_name':
          $value = country_property($country, $key, array('default' => ''));
          $row[] = array(
            'data' => $value . (($i18n && !empty($country->$key)) ? '<br/><small>(' . check_plain($country->$key) . ')</small>' : ''),
            'id' => $country->iso2 . '-' . $key,
          );
          break;

        default:
          $row[] = array(
            'data' => country_property($country, $key, array('default' => '')),
            'id' => $country->iso2 . '-' . $key,
          );
      }
    }
    $row[] = array(
      'data' => country_property($country, 'enabled'),
      'id' => $country->iso2 . '-enabled',
    );
    $operations = l(t('edit'), $base_url, array('query' => $destination));
    if (module_exists('countries_regions')) {
      $count = countries_regions_count($country);
      $operations .= '&nbsp;&nbsp;&nbsp;' . l(t('!count regions', array('!count' => $count)), $base_url . '/regions', array('query' => $destination));
    }
    if (!country_is_locked($country)) {
      $operations .= '&nbsp;&nbsp;&nbsp;' . l(t('delete'), $base_url . '/delete', array('query' => $destination));
    }
    $row[] = $operations;
    $rows[] = $row;
  }
  if (empty($rows)) {
    $rows[] = array(
      array('data' => t('No countries are available.'), 'colspan' => count($header)),
    );
  }

  $build['countries_table']  = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
  );
  $build['countries_pager'] = array('#theme' => 'pager');

  return $build;
}

/**
 * Menu callback; Display a country form.
 */
function countries_admin_page($country = NULL) {
  // Menu callbacks and local actions do not have page titles.
  if (isset($country)) {
    drupal_set_title(t('Edit country %title', array('%title' => $country->name)), PASS_THROUGH);
  }
  else {
    drupal_set_title(t('Add country'), PASS_THROUGH);
    $country = country_create();
  }
  return drupal_get_form('countries_admin_form', $country);
}

/**
 * Generate a country form.
 *
 * @ingroup forms
 * @see countries_admin_form_validate()
 * @see countries_admin_form_submit()
 */
function countries_admin_form($form, &$form_state, $country) {
  $form['cid'] = array(
    '#type' => 'value',
    '#value' => $country->cid,
  );
  $form['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Name'),
    '#default_value' => $country->name,
    '#description' => t('Specify an unique name for this country.'),
    '#required' => TRUE,
    '#maxlength' => 95,
  );
  $locked = country_is_locked($country);
  $form['iso2'] = array(
    '#type' => 'textfield',
    '#title' => t('ISO alpha-2 code'),
    '#default_value' => $country->iso2,
    '#required' => TRUE,
    '#maxlength' => 2,
    '#disabled' => $locked,
  );
  if ($locked) {
    $form['iso2']['#description'] = t('Core country ISO alpha-2 codes are not editable.');
  }
  else {
    $form['iso2']['#description'] = t('Specify an unique alpha-2 code for this country. This is used as the key to this country, changing it may result in data loss.');
  }
  $form['iso3'] = array(
    '#type' => 'textfield',
    '#title' => t('ISO alpha-3 code'),
    '#default_value' => $country->iso3,
    '#description' => t('Specify an unique ISO alpha-3 code for this country.'),
    '#required' => FALSE,
    '#maxlength' => 3,
  );
  $form['official_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Official name'),
    '#default_value' => $country->official_name,
    '#description' => t('Specify an unique official name for this country.'),
    '#required' => FALSE,
    '#maxlength' => 127,
  );
  $form['numcode'] = array(
    '#type' => 'textfield',
    '#title' => t('ISO numeric-3 code'),
    '#default_value' => empty($country->numcode) ? '' : $country->numcode,
    '#description' => t('Specify an unique ISO numeric-3 code for this country.'),
    '#required' => FALSE,
    '#maxlength' => 5,
  );
  $form['continent'] = array(
    '#type' => 'select',
    '#title' => t('Continent'),
    '#options' => countries_get_continents(),
    '#default_value' => $country->continent,
    '#required' => TRUE,
  );
  $form['enabled'] = array(
    '#type' => 'checkbox',
    '#title' => t('Status'),
    '#default_value' => $country->enabled,
    '#description' => t('Disabling a country should removing it from all country listings, with the exclusion of views and fields define by the Countries module. These will allow you to choose the status per field.'),
  );

  if (!empty($country->iso2)) {
    $form['#country'] = $country;
  }

  field_attach_form('country', $country, $form, $form_state);
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
    '#weight' => 100,
  );
  return $form;
}

/**
 * Validate country form submissions.
 */
function countries_admin_form_validate($form, &$form_state) {
  $country = (object) $form_state['values'];

  // Added to provide a better UI experience. Decide to keep or drop.
  if (!empty($country->iso2) && $existing = country_load($country->iso2)) {
    // New country, the ISO alpha-2 must not be used.
    if (empty($form['#country'])) {
      form_set_error('iso2',
        t('Another country was found with this ISO alpha-2 code; !link',
        array(
          '!link' => l(country_property($existing),
          'admin/config/regional/countries/' . $existing->iso2),
        )));
      return;
    }
    elseif ($existing->iso2 != $form['#country']->iso2) {
      form_set_error('iso2', t('Another country was found with this ISO alpha-2 code; !link',
        array('!link' => l(country_property($existing), 'admin/config/regional/countries/' . $existing->iso2))));
      return;
    }
  }
  if (country_validate($country)) {
    $form_state['values'] = (array) $country;
  }
  else {
    foreach ($country->_errors as $property => $error_message) {
      form_set_error($property, $error_message);
    }
  }
}

/**
 * Process country form submissions.
 */
function countries_admin_form_submit($form, &$form_state) {
  $country = (object) $form_state['values'];
  entity_form_submit_build_entity('country', $country, $form, $form_state);

  if (country_save($country) == SAVED_UPDATED) {
    $message = t('The country %country has been updated.', array('%country' => $country->name));
  }
  else {
    $message = t('Added country %country.', array('%country' => $country->name));
  }
  drupal_set_message($message);
  $form_state['redirect'] = 'admin/config/regional/countries';
}

/**
 * Menu callback; confirm deletion of a country.
 *
 * @ingroup forms
 * @see countries_admin_delete_submit()
 */
function countries_admin_delete($form, &$form_state, $country) {
  if (country_is_locked($country)) {
    drupal_set_message(t('Core countries defined by the system can not be deleted.'), 'error');
    drupal_goto('admin/config/regional/countries');
  }

  $form['#country'] = $country;

  return confirm_form($form,
    t('Are you sure you want to delete the country %country?', array('%country' => $country->name)),
    'admin/config/regional/countries',
    t('References that use this country will become invalid. This action cannot be undone.'),
    t('Delete'),
    t('Cancel')
  );
}

/**
 * Process countries delete form submission.
 */
function countries_admin_delete_submit($form, &$form_state) {
  $country = $form['#country'];
  country_delete($country->iso2);

  drupal_set_message(t('Deleted country %country.', array('%country' => $country->name)));
  $form_state['redirect'] = 'admin/config/regional/countries';
}

/**
 * Helper function to validate a core country property.
 */
function countries_property_invalid($property, &$value) {
  static $schema = NULL;
  if (!isset($schema)) {
    $schema = drupal_get_schema('countries_country');
  }

  $value = trim($value);
  switch ($property) {
    case 'iso2':
      $value = drupal_strtoupper($value);
      if (preg_match('/[^A-Z]/', $value) || drupal_strlen($value) != 2) {
        return t('ISO alpha-2 code must contain 2 letters between A and Z. %value was found.', array('%value' => $value));
      }
      break;

    case 'iso3':
      $value = drupal_strtoupper($value);
      if (preg_match('/[^A-Z]/', $value) || drupal_strlen($value) != 3) {
        return t('ISO alpha-3 code must contain 3 letters between A and Z. %value was found.', array('%value' => $value));
      }
      break;

    case 'numcode':
      if (!empty($value) && (preg_match('/[^0-9]/', $value) || ($value > 999 || $value < 0))) {
        return t('ISO numeric-3 code must be a number between 1 and 999. %value was found.', array('%value' => $value));
      }
      break;

    default:
      if (isset($schema['fields'][$property])) {
        $field_schema = $schema['fields'][$property];
        if (isset($field_schema['length']) && $field_schema['length'] < drupal_strlen($value)) {
          $core_properties = countries_core_properties();
          return t('!property must be less than !count characters.',
              array('!property' => $core_properties[$property], '!count' => $field_schema['length']));
        }
      }
      break;
  }
  return FALSE;
}

/**
 * Helper function to check for duplicates.
 */
function country_duplicate_field_check($country) {
  $duplicates = array();
  $cid = empty($country->cid) ? FALSE : $country->cid;
  foreach (array('name', 'official_name', 'iso2', 'iso3', 'numcode') as $property) {

    if (drupal_strlen($country->$property)) {
      if ($property == 'numcode' && empty($country->$property)) {
        continue;
      }

      $query = db_select('countries_country', 'c')
          ->fields('c', array('iso2'));
      if ($cid) {
        $query->condition('cid', $cid, '!=');
      }
      if ($property == 'official_name' || $property == 'name') {
        $db_or = db_or();
        $db_or->condition('official_name', $country->$property)
            ->condition('name', $country->$property);
        $query->condition($db_or);
      }
      else {
        $query->condition($property, $country->$property);
      }

      $value = $query->execute()->fetchColumn();
      if (!empty($value)) {
        $conflict = country_load($value);
        $duplicates[$property] = t('The <em>@value</em> is already used by <em>@country</em>',
            array(
              '@value' => country_property($country, $property),
              '@country' => country_property($conflict, 'name'),
            ));
      }
    }
  }
  return empty($duplicates) ? FALSE : $duplicates;
}
