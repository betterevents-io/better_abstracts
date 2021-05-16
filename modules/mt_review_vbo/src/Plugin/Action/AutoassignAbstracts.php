<?php

namespace Drupal\mt_review_vbo\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 *
 * @Action(
 *   id = "mt_review_vbo_autoassign_abstracts",
 *   label = @Translation("Autoassign abstracts to reviewers"),
 *   type = "node",
 *   confirm = TRUE,
 *   requirements = {
 *     "_permission" = "assign abstracts to reviewers",
 *     "_custom_access" = FALSE,
 *   },
 * )
 */

class AutoassignAbstracts extends ViewsBulkOperationsActionBase {

  use StringTranslationTrait;

  protected $FIELD_KEY_ABSTRACT_REVIEWERS = 'field_mt_abstract_reviewers';
  protected $FIELD_KEY_ABSTRACT_TOPIC = 'field_mt_abstract_topic';
  protected $FIELD_KEY_USER_INTEREST = 'field_mt_area_of_interest';
  protected $USER_ROLE_NAME_REVIEWER = 'Reviewer';

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $abstract_topics =  $entity->get($this->FIELD_KEY_ABSTRACT_TOPIC)->referencedEntities();
    $reviewers_based_on_topics = $this->getReviewers($abstract_topics);
    // Get cardinality of field_mt_abstract_reviewers
    // The maximum number or reviewers an abstract can have
    $abstract_fields = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions('node', 'mt_abstract');
    $reviewers_limit = $abstract_fields[$this->FIELD_KEY_ABSTRACT_REVIEWERS]->getCardinality();
    $current_abstract_reviewers = $entity->get($this->FIELD_KEY_ABSTRACT_REVIEWERS)->referencedEntities();
    $nonassigned_reviewers = $this->selectNonAssignedReviewers($reviewers_based_on_topics, $reviewers_limit,
      $current_abstract_reviewers);
    foreach ($nonassigned_reviewers as $key => $reviewer) {
      $reviewer_id = $reviewer->id();
      $reviewer_name= $reviewer->get('name')->value;
      $entity->get($this->FIELD_KEY_ABSTRACT_REVIEWERS)->appendItem([
        'target_id' => $reviewer_id,
      ]);
      $entity->save();
      \Drupal::messenger()->addMessage($this->t('"' . $entity->get('title')->value
        . '" assigned to reviewer ' .  $reviewer_name));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\node\NodeInterface $object */
    return $object->access('update', $account);
  }

  private function countAssignments($reviewer)
  {
    $abstract_ids = \Drupal::entityQuery('node')
      ->condition('type', 'mt_abstract')
      ->condition('field_mt_abstract_reviewers', $reviewer->id(), 'CONTAINS')
      ->condition('status', 1)
      ->execute();
    return count($abstract_ids);
  }

  private function getReviewers($topics = NULL) {
    $user_ids = \Drupal::entityQuery('user')
      ->condition('roles', $this->USER_ROLE_NAME_REVIEWER, 'CONTAINS')
      ->condition('status', 1)
      ->execute();
    $users = \Drupal\user\Entity\User::loadMultiple($user_ids);
    // Count assignments for each reviewer and sort the reviewers based on that.
    // Reviewers are sorted in ascending order.
    $count_assignments = function($reviewer) {
      $reviewer->assignments = $this->countAssignments($reviewer);
      return $reviewer ;
    };
    $reviewers =  array_map($count_assignments, $users);
    usort($reviewers,function($first,$second){
      return $first->assignments > $second->assignments;
    });

    // Exclude reviewers that do not have the same areas of interest
    // We are keeping a reviewer if just 1 abstract topic belongs to the reviewer's areas of interest.
    if(empty($topics)){
      return $reviewers;
    }
    $reviewers = array_filter($reviewers, function ($reviewer) use ($topics) {
      $reviewer_topics = $reviewer->get($this->FIELD_KEY_USER_INTEREST)->referencedEntities();
      $topics_intersection = array_uintersect($reviewer_topics, $topics, function($topic_a, $topic_b) {
        return $topic_a <=> $topic_b;
        });
      return count($topics_intersection) > 0;
    });
    return $reviewers;
  }

  // Returns reviewers that are not already assigned to the abstract
  private function selectNonAssignedReviewers($reviewers, $reviewers_limit, $abstract_reviewers) {
    $current_abstract_reviewers_count = count($abstract_reviewers);
    $diff_reviewers = array_udiff($reviewers, $abstract_reviewers,
      function ($obj_a, $obj_b) {
        return $obj_a->id() <=> $obj_b->id();
      }
    );
    // Take the first X number of reviewers
    $selected_reviewers = array_slice($diff_reviewers,0,$reviewers_limit-$current_abstract_reviewers_count);
    return $selected_reviewers;
  }
}
