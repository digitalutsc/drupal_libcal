<?php

namespace Drupal\libcal\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class LibcalAPIConfigForm.
 */
class LibcalAPIConfigForm extends ConfigFormBase
{
  private $noTags = 1;

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames()
  {
    return [
      'libcal.libcalapiconfig',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'libcal_api_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $config = $this->config('libcal.libcalapiconfig');

    $form['container'] = array(
      '#type' => 'container',
      '#prefix' => $this->t('<div class="clearfix">'),
      '#suffix' => $this->t('</div>')
    );

    $form['container']['api-config'] = array(
      '#type' => 'fieldset',
      '#title' => 'API Configuration',
      '#attributes' => ['class' => ['layout-column layout-column--half'], 'style' => "width:45% !important"],
    );

    $form['container']['api-config']['libcal-host'] = array(
      '#type' => 'textfield',
      '#title' => $this
        ->t('LibCal Host:'),
      '#required' => TRUE,
      '#default_value' => ($config->get("host") !== null) ? $config->get("host") : ""
    );

    $form['container']['api-config']['libcal-client-id'] = array(
      '#type' => 'textfield',
      '#title' => $this
        ->t('Client ID:'),
      '#required' => TRUE,
      '#pattern' => '^\d+$',
      '#default_value' => ($config->get("client_id") !== null) ? $config->get("client_id") : ""
    );

    $form['container']['api-config']['libcal-secret'] = array(
      '#type' => 'textfield',
      '#title' => $this
        ->t('Client Secret:'),
      '#required' => TRUE,
      '#default_value' => ($config->get("client_secret") !== null) ? $config->get("client_secret") : ""
    );

    $form['container']['api-config']['libcal-calid'] = array(
      '#type' => 'textfield',
      '#title' => $this
        ->t('Calendar ID(s):'),
      '#required' => TRUE,
      '#description' => $this->t('<p>Separate multiple calendar IDs with a comma ",".</p>'),
      '#pattern' => '^\d+(,\d+)*$',
      '#default_value' => ($config->get("calendar-id") !== null) ? $config->get("calendar-id") : ""
    );

    $form['container']['api-config']['libcal-tags'] = array(
      '#type' => 'textfield',
      '#title' => $this
        ->t('Tag ID(s):'),
      '#description' => $this->t('<p>Separate multiple tag IDs with a comma ",".</p>'),
      '#pattern' => '^\d+(,\d+)*$',
      '#default_value' => ($config->get("tags") !== null) ? $config->get("tags") : ""
    );

    $form['container']['api-config']['libcal-limit'] = array(
      '#type' => 'number',
      '#title' => $this->t('Limit:'),
      '#description' => $this->t('<p>The maximum number of events to retrieve (default: 20 [Range 1-500])</p>'),
      '#min' => 1,
      '#max' => 500,
      '#default_value' => ($config->get("limit") !== null) ? $config->get("limit") : 20
    );

    $form['container']['api-config']['libcal-days'] = array(
      '#type' => 'number',
      '#title' => $this->t('Days:'),
      '#description' => $this->t('<p>The number of days into the future to retrieve events from (default: 30 [Range 0-365])</p>'),
      '#min' => 0,
      '#max' => 365,
      '#default_value' => ($config->get("days") !== null) ? $config->get("days") : 30
    );

    $form['container']['api-config']['libcal-remove-past-events'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Remove past events?'),
      '#description' => $this->t('<p>Check this box if you want events to be removed once they have passed.</p>'),
      '#default_value' => ($config->get("remove-past-events") !== null) ? $config->get("remove-past-events") : true
    );

    $form['container']['api-config']['submit-save-config'] = array(
      '#type' => 'submit',
      '#name' => "submit-save-config",
      '#value' => "Save",
      '#attributes' => ['class' => ["button button--primary"]],
      '#submit' => array([$this, 'submitForm'])
    );

    $form['container']['manually'] = array(
      '#type' => 'fieldset',
      '#title' => 'Manually Download Events',
      '#attributes' => ['class' => ['layout-column layout-column--half'], 'style' => "left: 10px !important"],
    );

    $form['container']['manually']['description'] = array(
      '#markup' => $this->t('<p>Download Events process will be run when the <a href="admin/config/system/cron">scheduled cron</a> runs. However, it can be run immediately by clicking the Download button below.</p>')
    );

    $form['container']['manually']['submit-manually-download-events'] = array(
      '#type' => 'submit',
      '#name' => "submit-manually-download",
      '#value' => "Download",
      '#attributes' => ['class' => ["button button--primary"]],
      '#submit' => array([$this, 'submitFormManuallyDownloadEvents'])
    );

    return $form;
    //return parent::buildForm($form, $form_state);
  }

  /**
   * Submit handler manually download event from LibCal
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function submitFormManuallyDownloadEvents(array &$form, FormStateInterface $form_state)
  {
    startDownloadLibCalEvents();

    $messenger = \Drupal::messenger();
    $messenger->addMessage('Successfully downloaded events.');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $configFactory = $this->configFactory->getEditable('libcal.libcalapiconfig');
    $configFactory
      ->set('host', $form_state->getValues()['libcal-host'])
      ->set('client_id', $form_state->getValues()['libcal-client-id'])
      ->set('client_secret', $form_state->getValues()['libcal-secret'])
      ->set('calendar-id', $form_state->getValues()['libcal-calid'])
      ->set('tags', $form_state->getValues()['libcal-tags'])
      ->set('limit', $form_state->getValues()['libcal-limit'])
      ->set('days', $form_state->getValues()['libcal-days'])
      ->set('remove-past-events', $form_state->getValues()['libcal-remove-past-events'])
      ->save();

    parent::submitForm($form, $form_state);
  }
}
