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
      '#title' => 'Access Token',
      '#prefix' => '<div class="clearfix">',
      '#suffix' => '</div>'
    );
    $form['test-api-accesstoken']['operations'] = array(
      '#type' => 'container',
      '#prefix' => '<div class="layout-column layout-column--half">',
      '#suffix' => '</div>'
    );
    $form['test-api-accesstoken']['container']['submit-access-token'] = array(
      '#type' => 'submit',
      '#name' => "submit-access-token",
      '#value' => "Send Request",
      '#submit' => array([$this, 'submitFormAccessToken'])
    );
    $form['test-api-accesstoken']['results'] = array(
      '#type' => 'container',
      '#prefix' => '<div class="layout-column layout-column--half">',
      '#suffix' => '</div>'
    );

    $form['test-api-getCalendar'] = array(
      '#type' => 'fieldset',
      '#title' => 'Get Calendars',
      '#prefix' => '<div class="clearfix">',
      '#suffix' => '</div>'
    );
    $form['test-api-getCalendar']['operations'] = array(
      '#type' => 'container',
      '#prefix' => '<div class="layout-column layout-column--half">',
      '#suffix' => '</div>'
    );
    $form['test-api-getCalendar']['container']['submit-get-calendar'] = array(
      '#type' => 'submit',
      '#name' => "submit-test-get-calendar",
      '#value' => "Send Request",
      '#submit' => array([$this, 'submitFormCalendars'])
    );

    $form['test-api-getEvent'] = array(
      '#type' => 'fieldset',
      '#title' => 'Get Events',
      '#prefix' => '<div class="clearfix">',
      '#suffix' => '</div>'
    );
    $form['test-api-getEvent']['operations'] = array(
      '#type' => 'container',
      '#prefix' => '<div class="layout-column layout-column--half">',
      '#suffix' => '</div>'
    );
    $form['test-api-getEvent']['container']['submit-get-events'] = array(
      '#type' => 'submit',
      '#name' => "submit-test-get-events",
      '#value' => "Send Request",
      '#submit' => array([$this, 'submitFormEvent'])
    );

    return parent::buildForm($form, $form_state);
  }

  public function submitFormAccessToken(array &$form, FormStateInterface $form_state)
  {
    $service = \Drupal::service('libcal.download');
    $result = $service->postAccessToken();
    print_log($result);
  }

  public function submitFormCalendars(array &$form, FormStateInterface $form_state)
  {
    $service = \Drupal::service('libcal.download');
    $result = $service->get("calendars");
    print_log($result);
  }
  public function submitFormEvent(array &$form, FormStateInterface $form_state)
  {
    $service = \Drupal::service('libcal.download');
    $result = $service->get("events?cal_id=2020")->events;
    print_log($result);
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
