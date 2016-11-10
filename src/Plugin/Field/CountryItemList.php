<?php

namespace Drupal\countries\Plugin\Field;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Form\FormStateInterface;
use Drupal\countries\Plugin\Field\CountryItemListInterface;

/**
 * Defines a item list class for Country fields.
 */
class CountryItemList extends EntityReferenceFieldItemList implements CountryItemListInterface  {

  /**
   * {@inheritdoc}
   */
  public function referencedEntities() {
    if (empty($this->list)) {
      return [];
    }

    $target_countries = $ids = array();
    foreach ($this->list as $delta => $item) {
      if ($item->iso2 !== NULL) {
        $ids[$delta] = $item->iso2;
      }
      elseif ($item->target_id !== NULL) {
        $ids[$delta] = $item->target_id;
      }
      elseif ($item->hasNewEntity()) {
        $target_countries[$delta] = $item->entity;
      }
    }

    // Load and add the existing entities.
    if ($ids) {
      $countries = \Drupal::entityTypeManager()
        ->getStorage('country')
        ->loadMultiple($ids);

      foreach ($ids as $delta => $iso2) {
        if (isset($countries[$iso2])) {
          $target_countries[$delta] = $countries[$iso2];
        }
      }

      // Ensure the returned array is ordered by deltas.
      ksort($target_countries);
    }

    return $target_countries;
  }

  /**
   * {@inheritdoc}
   */
  public static function processDefaultValue($default_value, FieldableEntityInterface $entity, FieldDefinitionInterface $definition) {
    $default_value = FieldItemList::processDefaultValue($default_value, $entity, $definition);

    if ($default_value) {
      // Convert UUIDs to numeric IDs.
      $uuids = array();
      foreach ($default_value as $delta => $properties) {
        if (isset($properties['target_uuid'])) {
          $uuids[$delta] = $properties['target_uuid'];
        }
      }
      if ($uuids) {
        $entity_ids = \Drupal::entityQuery('country')
          ->condition('uuid', $uuids, 'IN')
          ->execute();
        $entities = \Drupal::entityTypeManager()
          ->getStorage('country')
          ->loadMultiple($entity_ids);

        $entity_uuids = [];
        foreach ($entities as $id => $entity) {
          $entity_uuids[$entity->uuid()] = $id;
        }
        foreach ($uuids as $delta => $uuid) {
          if (isset($entity_uuids[$uuid])) {
            $default_value[$delta]['target_id'] = $default_value[$delta]['iso2'] = $entity_uuids[$uuid];
            unset($default_value[$delta]['target_uuid']);
          }
          else {
            unset($default_value[$delta]);
          }
        }
      }

      $default_value = array_values($default_value);
    }
    return $default_value;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormSubmit(array $element, array &$form, FormStateInterface $form_state) {
    $default_value = FieldItemList::defaultValuesFormSubmit($element, $form, $form_state);

    $ids = array();
    foreach ($default_value as $delta => $properties) {
      $default_value[$delta]['iso2'] = $default_value[$delta]['target_id'];
      if (isset($properties['entity']) && $properties['entity']->isNew()) {
        // This may be a newly created term.
        $properties['entity']->save();
        $default_value[$delta]['target_id'] = $default_value[$delta]['iso2'] = $properties['entity']->id();
        unset($default_value[$delta]['entity']);
      }
      $ids[] = $default_value[$delta]['iso2'];
    }
    $entities = \Drupal::entityTypeManager()
      ->getStorage('country')
      ->loadMultiple($ids);

    foreach ($default_value as $delta => $properties) {
      unset($default_value[$delta]['iso2']);
      unset($default_value[$delta]['target_id']);
      $default_value[$delta]['target_uuid'] = $entities[$properties['iso2']]->uuid();
    }

    return $default_value;
  }

}
