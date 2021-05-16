<?php

namespace Drupal\mt_review_deadline_block\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a block with the support details and links
 * "mt_review_dashboard.settings" configuration form.
 * The "mt_review_dashboard.settings" configuration form is
 * created by the "mt_review_dashboard" module.
 *
 * @Block(
 *   id = "mt_support",
 *   admin_label = @Translation("Support"),
 *   category = @Translation("Abstracts Review"),
 * )
 */
class SupportBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Block configuration
    // $config = $this->getConfiguration();
    $current_path = \Drupal::service('path.current')->getPath();
    $config_id = 'mt_review_dashboard.settings';
    $config_key_fb_page_id = 'fb_page_id';
    $config = \Drupal::config($config_id);
    $fb_page_id = $config->get($config_key_fb_page_id);
    return [
      '#theme' => 'review_support_block',
      '#fb_page_id' => $fb_page_id,
      '#current_path' => $current_path,
    ];
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
