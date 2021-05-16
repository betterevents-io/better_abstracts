<?php

use Drupal\views\ViewExecutable;

/**
 * Implements hook_views_pre_render().
 */
function mt_review_users_view_views_pre_render(ViewExecutable $view) {

  if ($view->id() !== 'mt_reviewers_incomplete') {
    return;
  }
  // Filter the "mt_reviewers_incomplete" view so it includes Reviewer users who have incomplete reviews
  $userResultRows = $view->result;
  $filteredReviewers = [];

  foreach ($userResultRows as $result) {
    $uid = $result->uid;
    $abstract_ids = \Drupal::entityQuery('node')
      ->condition('type', 'mt_abstract')
      ->condition('field_mt_abstract_reviewers', $uid)
      ->condition('status', 1)
      ->execute();
    foreach ($abstract_ids as $nid) {
      // Find review comments for the assigned abstract
      $comment_types = ['mt_score_comments', 'mt_conflict_statement'];
      $cids = \Drupal::entityQuery('comment')
        ->condition('entity_id', $nid)
        ->condition('uid', $uid)
        ->condition('entity_type', 'node')
        ->condition('comment_type', $comment_types, 'IN')
        ->execute();
      $comments_count = count($cids);
      // Select reviewers who have not submitted a score/conflict comment
      if ($comments_count < 1) {
        $filteredReviewers[] = $result;
      }
    }
  }
  // Remove duplicate objects from the $filteredReviewers array
  $filteredUniqueIds = array_unique(array_map(function($reviewer) {
    return $reviewer->uid;
  }, $filteredReviewers));
  $filteredReviewers = array_values(array_intersect_key($filteredReviewers, $filteredUniqueIds));
  // Pass the new data to the view result
  $view->total_rows = count($filteredReviewers);
  $view->pager->total_items = count($filteredReviewers);
  $view->pager->updatePageInfo();
  // Fix indexes after filtering
  $filteredReviewers = array_map(function($reviewer, $key) {
    $reviewer->index = $key;
    return $reviewer;
  }, $filteredReviewers, array_keys($filteredReviewers));
  $view->result = $filteredReviewers;
}
