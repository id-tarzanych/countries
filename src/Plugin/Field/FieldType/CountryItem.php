<?php

namespace Drupal\countries\Plugin\Field\FieldType;

use Drupal\Core\Entity\TypedData\EntityDataDefinition;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataReferenceDefinition;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;
use Drupal\countries\CountryInterface;

/**
 * Plugin implementation of the 'country' field type.
 *
 * @FieldType(
 *   id = "country",
 *   label = @Translation("Country"),
 *   description = @Translation("This field stores a country reference in the database."),
 *   category = @Translation("General"),
 *   default_widget = "countries_continent",
 *   default_formatter = "country_default",
 *   list_class = "\Drupal\countries\Plugin\Field\CountryItemList"
 * )
 */
class CountryItem extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'target_type' => 'country',
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'countries' => [],
      'continents' => [],
      'enabled' => COUNTRIES_ENABLED,
      'size' => 5,
      'handler' => 'default',
      'handler_settings' => [],
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    /** @var FieldConfigInterface $field */
    $field = $form_state->getFormObject()->getEntity();

    $form['enabled'] = [
      '#type' => 'radios',
      '#title' => $this->t('Country status'),
      '#default_value' => $field->getSetting('enabled') ?: COUNTRIES_ENABLED,
      '#options' => [
        COUNTRIES_ALL => $this->t('Both'),
        COUNTRIES_ENABLED => $this->t('Enabled countries only'),
        COUNTRIES_DISABLED => $this->t('Disabled countries only'),
      ],
    ];

    // Load countries options.
    $options = \Drupal::entityTypeManager()
      ->getStorage('country')
      ->getCountries('name');

    $form['countries'] = [
      '#type' => 'select',
      '#title' => $this->t('Filter by country'),
      '#default_value' => $field->getSetting('countries') ?: [],
      '#options' => $options,
      '#description' => $this->t('If no countries are selected, this filter will not be used.'),
      '#size' => 10,
      '#multiple' => TRUE,
    ];

    $form['continents'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Filter by continent'),
      '#default_value' => $field->getSetting('continents') ?: [],
      '#options' => \Drupal::service('countries.continent_manager')->getList(TRUE),
      '#description' => $this->t('If no continents are selected, this filter will not be used.'),
      '#element_validate' => [[get_class($this), 'checkboxFilter']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $target_type_info = \Drupal::entityTypeManager()->getDefinition('country');
    $target_id_definition = DataReferenceTargetDefinition::create('string')
      ->setLabel(new TranslatableMarkup('@label ID', ['@label' => $target_type_info->getLabel()]));

    $target_id_definition->setRequired(TRUE);
    $properties['iso2'] = $properties['target_id'] = $target_id_definition;

    $properties['entity'] = DataReferenceDefinition::create('entity')
      ->setLabel($target_type_info->getLabel())
      ->setDescription(new TranslatableMarkup('The referenced entity'))
      ->setComputed(TRUE)
      ->setReadOnly(FALSE)
      ->setTargetDefinition(EntityDataDefinition::create('country'))
      ->addConstraint('EntityType', 'country');

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'target_id';
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'iso2' => [
          'type' => 'varchar',
          'length' => 2,
          'not null' => FALSE,
        ],
      ],
      'indexes' => [
        'iso2' => ['iso2'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    if (isset($values) && !is_array($values)) {
      $this->set('entity', $values, $notify);
    }
    else {
      parent::setValue($values, FALSE);

      if (is_array($values) && !isset($values['entity'])) {
        if (array_key_exists('target_id', $values)) {
          $this->onChange('target_id', FALSE);
        }
        if (array_key_exists('iso2', $values)) {
          $this->onChange('iso2', FALSE);
        }
      }
      elseif (is_array($values) && !array_key_exists('target_id', $values) && !array_key_exists('iso2', $values) && isset($values['entity'])) {
        $this->onChange('entity', FALSE);
      }
      elseif (is_array($values) && isset($values['entity'])) {
        if (array_key_exists('iso2', $values) || array_key_exists('target_id', $values)) {
          $entity_id = $this->get('entity')->getTargetIdentifier();

          if (
            !$this->entity->isNew() &&
            (($values['iso2'] !== NULL && ($entity_id !== $values['iso2'])) || ($values['target_id'] !== NULL && ($entity_id !== $values['target_id'])))
          ) {
            throw new \InvalidArgumentException('The target id and entity passed to the entity reference item do not match.');
          }
        }
      }

      // Notify the parent if necessary.
      if ($notify && $this->parent) {
        $this->parent->onChange($this->getName());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($property_name, $notify = TRUE) {
    // Make sure that the target ID and the target property stay in sync.
    if ($property_name == 'entity') {
      $property = $this->get('entity');
      $target_id = $property->isTargetNew() ? NULL : $property->getTargetIdentifier();
      $this->writePropertyValue('iso2', $target_id);
      $this->writePropertyValue('target_id', $target_id);
    }
    elseif ($property_name == 'target_id') {
      $this->writePropertyValue('entity', $this->target_id);
    }
    elseif ($property_name == 'iso2') {
      $this->writePropertyValue('entity', $this->iso2 ?: $this->target_id);
    }

    parent::onChange($property_name, $notify);
  }


  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    if ($this->iso2 !== NULL || $this->target_id !== NULL) {
      return FALSE;
    }
    if ($this->entity && $this->entity instanceof CountryInterface) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    if ($this->hasNewEntity()) {
      // Save the entity if it has not already been saved by some other code.
      if ($this->entity->isNew()) {
        $this->entity->save();
      }
      // Make sure the parent knows we are updating this property so it can
      // react properly.
      $this->target_id = $this->iso2 = $this->entity->id();
    }
    if (!$this->isEmpty() && ($this->iso2 === NULL || $this->target_id === NULL)) {
      $this->target_id = $this->iso2 = $this->entity->id();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $manager = \Drupal::service('plugin.manager.entity_reference_selection');

    // Instead of calling $manager->getSelectionHandler($field_definition)
    // replicate the behavior to be able to override the sorting settings.
    $options = array(
      'target_type' => 'country',
      'handler' => $field_definition->getSetting('handler'),
      'handler_settings' => $field_definition->getSetting('handler_settings') ?: array(),
      'entity' => NULL,
    );

    $entity_type = \Drupal::entityTypeManager()->getDefinition('country');
    $options['handler_settings']['sort'] = [
      'field' => $entity_type->getKey('id'),
      'direction' => 'DESC',
    ];
    $selection_handler = $manager->getInstance($options);

    // Select a random number of references between the last 50 referenceable
    // entities created.
    if ($referenceable = $selection_handler->getReferenceableEntities(NULL, 'CONTAINS', 50)) {
      $group = array_rand($referenceable);
      $values['target_id'] = $values['iso2'] = array_rand($referenceable[$group]);
      return $values;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    // Remove entity type select elements from field storage config form.
    return [];
  }

  /**
   * Determines whether the item holds an unsaved entity.
   *
   * This is notably used for "autocreate" widgets, and more generally to
   * support referencing freshly created entities (they will get saved
   * automatically as the hosting entity gets saved).
   *
   * @return bool
   *   TRUE if the item holds an unsaved entity.
   */
  public function hasNewEntity() {
    return !$this->isEmpty() && $this->iso2 === NULL && $this->target_id === NULL && $this->entity->isNew();
  }

  /**
   * Helper function to filter empty options from a multi-checkbox field.
   */
  public static function checkboxFilter(&$element, FormStateInterface $form_state) {
    $form_state->setValueForElement($element, $element['#value']);
  }

}
