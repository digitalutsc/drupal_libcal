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

    $form['container']['api-config']['libcal-api-key'] = array(
      '#type' => 'textfield',
      '#title' => $this
        ->t('Client ID:'),
      '#required' => TRUE,
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
      '#description' => $this->t('<p>To ignore, please enter -1. <br />For multiple calendar IDs, please use "," to seperate them</p>'),
      '#default_value' => ($config->get("calendar-id") !== null) ? $config->get("calendar-id") : ""
    );

    $form['container']['api-config']['libcal-tags'] = array(
      '#type' => 'textfield',
      '#title' => $this
        ->t('Tag ID(s):'),
      '#required' => TRUE,
      '#description' => $this->t('<p>To ignore, please enter -1. <br />For multiple calendar tag IDs, please use "," to seperate them</p>'),
      '#default_value' => ($config->get("tags") !== null) ? $config->get("tags") : ""
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
      '#markup' => $this->t('<p>Download Events process will be run when the <a href="admin/config/system/cron">scheduled cron</a> run. However, it can be run immediately by clicking the Download button below.</p>')
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
   * Submit handler clear session variables
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function submitFormReset(array &$form, FormStateInterface $form_state)
  {
    $tempstore = \Drupal::service('tempstore.private')->get('libcal.api.testing');
    $tempstore->set('output_accesstoken', print_r("", true));
    $tempstore->set('output_calendar', print_r("", true));
    $tempstore->set('output_events', print_r("", true));
    $tempstore->set('testing-calid', "");
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
    $messenger->addMessage('Successfully download UTSC events from <a href="https://libcal.library.utoronto.ca/">https://libcal.library.utoronto.ca</a>');
  }

  /**
   * Submit handler Post request to obtain access token
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function submitFormAccessToken(array &$form, FormStateInterface $form_state)
  {
    $service = \Drupal::service('libcal.download');
    $result = $service->postAccessToken();

    $tempstore = \Drupal::service('tempstore.private')->get('libcal.api.testing');
    $tempstore->set('output_accesstoken', print_r($result, true));
  }

  /**
   * Submit handler download Calendar data
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function submitFormCalendars(array &$form, FormStateInterface $form_state)
  {
    $service = \Drupal::service('libcal.download');
    $result = $service->get("calendars");

    $tempstore = \Drupal::service('tempstore.private')->get('libcal.api.testing');
    $tempstore->set('output_calendar', print_r($result, true));
  }

  /**
   * Submit handler download Event data
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function submitFormEvent(array &$form, FormStateInterface $form_state)
  {

    $service = \Drupal::service('libcal.download');
    $result = $service->get($form_state->getValues()['cal_id'])->events;
    $tempstore = \Drupal::service('tempstore.private')->get('libcal.api.testing');
    $tempstore->set('output_events', print_r($result, true));
    $tempstore->set('testing-calid', $form_state->getValues()['cal_id']);

    // process event data to Event nodes
    //$service->libcalEventToNode($result);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $configFactory = $this->configFactory->getEditable('libcal.libcalapiconfig');
    $configFactory
      ->set('host', $form_state->getValues()['libcal-host'])
      ->set('client_id', $form_state->getValues()['libcal-api-key'])
      ->set('client_secret', $form_state->getValues()['libcal-secret'])
      ->set('calendar-id', $form_state->getValues()['libcal-calid']);


    $configFactory->set('tags', $form_state->getValues()['libcal-tags']);

    $configFactory->save();
    parent::submitForm($form, $form_state);
  }

}
