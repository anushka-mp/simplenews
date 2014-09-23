<?php

/**
 * @file
 * Contains \Drupal\simplenews\Form\SubscriptionsAccountForm.
 */

namespace Drupal\simplenews\Form;

use Drupal\Component\Utility\String;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simplenews\Entity\Subscriber;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Configure simplenews subscriptions of a user.
 */
class SubscriptionsAccountForm extends SubscriberFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simplenews_subscriptions_account';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $user = NULL) {
    // Uid parameter has to be named $user but we use that name for the entity.
    $uid = $user;

    // Try to load a subscriber from the uid.
    if (isset($uid)) {
      $user = User::load($uid);
      $form_state->set('user', $user);
      $this->setEntity(simplenews_subscriber_load_by_uid($uid) ?: Subscriber::create(array('mail' => $user->getEmail())));
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user = \Drupal::currentUser();

    $account = $form_state->get('user');

    // We first subscribe, then unsubscribe. This prevents deletion of subscriptions
    // when unsubscribed from the
    arsort($form_state->getValue('newsletters'), SORT_NUMERIC);
    foreach ($form_state->getValue('newsletters') as $newsletter_id => $checked) {
      if ($checked) {
        simplenews_subscribe($account->getEmail(), $newsletter_id, FALSE, 'website');
      }
      else {
        simplenews_unsubscribe($account->getEmail(), $newsletter_id, FALSE, 'website');
      }
    }
    if ($user->id() == $account->id()) {
      drupal_set_message(t('Your newsletter subscriptions have been updated.'));
    }
    else {
      drupal_set_message(t('The newsletter subscriptions for user %account have been updated.', array('%account' => $account->label() )));
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Checks access for the simplenews account form.
   *
   * @param int $user
   *   The ID of the account to use in the form.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   An access result object.
   */
  public function checkAccess($user) {
    $user = User::load($user);
    $account = $this->currentUser();

    return AccessResult::allowedIfHasPermission($account, 'administer simplenews subscriptions')
      ->orIf(AccessResult::allowedIfHasPermission($account, 'subscribe to newsletters')
        ->andIf(AccessResult::allowedIf($user->id() == $account->id())));
  }

}
