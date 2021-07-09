<?php

namespace Drupal\mt_review_dashboard\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;

class SettingsConfigurationForm extends ConfigFormBase
{
  protected $CONFIG_ID = 'mt_review_dashboard.settings';
  protected $CONFIG_KEY_DEADLINE = 'deadline';
  protected $CONFIG_KEY_FB_PAGE_ID = 'fb_page_id';
  protected $form_id = 'review_settings_configuration_form';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames()
  {
    return [
      $this->CONFIG_ID
    ];
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
    $config = $this->config($this->CONFIG_ID);
    $form_key = 'mt_review_settings';

    $form[$form_key]['general_information'] = array(
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => 'General Information',
      '#description' => $this->t('Set the general information.'),
    );

    // End date of the review
    $form[$form_key]['general_information'][$this->CONFIG_KEY_DEADLINE] = array(
      '#type' => 'datetime',
      '#title' => $this->t('Deadline'),
      '#required' => TRUE,
      '#description' => $this->t('The end date of the review'),
      '#default_value' => new DrupalDateTime($config->get($this->CONFIG_KEY_DEADLINE)),
    );

    // End date of the review
    $form[$form_key]['general_information'][$this->CONFIG_KEY_FB_PAGE_ID] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Facebook Chat'),
      '#required' => TRUE,
      '#description' => $this->t('The ID for the Facebook Page the chat is configured for.'),
      '#default_value' => '123456789012345',
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    // Save Configuration
    $this->config($this->CONFIG_ID)
      ->set( $this->CONFIG_KEY_DEADLINE, $form_state->getValue($this->CONFIG_KEY_DEADLINE)->__toString() )
      ->set( $this->CONFIG_KEY_FB_PAGE_ID, $form_state->getValue($this->CONFIG_KEY_FB_PAGE_ID) )
      ->save();

    parent::submitForm($form, $form_state);
  }
}
