<?php

namespace Drupal\countries\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\countries\ContinentManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'options_buttons' widget.
 *
 * @FieldWidget(
 *   id = "countries_continent",
 *   label = @Translation("Countries by continent"),
 *   field_types = {
 *     "country"
 *   }
 * )
 */
class CountriesContinentWidget extends WidgetBase implements ContainerFactoryPluginInterface  {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The continent manager.
   *
   * @var \Drupal\countries\ContinentManagerInterface
   */
  protected $continentManager;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\countries\ContinentManagerInterface $continent_manager
   *   The continent manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, ContinentManagerInterface $continent_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->entityTypeManager = $entity_type_manager;
    $this->continentManager = $continent_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('countries.continent_manager')
    );
  }


  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return ['extended' => TRUE] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['extended'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use extended list of continents'),
      '#default_value' => $this->getSetting('extended'),
      '#weight' => -1,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $display_label = $this->getSetting('extended');
    $summary[] = $this->t('Use extended list of continents: @extended', ['@extended' => $display_label ? $this->t('Yes') : $this->t('No')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $settings = $this->fieldDefinition->getSettings();
    $required = !empty($element['#required']);
    $continents = $this->continentManager->getList($this->getSetting('extended'));

    if (!empty($settings['continents'])) {
      $continents = array_intersect_key($continents, $settings['continents']);
    }

    // Widget may be rendered individually, in a group or even a sub-form.
    $field_parents = empty($form['#parents']) ? [] : $form['#parents'];
    $parents = array_merge($field_parents, [$field_name, $delta]);
    $continent_parents = array_merge($parents, ['continent']);

    $iso2 = empty($items[$delta]->iso2) ? '' : $items[$delta]->iso2;
    $selected_continent = '';
    if (!empty($form_state->getValues())) {
      // This override the settings that are passed onto the options filter
      // query. This is not called when JScript is disabled.
      $selected_continent = NestedArray::getValue($form_state->getValues(), $continent_parents);
      if (!$selected_continent) {
        // Fallback to the raw user post. A workaround for AJAX submissions.
        $user_data = NestedArray::getValue($form_state->getUserInput(), $continent_parents);
        if (array_key_exists($user_data, $continents)) {
          $selected_continent = $user_data;
        }
      }
      // Do not override the continent settings when empty.
      if ($selected_continent) {
        $settings['continents'] = [$selected_continent];
      }
    }
    elseif (!empty($iso2) && $country = $this->entityTypeManager->getStorage('country')->load($iso2)) {
      $selected_continent = $country->getContinent();
    }

    // Work out the best ID to exactly target the element.
    $wrapper_parents = array_merge($parents, ['iso-wrapper']);
    $wrapper_id = implode('-', $wrapper_parents);

    // Assist themers with floating DIVs.
    $element['#type'] = 'container';
    $element['#attributes']['class'][] = 'clearfix';
    $element['#attributes']['class'][] = 'countries-continent-wrapper';
    $element['continent'] = [
      '#type' => 'select',
      '#title' => $this->t('Continent'),
      '#options' => $continents,
      '#empty_option' => $this->t('-- None selected --'),
      '#empty_value' => 'none',
      '#default_value' => $selected_continent,
      '#required' => $required,
      '#ajax' => [
        'callback' => [$this, 'continentWidgetCallback'],
        'wrapper' => $wrapper_id,
      ],
    ];

    $first = array_shift($continent_parents);
    $element['target_id'] = [
      '#type' => 'country',
      '#title' => $this->t('Country'),
      '#default_value' => $iso2,
      '#prefix' => '<div id="' . $wrapper_id . '" class="countries-ajax-wrapper">',
      '#suffix' => '</div>',
      '#empty_value' => '',
      '#hide_empty' => TRUE,
      '#filters' => $settings,
      '#required' => $required,
      '#states' => [
        'visible' => [
          ':input[name="' . $first . '[' . implode('][', $continent_parents) . ']"]' => ['!value' => 'none'],
        ],
      ],
    ];

    return $element;
  }

  /**
   * Returns the country element filtered by continent via AJAX.
   *
   * @param array $form
   *   Form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state instance.
   *
   * @return array
   *   Form element render array.
   */
  function continentWidgetCallback(array $form, FormStateInterface $form_state) {
    $parents = $form_state->getTriggeringElement()['#array_parents'];
    array_pop($parents);

    $parents[] = 'target_id';
    $element = NestedArray::getValue($form, $parents);

    return $element;
  }

}
