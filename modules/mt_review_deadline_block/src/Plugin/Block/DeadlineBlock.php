<?php

namespace Drupal\mt_review_deadline_block\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a block with the deadline date set through the
 * "mt_review_dashboard.settings" configuration form.
 * The "mt_review_dashboard.settings" configuration form is
 * created by the "mt_review_dashboard" module.
 *
 * @Block(
 *   id = "mt_deadline",
 *   admin_label = @Translation("Deadline"),
 *   category = @Translation("Abstracts Review"),
 * )
 */
class DeadlineBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Block configuration
    // $config = $this->getConfiguration();
    $current_path = \Drupal::service('path.current')->getPath();
    $config_id = 'mt_review_dashboard.settings';
    $config_key_deadline = 'deadline';
    $config = \Drupal::config($config_id);
    $deadline_str = $config->get($config_key_deadline);
    if ($deadline_str) {
      $deadline_obj = new DrupalDateTime($config->get($config_key_deadline));
    }
    $deadline_formatted = $deadline_obj ? $deadline_obj->format('d/m/Y') : 'Not configured yet';
    $hide = $this->hide($current_path);
    return [
      '#theme' => 'review_deadline_block',
      '#deadline' => $deadline_formatted,
      '#current_path' => $current_path,
      '#hide' => $hide
    ];
  }

  private function hide($current_path){
    $re = '/^\/user\/(\d$|\d\/abstracts)/m';
    $reviewer_role = 'mt_reviewer';
    // Get the current user
    /* @var \Drupal\Core\Session\AccountProxy */
    $account = \Drupal::currentUser();
    $isReviewer = in_array($reviewer_role, $account->getRoles());
    if(!$isReviewer || preg_match($re, $current_path) )
    {
      return true;
    }
    return false;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
  }
}
