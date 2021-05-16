<?php

namespace Drupal\mt_review_vbo\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 *
 * @Action(
 *   id = "mt_review_vbo_assign_award_abstracts",
 *   label = @Translation("Assign abstracts to Awards Board"),
 *   type = "node",
 *   confirm = TRUE,
 *   requirements = {
 *     "_permission" = "assign abstracts to reviewers",
 *     "_custom_access" = TRUE,
 *   },
 * )
 */

class AssignAwardAbstracts extends ViewsBulkOperationsActionBase {

  use StringTranslationTrait;

  protected $FIELD_KEY_ABSTRACT_AW_REVIEWERS = 'field_mt_abstract_aw_reviewers';
  protected $USER_ROLE_NAME_AWARDS_BOARD_MEMBER = 'mt_awards_board_member';

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $awards_board_members = $this->getReviewers();
    $current_abstract_reviewers = $entity->get($this->FIELD_KEY_ABSTRACT_AW_REVIEWERS);
    foreach ($awards_board_members as &$reviewer_id) {
      if (!$this->reviewerExists($reviewer_id, $current_abstract_reviewers->getValue())) {
        $entity->get($this->FIELD_KEY_ABSTRACT_AW_REVIEWERS)->appendItem([
          'target_id' => $reviewer_id,
        ]);
        $entity->save();
      }
    }

    return $this->t('Abstract assignments finished');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\node\NodeInterface $object */
    return $object->access('update', $account);
  }

  private function getReviewers() {
    $user_ids = \Drupal::entityQuery('user')
      ->condition('roles', $this->USER_ROLE_NAME_AWARDS_BOARD_MEMBER)
      ->condition('status', 1)
      ->execute();
    return $user_ids;
  }

  private function reviewerExists($reviewer_id, $abstract_reviewers) {
    $abstract_reviewer_id = function($reviewer) {
      return $reviewer['target_id'];
    };
    $flat_abstract_reviewers = array_map($abstract_reviewer_id, $abstract_reviewers);
    return in_array($reviewer_id ,$flat_abstract_reviewers);
  }
}
