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
 *   id = "mt_review_vbo_assign_abstracts",
 *   label = @Translation("Assign abstracts to reviewers"),
 *   type = "node",
 *   confirm = TRUE,
 *   requirements = {
 *     "_permission" = "assign abstracts to reviewers",
 *     "_custom_access" = FALSE,
 *   },
 * )
 */

class AssignAbstracts extends ViewsBulkOperationsActionBase implements PluginFormInterface {

  use StringTranslationTrait;

  protected $CONFIG_KEY_REVIEWERS = 'select_reviewers';
  protected $FIELD_KEY_ABSTRACT_REVIEWERS = 'field_mt_abstract_reviewers';
  protected $USER_ROLE_NAME_REVIEWER = 'Reviewer';
  protected $USER_FIELD_KEY_FIRST_NAME = 'field_mt_first_name';
  protected $USER_FIELD_KEY_LAST_NAME = 'field_mt_last_name';

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[$this->CONFIG_KEY_REVIEWERS] = [
      '#type' => 'select',
      '#title' => $this->t('Reviewers'),
      '#description' => $this->t('Select the reviewers you want to assign to the abstracts selected in the previous step.'),
      '#required' => TRUE,
      '#multiple' => TRUE,
      '#options' => $this->getReviewersOptions(),
    ];
    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $selected_reviewers = $this->configuration[$this->CONFIG_KEY_REVIEWERS];

    // Get cardinality of field_mt_abstract_reviewers
    $abstract_fields = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions('node', 'mt_abstract');
    $reviewers_limit = $abstract_fields[$this->FIELD_KEY_ABSTRACT_REVIEWERS]->getCardinality();

    $current_abstract_reviewers = $entity->get($this->FIELD_KEY_ABSTRACT_REVIEWERS);
    $current_abstract_reviewers_count = count($current_abstract_reviewers);
    foreach ($selected_reviewers as &$reviewer_id) {
      $reviewer = $this->getReviewers()[$reviewer_id]->get('name')->value;
      if ($current_abstract_reviewers_count >= $reviewers_limit
          || $this->reviewerExists($reviewer_id, $current_abstract_reviewers->getValue())) {
        // Collect abstracts failed to be assigned to reviewers
        // $this->context['results']['failed_entities'] = $this->context['results']['failed_entities'] ? $this->context['results']['failed_entities'] : [];
        // array_push($this->context['results']['failed_entities'], $entity);
        \Drupal::messenger()->addMessage($this->t('"' . $entity->get('title')->value
          . '" could not be assigned to reviewer ' . $reviewer
          . '. <a href=":url">Check</a> if it reached the maximum number of reviewers or the reviewer you selected is already assigned.', [
            ':url' => $entity->toUrl()->toString(),
          ]), 'error');
      } else {
        $entity->get($this->FIELD_KEY_ABSTRACT_REVIEWERS)->appendItem([
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
      ->condition('roles', $this->USER_ROLE_NAME_REVIEWER, 'CONTAINS')
      ->condition('status', 1)
      ->execute();
    $users = \Drupal\user\Entity\User::loadMultiple($user_ids);
    return $users;
  }

  private function getReviewersOptions() {
    $extract_user_full_name = function($user) {
      $first_name = $user->get($this->USER_FIELD_KEY_FIRST_NAME)->value;
      $last_name = $user->get($this->USER_FIELD_KEY_LAST_NAME)->value;
      $full_name = $first_name ? $first_name . ' ' . $last_name : $last_name;
      $full_name = trim($full_name);
      $username = $user->get('name')->value;
      return $full_name ? $full_name . '(' . $username . ')' : $username;
    };
    $user_full_names = array_map($extract_user_full_name, $this->getReviewers());
    return $user_full_names;
  }

  private function reviewerExists($reviewer_id, $abstract_reviewers) {
    $abstract_reviewer_id = function($reviewer) {
      return $reviewer['target_id'];
    };
    $flat_abstract_reviewers = array_map($abstract_reviewer_id, $abstract_reviewers);
    return in_array($reviewer_id ,$flat_abstract_reviewers);
  }
}
