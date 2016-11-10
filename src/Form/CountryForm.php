<?php

namespace Drupal\countries\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\countries\ContinentManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class that represents entity form for Country entities.
 */
class CountryForm extends EntityForm {

  /**
   * The country entity.
   *
   * @var \Drupal\countries\CountryInterface
   */
  protected $entity;

  /**
   * The country storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The continent manager.
   *
   * @var \Drupal\countries\ContinentManagerInterface
   */
  protected $continentManager;

  /**
   * Constructs a CountryForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\countries\ContinentManagerInterface $continent_manager
   *   The continent manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ContinentManagerInterface $continent_manager) {
    $this->storage = $entity_type_manager->getStorage('country');
    $this->continentManager = $continent_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('countries.continent_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $country = $this->entity;

    $form = parent::form($form, $form_state);

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $country->getName(),
      '#description' => $this->t('Specify an unique name for this country.'),
      '#required' => TRUE,
      '#maxlength' => 95,
    ];

    $form['iso2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ISO alpha-2 code'),
      '#default_value' => $country->id(),
      '#required' => TRUE,
      '#maxlength' => 2,
      '#description' => $this->t('Specify an unique alpha-2 code for this country. This is used as the key to this country, changing it may result in data loss.'),
    ];

    $form['iso3'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ISO alpha-3 code'),
      '#default_value' => $country->get('iso3'),
      '#description' => $this->t('Specify an unique ISO alpha-3 code for this country.'),
      '#required' => FALSE,
      '#maxlength' => 3,
    ];

    $form['official_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Official name'),
      '#default_value' => $country->getOfficialName(),
      '#description' => $this->t('Specify an unique official name for this country.'),
      '#required' => FALSE,
      '#maxlength' => 127,
    ];

    $form['numcode'] = [
      '#type' => 'textfield',
      '#title' => t('ISO numeric-3 code'),
      '#default_value' => $country->get('numcode'),
      '#description' => t('Specify an unique ISO numeric-3 code for this country.'),
      '#required' => FALSE,
      '#maxlength' => 5,
    ];

    $form['continent'] = [
      '#type' => 'select',
      '#title' => $this->t('Continent'),
      '#options' => $this->continentManager->getList(TRUE),
      '#default_value' => $country->getContinent(),
      '#required' => TRUE,
    ];

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Status'),
      '#default_value' => $country->get('status'),
      '#description' => $this->t('Disabling a country should removing it from all country listings, with the exclusion of views and fields define by the Countries module. These will allow you to choose the status per field.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    try {
      $result = parent::save($form, $form_state);

      drupal_set_message($this->t('Country %name has been saved.', ['%name', $this->entity->getName()]));

      return $result;
    }
    catch (\Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
      $form_state->setRebuild();
    }
  }

}
