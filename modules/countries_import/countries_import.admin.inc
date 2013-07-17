<?php

function countries_import_bulk_import_form($form, &$form_state) {
  if (!isset($form_state['stage'])) {
    $form_state['stage'] = 'source';
  }

  switch ($form_state['stage']) {
    case 'source':
      return _countries_import_bulk_import_source_form($form, $form_state);
    case 'selection':
      return _countries_import_bulk_import_selection_form($form, $form_state);
  }

  return $form;
}

function _countries_import_bulk_import_source_form($form, &$form_state) {
  $languages = language_list('enabled');
  $list = array();
  foreach ($languages[1] as $language) {
    $list[$language->language] = t($language->name);
  }
  if (count($list) > 1 || 1) {
    $form['langcode'] = array(
      '#type' => 'select',
      '#title' => t('Language'),
      '#options' => $list,
      '#default_value' => key($list),
    );
  }
  else {
    $form['langcode'] = array(
      '#type' => 'value',
      '#value' => key($list),
    );
  }
  $plugins = array();
  foreach (countries_import_plugins() as $class => $plugin) {
    if (!class_exists($class)) {
      continue;
    }
    $plugins[$class] = $plugin['title'];
  }
  $form['source'] = array(
    '#type' => 'select',
    '#title' => t('Data source'),
    '#options' => $plugins,
    '#required' => TRUE,
    '#ajax' => array(
      'event' => 'change',
      'callback' => 'countries_import_bulk_import_form_ajax_callback',
      'wrapper' => 'countries-import-bulk-import-settings',
    ),
  );
  $form['plugin'] = array(
    '#tree' => TRUE,
    '#type' => 'container',
    '#prefix' => '<div id="countries-import-bulk-import-settings">',
    '#suffix' => '</div>',
  );

  if( (!empty($form_state['values']['source']))) {
    $core_properties = countries_core_properties();
    $manger = new $form_state['values']['source']();
    $plugin_info = countries_import_plugins($form_state['values']['source']);
    $form['plugin']['info'] = array(
      '#type' => 'item',
      '#title' => $plugin_info['title'],
      '#markup' => $plugin_info['description'],
    );
    $form['plugin']['settings'] = $manger->importSettingsForm(array('languages' => $list), $form_state);
    $form['plugin']['properties'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Properties to update'),
      '#default_value' => $manger->suggestedImportProperties(),
      '#options' => array(),
    );
    $property_info = array();
    foreach ($manger->propertiesProvided() as $property => $description) {
      if (isset($core_properties[$property])) {
        $form['plugin']['properties']['#options'][$property] = $core_properties[$property];
         $property_info[] = t('<strong>%label: </strong> %description', array(
            '%label' => $core_properties[$property],
            '%description' => $description,
        ));
      }
    }
    $form['plugin']['properties']['#description'] = t('<em>!source</em> defines it properties as: !list', array(
      '!list' => theme('item_list', array('items' => $property_info)),
      '!source' => $plugin_info['title'],
    ));
  }
  $form['skip_disabled'] = array(
    '#type' => 'checkbox',
    '#title' => t('Ignore disabled countries'),
    '#default_value' => TRUE,
    '#description' => t('This excludes any disabled countries from the second step'),
  );
  $form['actions']['#type'] = 'actions';
  $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Next >'));
  return $form;
}

function countries_import_bulk_import_form_ajax_callback($form, $form_state) {
  return $form['plugin'];
}

