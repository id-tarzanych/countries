<?php

namespace Drupal\countries\Plugin\migrate\field;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * MigrateField Plugin for Drupal 6 and 7 countries fields.
 *
 * @MigrateField(
 *   id = "country",
 *   core = {7},
 *   type_map = {
 *     "country" = "country"
 *   },
 *   source_module = "countries",
 *   destination_module = "countries"
 * )
 */
class Country extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    return [
      'country_default' => 'countries_continent',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [
      'country_default' => 'country_default',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defineValueProcessPipeline(MigrationInterface $migration, $field_name, $data) {
    $process = [
      'plugin' => 'country',
      'source' => $field_name,
    ];
    $migration->mergeProcessOfProperty($field_name, $process);
  }

  /**
   * {@inheritdoc}
   */
  public function processFieldValues(MigrationInterface $migration, $field_name, $data) {
    $this->defineValueProcessPipeline($migration, $field_name, $data);
  }

}
