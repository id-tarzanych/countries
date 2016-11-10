<?php

namespace Drupal\countries\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines a class to check whether a country is able to be enabled or disabled.
 */
class CountryAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\countries\CountryInterface $entity */
    if ($operation === 'enable') {
      if (!$entity->isEnabled()) {
        return AccessResult::allowedIfHasPermission($account, 'administer countries');
      }

      return AccessResult::forbidden();
    }

    if ($operation === 'disable') {
      if ($entity->isEnabled()) {
        return AccessResult::allowedIfHasPermission($account, 'administer countries');
      }

      return AccessResult::forbidden();
    }

    return parent::checkAccess($entity, $operation, $account);
  }
}
