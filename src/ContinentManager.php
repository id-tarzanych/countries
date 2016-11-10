<?php

namespace Drupal\countries;

use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides list of continents.
 */
class ContinentManager implements ContinentManagerInterface {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * An array of country code => country name pairs.
   */
  protected $continents;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * Get an array of all two-letter continent code => continent name pairs.
   *
   * @param bool $extended
   *   TRUE if extended list required.
   *
   * @return array
   *   An array of continent code => continent name pairs.
   */
  public static function getStandardList($extended = FALSE) {
    $continents = array(
      'AF' => t('Africa'),
      'AS' => t('Asia'),
      'EU' => t('Europe'),
      'NA' => t('North America'),
      'SA' => t('South America'),
      'OC' => t('Oceania'),
      'AN' => t('Antarctica'),
      'UN' => t('Unknown'),
    );

    if ($extended) {
      $continents += [
        'AE' => t('Afro-Eurasia'),

        '1S' => t('Southern Africa'),
        '1W' => t('Western Africa'),
        '1N' => t('Northern Africa'),
        '1M' => t('Middle Africa'),
        '1E' => t('Eastern Africa'),

        '2S' => t('Southern Asia'),
        '2W' => t('Western Asia'),
        '2Z' => t('South-Eastern Asia'),
        '2E' => t('Eastern Asia'),
        '2C' => t('Central Asia'),
        'IC' => t('Indian subcontinent'),
        '2M' => t('Middle East'),
        '2G' => t('Greater Middle East'),

        '3S' => t('Southern Europe'),
        '3W' => t('Western Europe'),
        '3E' => t('Eastern Europe'),
        '3N' => t('Northern Europe'),
        'CE' => t('Continental Europe'),
        'ER' => t('Eurasia'),

        'AM' => t('Americas'),
        'CA' => t('Caribbean'),
        'AC' => t('Central America'),

        'AU' => t('Australasia'),
        'AZ' => t('Australia and New Zealand'),
        'PO' => t('Polynesia'),
        'ME' => t('Melanesia'),
        'MI' => t('Micronesia'),
      ];
    }

    // Sort the list.
    natcasesort($continents);

    return $continents;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\countries\ContinentManager::getStandardList()
   */
  public function getList($extended = FALSE) {
    // Populate the continents list if it is not already populated.
    if (!isset($this->continents)) {
      $this->continents = static::getStandardList($extended);
      $this->moduleHandler->alter('countries_continents', $this->continents);
    }

    return $this->continents;
  }

}
