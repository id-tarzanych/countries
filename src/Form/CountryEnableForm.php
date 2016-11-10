<?php

namespace Drupal\countries\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form constructor for Country enable form.
 */
class CountryEnableForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Do you really want to enable @country?', [
      '@country' => $this->getEntity()->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This will enable country %country.', [
      '%country' => $this->getEntity()->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->getEntity()->toUrl('collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Enable');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->enable()->save();
    drupal_set_message($this->t('Enabled country %country.', array('%country' => $this->entity->label())));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
