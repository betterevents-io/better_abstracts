<?php

/**
 * @file
 * Hooks specific to the "Comment types" module
 */

/**
 * @addtogroup hooks_mt_review_comment_types
 * @{
 */

/**
 * Act after a review is submitted for an Abstract.
 *
 * @param $account
 *   The account of the reviewer.
 * @param $review_comment
 *   The comment entity that is submitted by the reviewer.
 * @param $abstract
 *   The node entity of Abstract that received the review.
 *
 */
function hook_mt_review_comment_types_review_submitted(\Drupal\Core\Session\AccountProxyInterface $account,
                                                       \Drupal\comment\Entity\Comment $review_comment,
                                                       \Drupal\node\NodeInterface $abstract) {
  $flag_service = \Drupal::service('flag');
  $flag = $flag_service->getFlagById('mt_review_completed');
  $flag_service->flag($flag, $abstract, $account);
}

/**
 * Act after a conflict is submitted for an Abstract.
 *
 * @param $account
 *   The account of the reviewer.
 * @param $review_comment
 *   The comment entity that is submitted by the reviewer.
 * @param $abstract
 *   The node entity of Abstract that received the review.
 *
 */
function hook_mt_review_comment_types_conflict_submitted(\Drupal\Core\Session\AccountProxyInterface $account,
                                                       \Drupal\comment\Entity\Comment $review_comment,
                                                       \Drupal\node\NodeInterface $abstract) {
  $flag_service = \Drupal::service('flag');
  $flag = $flag_service->getFlagById('mt_abstract_fully_reviewed');
  if (!empty($flag) && $flag->isFlagged($abstract)) {
    $flag_service->unflag($flag, $abstract);
  }
}

/**
 * Act after a review is deleted for an Abstract.
 *
 * @param $review_comment
 *   The comment entity that is submitted by the reviewer.
 *
 */
function hook_mt_review_comment_types_conflict_deleted(\Drupal\comment\Entity\Comment $review_comment) {
  $abstract_id = $review_comment->getCommentedEntityId();
  $abstract = \Drupal\node\Entity\Node::load($abstract_id);
  $reviewer_id = $review_comment->getOwner()->id();
  $account = Drupal\user\Entity\User::load($reviewer_id);
  $flag_service = \Drupal::service('flag');
  $flag = $flag_service->getFlagById('mt_review_completed');
  if (!empty($flag) && $flag->isFlagged($abstract, $account)) {
    $flag_service->unflag($flag, $abstract, $account, $skip_permission_check = TRUE);
  }
}

/**
 * @} End of "addtogroup hooks_mt_review_comment_types".
 */
