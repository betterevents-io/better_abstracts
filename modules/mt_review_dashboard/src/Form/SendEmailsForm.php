<?php

namespace Drupal\mt_review_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form definition for sending email invitations.
 */
class SendEmailsForm extends FormBase
{
  protected $FIELD_KEY_EMAIL_TEMPLATE = 'email_template';
  protected $FIELD_KEY_AUDIENCE = 'audience';
  protected $FIELD_KEY_SELECTED_REVIEWERS = 'selected_reviewers';
  protected $form_id = 'send_emails_form';

  public function __construct() {
    $this->email_templates = $this->getEmailTemplates();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return $this->form_id;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $form_key = 'mt_event_email_invitations';
    $template_options = $this->getEmailTemplateOptions();

    $form[$form_key] = array(
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => 'Send Emails',
      '#description' => $this->t('Select the email template and its recipients and hit the "Send emails" button.'),
    );

    $form[$form_key][$this->FIELD_KEY_EMAIL_TEMPLATE] = array(
      '#type' => 'select',
      '#title' => $this->t('Email template'),
      '#description' => $this->t('Select the template that is going to be used for the invitation.'),
      '#required' => TRUE,
      '#options' => $template_options,
    );

    $form[$form_key][$this->FIELD_KEY_AUDIENCE] = array(
      '#type' => 'select',
      '#title' => $this->t('Audience'),
      '#description' => $this->t('Select the audience you are sending this email to.'),
      '#required' => TRUE,
      '#options' => [
        'all_reviewers' => $this
          ->t('All reviewers'),
        'selected_reviewers' => $this
          ->t('Selected reviewer(s)'),
      ],
      '#attributes' => [
        'id' => 'field_select_audience',
      ],
    );

    $form[$form_key][$this->FIELD_KEY_SELECTED_REVIEWERS] = [
      '#type' => 'select',
      '#title' => $this->t('Select reviewers'),
      '#attributes' => [
        'id' => $this->FIELD_KEY_SELECTED_REVIEWERS,
      ],
      '#states' => [
        //show this field only if the radio 'selected_reviewers' is selected above
        'visible' => [
          // don't mistake :input for the type of field. You'll always use
          // :input here, no matter whether your source is a select, radio or checkbox element.
          ':input[id="field_select_audience"]' => ['value' => 'selected_reviewers'],
        ],
      ],
      '#multiple' => TRUE,
      '#options' => [
        '1' => 'Sofia Atsalou',
        '2' => 'Stavros Kounis',
      ],
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Send emails'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    // Log result.
    foreach ($form_state->getValues() as $key => $value) {
      \Drupal::logger('mt_review_dashboard')->notice('Value of key ' . $key . ': ' . $value);
    }
//    $status = $form_state->getValue($this->FIELD_KEY_AUDIENCE);
//    $email_template_nid = $form_state->getValue($this->FIELD_KEY_EMAIL_TEMPLATE);
//    $email_template_nid = $form_state->getValue($this->FIELD_KEY_SELECTED_REVIEWERS);
//    $email_template = $this->email_templates[$email_template_nid];
//    $entity_type = 'node';
//    $query = \Drupal::entityTypeManager()->getStorage($entity_type)->getQuery();
//    $query
//      ->condition('type', 'mt_invitation_recipient')
//      ->condition('field_mt_inv_rec_status', $status)
//      ->condition('field_mt_inv_rec_is_tester', $send_to_testers)
//      ->condition('status', 1);
//    $nids = $query->execute();
//    $invitations = \Drupal::entityTypeManager()->getStorage($entity_type)->loadMultiple($nids);
//    foreach ($invitations as $key => $invitation) {
//      $invitation->set('field_mt_inv_rec_send_email', 1);
//      $invitation->save();
//      $this->send_email($invitation, $email_template);
//    }
  }

  private function send_email($invitation_recipient, $email_template) {
    $mailManager = \Drupal::service('plugin.manager.mail');
    $module = 'mt_event_email_invitations';
    $key = 'invitation';
    $to = $invitation_recipient->get('title')->value;
    $params['subject'] = $email_template->get('field_mt_email_subject')->value;
    $params['body'] = $email_template->get('body')->value;
    $template_banner = $email_template->get('field_mt_email_banner');
    // Use the Drupal::config() static method to get the configuration
    // with ID "mt_event_email_invitations.settings"
    $config = \Drupal::config('mt_event_email_invitations.settings');
    $config_banner = $config->get('banner');
    $config_banner_url = '';
    if ($config_banner) {
      $config_banner = \Drupal\file\Entity\File::load($config_banner[0]);
      $config_banner_url = file_create_url($config_banner->uri->value);
    }
    $params['banner'] = $template_banner->isEmpty() ? $config_banner_url : $template_banner;
    $params['recipient'] = $invitation_recipient;
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $send = true;

    $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
    if ($result['result'] != true) {
      $message = t('There was a problem sending the email invitation to @email.', array('@email' => $to));
      \Drupal::logger('mt_event_email_invitations')->error($message);
      return;
    }

    $invitation_recipient->set('field_mt_inv_rec_send_email', 0);
    if ($email_template->get('field_mt_email_purpose')->value == 'invitation') {
      $invitation_recipient->field_mt_inv_rec_status->value = 'pending';
      $invitation_recipient->save();
    }
    $message = t('An email has been sent to @email ', array('@email' => $to));
    \Drupal::logger('mt_event_email_invitations')->notice($message);
  }

  private function getEmailTemplates() {
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'mt_email_template')
      ->condition('status', 1)
      ->execute();
    $nodes = \Drupal\node\Entity\Node::loadMultiple($nids);
    return $nodes;
  }

  private function getEmailTemplateOptions() {
    $extract_node_title = function($node) {
      $title = $node->get('title')->value;
      return $title;
    };
    $node_titles = array_map($extract_node_title, $this->email_templates);
    return $node_titles;
  }
}
