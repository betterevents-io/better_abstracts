<?php

namespace Drupal\mt_review_vbo\Plugin\Action;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Access\AccessResult;
use Drupal\node\Entity\Node;

/**
 *
 * @Action(
 *   id = "mt_review_vbo_send_reminder",
 *   label = @Translation("Send reminder to reviewers with incomplete reviews"),
 *   type = "user",
 *   confirm = TRUE,
 *   requirements = {
 *     "_permission" = "assign abstracts to reviewers",
 *     "_custom_access" = TRUE,
 *   },
 * )
 */

class SendReminder extends ViewsBulkOperationsActionBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $mailManager = \Drupal::service('plugin.manager.mail');
    $module = 'mt_review_vbo';
    $key = 'mt_reminder';
    $to = $entity->get('mail')->value;
    // $params['subject'] = $this->t('REMINDER: Καταληκτική ημερομηνία ολοκλήρωσης αξιολογήσεων στις 16 Απριλίου 2021. ') . $this->getDeadline();
    $params['subject'] = $this->t('ΥΠΕΝΘΥΜΙΣΗ: Καταληκτική ημερομηνία ολοκλήρωσης αξιολογήσεων στις 16 Απριλίου 2021.');
    $site_mail = \Drupal::config('system.site')->get('mail');
    $site_slogan = \Drupal::config('system.site')->get('slogan');
    $params['from'] = $site_slogan . '<' . $site_mail . '>';
    $params['abstracts'] = $this->getIncompleteAsbtracts($entity->get('uid')->value);
    $langcode = $entity->getPreferredLangcode();
    $send = true;

    $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
    if ($result['result'] != true) {
      $message = t('There was a problem sending the reminder to @email.', array('@email' => $to));
      \Drupal::messenger()->addMessage($message, 'error');
        return;
    } else {
      $message = t('Reminder sent successfully to @email.', array('@email' => $to));
      \Drupal::messenger()->addMessage($message, 'notice');
    }

    return $this->t('Finished sending reminders');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return AccessResult::allowedIfHasPermission($account, 'assign abstracts to reviewers');
  }

  private function getDeadline() {
    $config_id = 'mt_review_dashboard.settings';
    $config_key_deadline = 'deadline';
    $config = \Drupal::config($config_id);
    $deadline_str = $config->get($config_key_deadline);
    if ($deadline_str) {
      $deadline_obj = new DrupalDateTime($config->get($config_key_deadline));
    }
    $deadline_formatted = $deadline_obj ? $deadline_obj->format('F jS') : '';
    return $deadline_formatted;
  }

  private function getIncompleteAsbtracts($uid)
  {
    $abstract_ids = \Drupal::entityQuery('node')
      ->condition('type', 'mt_abstract')
      ->condition('field_mt_abstract_reviewers', $uid, 'CONTAINS')
      ->condition('status', 1)
      ->execute();
    foreach ($abstract_ids as $nid) {
      // Find review comments for the assigned abstracts
      $comment_types = ['mt_score_comments', 'mt_conflict_statement'];
      $cids = \Drupal::entityQuery('comment')
        ->condition('entity_id', $nid)
        ->condition('uid', $uid)
        ->condition('entity_type', 'node')
        ->condition('comment_type', $comment_types, 'IN')
        ->execute();
      $comments_count = count($cids);
      // Select abstracts which have not a score/conflict comment
      if ($comments_count < 1) {
        $incomplete_abstracts[] = $nid;
      }
    }
    $abstracts = Node::loadMultiple($incomplete_abstracts);
    $abstracts = array_map(function ($abstract) {
      return $abstract->get('title')->value;
    }, $abstracts);
    return $this->format($abstracts);
  }

  private function format($abstract_titles) {
    $formatted = array_map(function ($abstract) {
      return '<li>' . $abstract . '</li>';
    }, $abstract_titles);
    $formatted = implode("", $formatted);
    return '<ul>' . $formatted . '</ul>';
  }
}
