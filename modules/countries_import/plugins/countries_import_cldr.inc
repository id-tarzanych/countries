<?php

class countries_import_cldr extends countries_import_manager {

  public function importSettingsForm($context, &$form_state) {
    if (empty($form_state['countries_import_cldr'])) {
      include_once DRUPAL_ROOT . '/includes/iso.inc';
      $languages = $context['languages'];
      foreach (_locale_get_predefined_list() as $key => $value) {
        if (isset($languages[$key])) {
          continue;
        }
        if (count($value) > 1) {
          $tname = t($value[0]);
          $languages[$key] = ($tname == $value[1]) ? $tname : "$tname ($value[1])";
        }
        else {
          $languages[$key] = t($value[0]);
        }
      }
      $languages[t('Other')] = array();
      $response = drupal_http_request('http://www.unicode.org/repos/cldr/trunk/common/main/');
      if ($response->code == 200 && !empty($response->data)) {
        preg_match_All("|href=[\"'](.*?)\.xml[\"']|", $response->data, $hrefs);

        $xml_files = array();
        foreach ($hrefs[1] as $href) {
          list($base) = explode('_', $href);
          if (isset($languages[$base])) {
            $xml_files[$languages[$base]][$href] = $href;
          }
          else {
            $xml_files[t('Other')][$href] = $href;
          }
        }
        uksort ($xml_files, 'countries_sort');
        $other = $xml_files[t('Other')];
        unset($xml_files[t('Other')]);
        $xml_files[t('Other')] = $other;
        $form_state['countries_import_cldr'] = $xml_files;
      }
    }
    if (!isset($form_state['countries_import_cldr'])) {
      $form['error'] = array(
        '#markup' => t('Unable to connect to www.unicode.org site, please check the servers internet connectivity.'),
      );
      return $form;
    }
    elseif (empty($form_state['countries_import_cldr'])) {
      $form['error'] = array(
        '#markup' => t('No source files to import were found.'),
      );
      return $form;
    }

    $form['xml_file'] = array(
      '#type' => 'select',
      '#title' => t('Source file'),
      '#options' => $form_state['countries_import_cldr'],
      '#required' => TRUE,
    );
    $form['parse_base'] = array(
      '#type' => 'checkbox',
      '#title' => t('Also parse base locale XML file'),
      '#default_value' => TRUE,
      '#description' => t('Attempt to complete missing values using the base locale. For example, en_AU.xml may only define 1 or 2 countries, while en.xml should define all countries.')
    );
    $form['remove_invalid'] = array(
      '#type' => 'checkbox',
      '#title' => t('Remove countries or territories with invalid ISO codes'),
      '#default_value' => TRUE,
      '#description' => t('The CLDR country list often contains non-country based values such as continent information that have codes like 001, etc. If selected, step two will require you to specify valid ISO alpha-2 codes for these if you want to import them.'),
    );
    $form['remove_user_defined'] = array(
      '#type' => 'checkbox',
      '#title' => t('Remove countries or territories with user defined ISO codes'),
      '#default_value' => FALSE,
      '#description' => t('These are the ISO alpha-2 codes AA, QM to QZ, XA to XZ, and ZZ. Two such codes as of July 2013 were "XK" for "Kosovo" and "ZZ" for "Unknown Region".'),
    );


    return $form;
  }

  public function propertiesProvided() {
    return array(
      'name' => t('Commonly used country names.'),
      'official_name' => t('Same data as the name field.'),
    );
  }

  public function suggestedImportProperties() {
    return array('name');
  }

  public function import($values, $form, $form_state) {
    $countries = array();
    $url = 'http://www.unicode.org/repos/cldr/trunk/common/main/' . $values['xml_file'] . '.xml';
    $response = drupal_http_request($url);
    if ($response->code == 200 && !empty($response->data)) {
      $xml = simplexml_load_string($response->data);
      if ($xml) {
        $territories = $xml->xpath('/ldml/localeDisplayNames/territories/territory');
        if (is_array($territories)) {
      		foreach ($territories as $territory) {
      		  if($name = (string) $territory) {
              $iso = drupal_strtoupper((string) $territory['type']);
              if ($values['remove_invalid'] && !$this->isValidISO2($iso)) {
                continue;
              }
      		    if ($values['remove_user_defined'] && $this->isUserDefinedISO2($iso)) {
                continue;
              }
              $type = isset($territory['alt']) ? (string) $territory['alt'] : 'name';
              switch ($type) {
                case 'name':
                  $countries[$iso] = (object) array(
                    'name' => $name,
                    'official_name' => $name,
                  );
                  break;
              }
      		  }
      		}
        }
      }
    }

    if ($values['parse_base']) {
      $parts = explode('_', $values['xml_file']);
      if (count($parts) <= 1 || empty($parts[0])) {
        return $countries;
      }

      $found = FALSE;
      foreach ($form_state['countries_import_cldr'] as $language => $files) {
        if (isset($files[$parts[0]])) {
          $found = TRUE;
          break;
        }
      }

      if ($found) {
        $url = 'http://www.unicode.org/repos/cldr/trunk/common/main/' . $parts[0] . '.xml';
        $response = drupal_http_request($url);
        if ($response->code == 200 && !empty($response->data)) {
          $xml = simplexml_load_string($response->data);
          if ($xml) {
            $territories = $xml->xpath('/ldml/localeDisplayNames/territories/territory');
            if (is_array($territories)) {
          		foreach ($territories as $territory) {
          		  if($name = (string) $territory) {
          		    $iso = drupal_strtoupper((string) $territory['type']);
                  if (!isset($countries[$iso])) {
            		    if ($values['remove_invalid'] && !$this->isValidISO2($iso)) {
                      continue;
                    }
            		    if ($values['remove_user_defined'] && $this->isUserDefinedISO2($iso)) {
                      continue;
                    }
                    $type = isset($territory['alt']) ? (string) $territory['alt'] : 'name';
                    switch ($type) {
                      case 'name':
                        $countries[$iso] = (object) array(
                          'name' => $name,
                          'official_name' => $name,
                          'iso2' => $iso,
                        );
                        break;
                    }
                  }
          		  }
          		}
            }
          }
        }
      }
    }
    return $countries;
  }

}
