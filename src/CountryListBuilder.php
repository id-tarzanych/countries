<?php

namespace Drupal\countries;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Country entities.
 *
 * @see \Drupal\countries\Entity\Country
 */
class CountryListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    $header['iso2'] = $this->t('ISO alpha-2 code');
    $header['continent'] = $this->t('Continent');
    $header['official_name'] = $this->t('Official name');
    $header['enabled'] = $this->t('Status');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\countries\CountryInterface $entity */
    $row['name'] = $entity->getName();
    $row['iso2'] = $entity->id();
    $row['continent'] = $entity->getContinentName();
    $row['official_name'] = $entity->getOfficialName();
    $row['enabled'] = $entity->isEnabled() ? $this->t('Yes') : $this->t('No');

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    return parent::getDefaultOperations($entity) + [
      'edit' => [
        'title' => $this->t('Edit'),
        'weight' => 100,
        'url' => $entity->toUrl('edit-form'),
      ],
      'delete' => [
        'title' => $this->t('Delete'),
        'weight' => 200,
        'url' => $entity->toUrl('delete-form'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('There are currently no countries. <a href=":url">Add a new one</a>.', [
      ':url' => Url::fromRoute('entity.country.add')->toString(),
    ]);

    return $build;
  }

}
