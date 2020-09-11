<?php

namespace Drupal\libcal\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class LibcalAPIConfigForm.
 */
class LibcalAPIConfigForm extends ConfigFormBase
{

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

    $form['api-config'] = array(
      '#type' => 'fieldset',
      '#title' => 'API Configuration'
    );
    $form['api-config']['libcal-api-key'] = array(
      '#type' => 'textfield',
      '#title' => $this
        ->t('Client ID:'),
      //'#required' => TRUE,
      '#default_value' => ($config->get("client_id") !== null) ? $config->get("client_id") : ""
    );
    $form['api-config']['libcal-secret'] = array(
      '#type' => 'textfield',
      '#title' => $this
        ->t('Client Secret:'),
      //'#required' => TRUE,
      '#default_value' => ($config->get("client_secret") !== null) ? $config->get("client_secret") : ""
    );


    $form['test-api-accesstoken'] = array(
      '#type' => 'fieldset',
      '#title' => 'Test Access Token',
      '#prefix' => '<div class="clearfix">',
      '#suffix' => '</div>'
    );
    $form['test-api-accesstoken']['operations'] = array(
      '#type' => 'container',
    );
    $form['test-api-accesstoken']['container']['submit-access-token'] = array(
      '#type' => 'submit',
      '#name' => "submit-access-token",
      '#value' => "Send Request",
      '#submit' => array([$this, 'submitFormAccessToken'])
    );
    $form['test-api-accesstoken']['results'] = array(
      '#type' => 'container',
    );
    $form['test-api-accesstoken']['results']['output'] = array(
      '#type' => 'textarea',
      '#title' => 'Result: ',
      '#default_value' => \Drupal::service('user.private_tempstore')->get('libcal.api.testing')->get('output_accesstoken')
    );

    $form['test-api-getCalendar'] = array(
      '#type' => 'fieldset',
      '#title' => 'Test Get Calendars',
      '#prefix' => '<div class="clearfix">',
      '#suffix' => '</div>'
    );
    $form['test-api-getCalendar']['operations'] = array(
      '#type' => 'container',
    );

    $form['test-api-getCalendar']['container']['submit-get-calendar'] = array(
      '#type' => 'submit',
      '#name' => "submit-test-get-calendar",
      '#value' => "Send Request",
      '#submit' => array([$this, 'submitFormCalendars'])
    );
    $form['test-api-getCalendar']['results'] = array(
      '#type' => 'container',
    );

    $form['test-api-getCalendar']['results']['output'] = array(
      '#type' => 'textarea',
      '#title' => 'Result: ',
      '#default_value' => \Drupal::service('user.private_tempstore')->get('libcal.api.testing')->get('output_calendar')
    );

    $form['test-api-getEvent'] = array(
      '#type' => 'fieldset',
      '#title' => 'Test - Manually Download Events from LibCal',
      '#prefix' => '<div class="clearfix">',
      '#suffix' => '</div>'
    );
    $form['test-api-getEvent']['operations'] = array(
      '#type' => 'container',
    );
    $form['test-api-getEvent']['container']['cal_id'] = array(
      '#type' => 'textfield',
      '#title' => "Calendar ID:",
      '#default_value' => \Drupal::service('user.private_tempstore')->get('libcal.api.testing')->get('testing-calid')
    );
    $form['test-api-getEvent']['container']['submit-get-events'] = array(
      '#type' => 'submit',
      '#name' => "submit-test-get-events",
      '#value' => "Send Request",
      '#submit' => array([$this, 'submitFormEvent'])
    );

    $form['test-api-getEvent']['results'] = array(
      '#type' => 'container',
    );
    $form['test-api-getEvent']['results']['output'] = array(
      '#type' => 'textarea',
      '#title' => 'Result: ',
      '#default_value' => \Drupal::service('user.private_tempstore')->get('libcal.api.testing')->get('output_events')
    );


    return parent::buildForm($form, $form_state);
  }

  public function submitFormAccessToken(array &$form, FormStateInterface $form_state)
  {
    $service = \Drupal::service('libcal.download');
    $result = $service->postAccessToken();

    $tempstore = \Drupal::service('user.private_tempstore')->get('libcal.api.testing');
    $tempstore->set('output_accesstoken', print_r($result, true));
  }

  public function submitFormCalendars(array &$form, FormStateInterface $form_state)
  {
    $service = \Drupal::service('libcal.download');
    $result = $service->get("calendars");

    $tempstore = \Drupal::service('user.private_tempstore')->get('libcal.api.testing');
    $tempstore->set('output_calendar', print_r($result, true));
  }

  public function submitFormEvent(array &$form, FormStateInterface $form_state)
  {

    $service = \Drupal::service('libcal.download');
    $result = $service->get("events?cal_id=" . $form_state->getValues()['cal_id'])->events;

    $tempstore = \Drupal::service('user.private_tempstore')->get('libcal.api.testing');
    $tempstore->set('output_events', print_r($result, true));
    $tempstore->set('testing-calid', $form_state->getValues()['cal_id']);

    // process event data to Event nodes
    $service->libcalEventToNode($result);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $configFactory = $this->configFactory->getEditable('libcal.libcalapiconfig');
    $configFactory->set('client_id', $form_state->getValues()['libcal-api-key'])
      ->set('client_secret', $form_state->getValues()['libcal-secret']);
    $configFactory->save();
    parent::submitForm($form, $form_state);
  }

}