function _countries_import_bulk_import_selection_form($form, &$form_state) {
  $plugin = new $form_state['values']['source']();
  $lookup_key = $plugin->importKey();
  $form['countries'] = array(
    '#tree' => TRUE,
    '#type' => 'container',
  );
  $form['source'] = array(
    '#type' => 'value',
    '#value' => $form_state['values']['source'],
  );
  $properties = $form_state['import_settings']['properties'];
  $properties[$lookup_key] = 1;
  $continents = countries_get_continents();

  $i18n = module_exists('countries_i18n');
  $update_i18n = $i18n && $form_state['langcode'] != language_default('language');

  foreach ($form_state['import_data'] as $key => $data) {
    $data->$lookup_key = $key;
    foreach (countries_core_properties() as $property => $property_label) {
      if (empty($properties[$property]) || !property_exists($data, $property)) {
        $data->$property = '';
      }
    }

    $country = countries_country_lookup($key, $lookup_key);
    if (!$country) {
      // We need a name to parse new countries.
      if (empty($properties['name'])) {
        continue;
      }
    }
    else {
      if ($form_state['skip_disabled'] && !$country->enabled) {
        continue;
      }
    }
    $form['countries'][$key]['skip'] = array(
      '#type' => 'checkbox',
      '#title' => t('Skip'),
      '#default_Value' => 1,
    );
    $form['countries'][$key]['enabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Status'),
      '#default_value' => isset($properties['enabled']) ? (bool)$data->enabled : ($country ? $country->enabled : FALSE),
    );

    $form['countries'][$key]['cid'] = array(
      '#type' => 'value',
      '#value' => $country ? $country->cid : NULL,
    );

    $form['countries'][$key]['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#default_value' => $data->name,
      '#required' => TRUE,
      '#maxlength' => 95,
    );

    $locked = country_is_locked($country);
    $form['countries'][$key]['iso2'] = array(
      '#type' => 'textfield',
      '#title' => t('ISO alpha-2 code'),
      '#default_value' => $data->iso2,
      '#required' => TRUE,
      '#maxlength' => 2,
    );

    $form['countries'][$key]['iso3'] = array(
      '#type' => 'textfield',
      '#title' => t('ISO alpha-3 code'),
      '#default_value' => $data->iso3,
      '#maxlength' => 3,
    );

    $form['countries'][$key]['official_name'] = array(
      '#type' => 'textfield',
      '#title' => t('Official name'),
      '#default_value' => $data->official_name,
      '#maxlength' => 127,
    );

    $form['countries'][$key]['numcode'] = array(
      '#type' => 'textfield',
      '#title' => t('ISO numeric-3 code'),
      '#default_value' => $data->numcode,
      '#maxlength' => 5,
    );

    $default_value = isset($continents[$data->continent]) ? $data->continent : '';
    $form['countries'][$key]['continent'] = array(
      '#type' => 'select',
      '#title' => t('Continent'),
      '#options' => $continents,
      '#default_value' => $default_value,
      '#empty_value' => '',
    );

    if ($country) {
      $has_update = FALSE;
      foreach (countries_core_properties() as $property => $property_label) {
        if (empty($form['countries'][$key][$property]['#default_value'])) {
          $form['countries'][$key][$property]['#default_value'] = $country->$property;
        }
        if (empty($properties[$property])) {
          $form['countries'][$key][$property]['#disabled'] = TRUE;
        }
        else {
          $comparison_property = $country->$property;
          if ($update_i18n && in_array($property, array('name', 'official_name'))) {
            $comparison_property = country_property($country, $property, array('langcode' => $form_state['langcode'], 'sanitize' => 0));
          }
          if ($comparison_property == $data->$property) {
            $form['countries'][$key][$property]['#disabled'] = TRUE;
          }
          else {
            $has_update = TRUE;
            if ($property == 'continent') {
              $form['countries'][$key][$property]['#description'] = t('Currently is "%value"', array('%value' => isset($continents[$country->continent]) ? $continents[$country->continent] : $country->continent));
            }
            else {
              $form['countries'][$key][$property]['#description'] = t('Currently is "%value"', array('%value' => $comparison_property));
            }
          }
        }
      }
      if (!$has_update) {
        unset($form['countries'][$key]);
      }
    }
  }
  $form['actions']['#type'] = 'actions';
  $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Import'));
  $form['countries']['#theme'] = 'countries_import_bulk_import_selection_form';
  $form['countries']['#update_country_i18n'] = $update_i18n;

  return $form;
}

function theme_countries_import_bulk_import_selection_form($variables) {
  $elements = $variables['element'];
  $header = array(
    t('Skip'),
    t('Status'),
    t('Name'),
    t('Official name'),
    t('ISO alpha-2 code'),
    t('ISO alpha-3 code'),
    t('ISO numeric-3 code'),
    t('Continent'),
  );
  $rows = array();
  foreach (element_children($elements) as $child) {
    $row = array();
    $element = &$elements[$child];
    foreach (array('skip' => 0, 'enabled' => 0, 'name' => 25, 'official_name' => 25, 'iso2' => 3, 'iso3'  => 4, 'numcode' => 4, 'continent' => 0) as $property => $size) {
      $element[$property]['#title_display'] = 'invisible';
      if (in_array($property, array('skip', 'enabled')) && isset($element[$property]['#description'])) {
        unset($element[$property]['#description']);
      }
      if ($size) {
        $element[$property]['#size'] = $size;
      }
      $row[] = drupal_render($element[$property]) . ($property == 'continent' ? drupal_render($element) : '');
    }
    $rows[] = $row;
  }

  return theme('table', array('header' => $header, 'rows' => $rows, 'empty' => t('No countries were found to update.'))) . drupal_render_children($elements);
}

