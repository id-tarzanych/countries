<?php

class countries_import_core extends countries_import_manager {

  public function importSettingsForm($context, &$form_state) {
    return array();
  }

  public function propertiesProvided() {
    return array(
      'name' => t('ISO 3166-1 Name'),
      'official_name' => t('ISO 3166-1 Offical Name'),
      'iso2' => t('ISO 3166-1 alpha-2 code'),
      'iso3' => t('ISO 3166-1 alpha-3 code'),
      'numcode' => t('ISO 3166-1 numeric-3 code'),
      'continent' => t('Continent information taken from various sources'),
      'enabled' => t('Status as defined by the ISO 3166-1. This may miss some countries that are not yet widely recognized by UN member states.'),
    );
  }

  public function import($values, $form, $form_state) {
    $countries = array();
    $langcode = $values['langcode'];
    $handle = fopen(drupal_get_path('module', 'countries') . '/countries.csv', 'r');
    $headers = fgetcsv($handle, 1024, ",");
    while (($row = fgetcsv($handle, 1024, ",")) !== FALSE) {
      // The iso2 data.
      $row[0] = trim($row[0]);
      // The iso3 data.
      $row[1] = empty($row[1]) || $row[1] == 'NULL' ? '' : trim($row[1]);
      // The name data.
      $row[2] = empty($row[2]) || $row[2] == 'NULL' ? '' : t(trim($row[2]), array(), array('langcode' => $langcode));
      // The official name data.
      $row[3] = empty($row[3]) || $row[3] == 'NULL' ? '' : t(trim($row[3]), array(), array('langcode' => $langcode));
      // The number code data.
      $row[4] = empty($row[4]) || $row[4] == 'NULL' ? 0 : trim($row[4]);
      // The continent data.
      $row[5] = empty($row[5]) || $row[5] == 'NULL' ? 'UN' : trim($row[5]);
      // The enabled data.
      $row[6] = empty($row[6]) || $row[6] == 'NULL' ? 0 : 1;
      if (!empty($row[0]) && $row[0] != 'NULL') {
        $countries[$row[0]] = (object)array(
          'iso2' => $row[0],
          'iso3' => $row[1],
          'name' => $row[2],
          'official_name' => $row[3],
          'numcode' => $row[4],
          'continent' => $row[5],
          'enabled' => $row[6],
        );
      }
    }

    return $countries;
  }

}
