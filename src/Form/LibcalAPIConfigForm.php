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

        $form['container'] = array(
            '#type' => 'container',
            '#prefix' => $this->t('<div class="clearfix">'),
            '#suffix' => $this->t('</div><hr><p><h1>Testing LibCal API Purposes</h1></p>')
        );

        $form['container']['api-config'] = array(
            '#type' => 'fieldset',
            '#title' => 'API Configuration',
            '#attributes' => ['class' => ['layout-column layout-column--half'], 'style' => "width:45% !important"],
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
        $form['container']['api-config']['submit-save-config'] = array(
            '#type' => 'submit',
            '#name' => "submit-save-config",
            '#value' => "Save",
            '#attributes' => ['class'=> ["button button--primary"]],
            '#submit' => array([$this, 'submitForm'])
        );

        $form['container']['manually'] = array(
            '#type' => 'fieldset',
            '#title' => 'Manually Download Events',
            '#attributes' => ['class' => ['layout-column layout-column--half'], 'style' => "left: 10px !important"],
        );
        $form['container']['manually']['description'] = array(
            '#markup' => $this->t('<p>Currently set download events from LibCal (UTSC Calendar Only) automatically in cron job at 8a.m daily. If there are updated events which need to be downloaded immediately, please click the below button, it will do exactly same job.</p>')
        );

        $form['container']['manually']['submit-manually-download-events'] = array(
            '#type' => 'submit',
            '#name' => "submit-manually-download",
            '#value' => "Download",
            '#attributes' => ['class'=> ["button button--primary"]],
            '#submit' => array([$this, 'submitFormManuallyDownloadEvents'])
        );

        $form['reset'] = array(
            '#type' => 'submit',
            '#name' => "submit-reset",
            '#value' => "Reset",
            '#submit' => array([$this, 'submitFormReset'])
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
            '#default_value' => \Drupal::service('tempstore.private')->get('libcal.api.testing')->get('output_accesstoken')
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
            '#default_value' => \Drupal::service('tempstore.private')->get('libcal.api.testing')->get('output_calendar')
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
            '#title' => "Search Params:",
            '#default_value' => (!empty(\Drupal::service('tempstore.private')->get('libcal.api.testing')->get('testing-calid'))) ? \Drupal::service('tempstore.private')->get('libcal.api.testing')->get('testing-calid') : LIBCAL_DSU_SEARCH,
            '#description' => "Default: ". LIBCAL_DSU_SEARCH
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
            '#default_value' => \Drupal::service('tempstore.private')->get('libcal.api.testing')->get('output_events')
        );

        return $form;
        //return parent::buildForm($form, $form_state);
    }

    /**
     * Submit handler clear session variables
     * @param array $form
     * @param FormStateInterface $form_state
     */
    public function submitFormReset(array &$form, FormStateInterface $form_state) {
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
    public function submitFormManuallyDownloadEvents(array &$form, FormStateInterface $form_state) {
        $service = \Drupal::service('libcal.download');
        $result = $service->get(LIBCAL_DSU_SEARCH)->events;
        //$result = $service->get("events?cal_id=2020")->events;

        // process event data to Event nodes
        $service->libcalEventToNode($result);

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
        $configFactory->set('client_id', $form_state->getValues()['libcal-api-key'])
            ->set('client_secret', $form_state->getValues()['libcal-secret']);
        $configFactory->save();
        parent::submitForm($form, $form_state);
    }

}