function countries_import_bulk_import_form_submit($form, &$form_state) {
  $class = $form_state['values']['source'];
  $plugin = new $class();

  switch ($form_state['stage']) {
    case 'source':
      $settings = empty($form_state['values']['plugin']['settings']) ? array() : $form_state['values']['plugin']['settings'];
      $settings['langcode'] = $form_state['values']['langcode'];
      $form_state['import_data'] = $plugin->import($settings, $form, $form_state);
      $form_state['stage'] = 'selection';
      $form_state['source'] = $form_state['values']['source'];
      $form_state['langcode'] = $form_state['values']['langcode'];
      $form_state['skip_disabled'] = $form_state['values']['skip_disabled'];
      $form_state['import_settings'] = $form_state['values']['plugin'];
      $form_state['rebuild'] = TRUE;
      break;

    case 'selection':
      if (empty($form_state['values']['countries'])) {
        unset($form_state['rebuild']);
      }
      $lookup_key = $plugin->importKey();

      $skipped = 0;
      $errors = 0;
      $updated = 0;
      $created = 0;

      $i18n = module_exists('countries_i18n');
      $update_i18n = $i18n && $form_state['langcode'] != language_default('language');
      foreach ($form_state['values']['countries'] as $values) {
        if (!empty($values['skip'])) {
          $skipped++;
          continue;
        }

        if ($values['cid']) {
          $countries = countries_load($values['cid']);
          $country = reset($countries);
          $country->enabled = $values['enabled'];
          foreach (countries_core_properties() as $property => $label) {
            $updated_property = trim($values[$property]);
            switch ($property) {
              case 'continent':
                if (empty($updated_property)) {
                  $values[$property] = 'UN';
                }
                $country->$property = $updated_property;
                break;

              case 'name':
              case 'official_name':
                if ($form_state['langcode'] == language_default('language')) {
                  $country->$property = $updated_property;
                }
                else {
                  // Leave the default untouched but save the changes.
                  if ($update_i18n) {
                    $name = 'countries:country:' . $country->iso2 . ':' . $property;
                    list($textgroup, $context) = i18n_string_context(explode(':', $name));
                    i18n_string_textgroup($textgroup)->update_translation($context, $form_state['langcode'], $updated_property);
                  }
                }
                break;

              default:
                $country->$property = $updated_property;
            }
          }
        }
        else {
          $country = (object) $values;
          $country->language = language_default('language');
          if (empty($country->continent)) {
            $country->continent = 'UN';
          }
        }
        if (country_validate($country)) {
          if (country_save($country) == SAVED_NEW) {
            $created++;
            drupal_set_message(t('Created %name (%iso2)', array(
                '%name' => $country->name,
                '%iso2' => $country->iso2,
            )));
            if ($update_i18n) {
              $name = 'countries:country:' . $country->iso2 . ':name';
              list($textgroup, $context) = i18n_string_context(explode(':', $name));
              i18n_string_textgroup($textgroup)->update_translation($context, $form_state['langcode'], $country->name);

              if (!empty($country->official_name)) {
                $name = 'countries:country:' . $country->iso2 . ':official_name';
                list($textgroup, $context) = i18n_string_context(explode(':', $name));
                i18n_string_textgroup($textgroup)->update_translation($context, $form_state['langcode'], $country->official_name);
              }
            }
          }
          else {
            $updated++;
            drupal_set_message(t('Updated %name (%iso2)', array(
                '%name' => country_property($country),
                '%iso2' => $country->iso2,
            )));
          }
          // Clear the form state if the country was imported.
          unset($form_state['import_data'][$country->$lookup_key]);
        }
        else {
          $errors++;
          drupal_set_message(t('Errors were detected while trying to import %name:<br/>!errors', array(
              '%name' => $country->name,
              '!errors' => implode('<br />', $country->_errors),
          )), 'error');
        }
      }
      if ($skipped || $errors) {
        $form_state['rebuild'] = TRUE;
      }
      drupal_set_message(t('Import complete.')
          . ' ' . format_plural($skipped, t('Skipped 1 country.'), t('Skipped @count countries.'))
          . ' ' . format_plural($created, t('Created 1 new country.'), t('Created @count new countries.'))
          . ' ' . format_plural($updated, t('Updated 1 country.'), t('Updated @count countries.'))
          . ' ' . format_plural($errors, t('1 error was detected.'), t('@count errors were detected.')));
      break;
  }

}
