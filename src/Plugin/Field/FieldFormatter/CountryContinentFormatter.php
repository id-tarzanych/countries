<?php

namespace Drupal\countries\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\countries\ContinentManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'country_number' formatter.
 *
 * @FieldFormatter(
 *   id = "country_continent_code",
 *   label = @Translation("Continent"),
 *   field_types = {
 *     "country"
 *   }
 * )
 */
class CountryContinentFormatter extends CountryFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Complete list of continents.
   *
   * @var array
   */
  protected $continents;

  /**
   * Constructs a CountryContinentFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\countries\ContinentManagerInterface $continent_manager
   *   The continent manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ContinentManagerInterface $continent_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->continents = $continent_manager->getList(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // @see \Drupal\Core\Field\FormatterPluginManager::createInstance().
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('countries.continent_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    /** @var \Drupal\countries\CountryInterface $country */
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $country) {
      if ($country->id()) {
        $elements[$delta] = [
          '#plain_text' => $this->continents[$country->get('continent')],
          '#cache' => [
            'tags' => $country->getCacheTags(),
          ],
        ];
      }
    }

    return $elements;
  }

}
