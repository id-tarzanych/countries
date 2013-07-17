<?php

/**
 * @file
 * Definition of countries_import_manager.
 */

/**
 * Defines a base class for country import managers.
 */
abstract class countries_import_manager {

  public function importSettingsForm($context, &$form_state) {
    return array();
  }

  public function importKey() {
    return 'iso2';
  }

  /**
   * Returns a list of properties that this provider handles.
   *
   * @return array
   *   An array of properties keyed by the entity property and a description
   *   of the data used to construct this.
   */
  abstract public function propertiesProvided();

  public function suggestedImportProperties() {
    return array_keys($this->propertiesProvided());
  }

  abstract public function import($values, $form, $form_state);

  protected function isValidISO2($code) {
    $code = drupal_strtoupper($code);
    return drupal_strlen($code) == 2 && !preg_match('/[^A-Z]/', $code);
  }

  protected function isUserDefinedISO2($code) {
    $code = drupal_strtoupper($code);
    if ($this->isValidISO2($code)) {
      return preg_match('/^(AA)|(Q[M-Z]+)|(X[A-Z]+)|(ZZ)$/', $code);
    }
    return FALSE;
  }
}
