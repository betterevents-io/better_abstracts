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
 *   id = "mt_review_vbo_clear_assignments",
 *   label = @Translation("Clear assignments"),
 *   type = "node",
 *   confirm = TRUE,
 *   requirements = {
 *     "_permission" = "assign abstracts to reviewers",
 *     "_custom_access" = FALSE,
 *   },
 * )
 */

class ClearAssignments extends ViewsBulkOperationsActionBase {

  use StringTranslationTrait;

  protected $FIELD_KEY_ABSTRACT_REVIEWERS = 'field_mt_abstract_reviewers';

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->get($this->FIELD_KEY_ABSTRACT_REVIEWERS)->setValue(array());
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\node\NodeInterface $object */
    return $object->access('update', $account);
  }
}
