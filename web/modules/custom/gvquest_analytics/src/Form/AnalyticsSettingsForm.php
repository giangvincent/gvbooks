<?php

namespace Drupal\gvquest_analytics\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Analytics configuration form.
 */
class AnalyticsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['gvquest_analytics.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gvquest_analytics_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('gvquest_analytics.settings');

    $form['telemetry_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable telemetry globally'),
      '#default_value' => $config->get('telemetry_enabled'),
    ];

    $form['default_user_opt_in'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Default users to opt-in'),
      '#default_value' => $config->get('default_user_opt_in'),
      '#description' => $this->t('When disabled, new accounts start with analytics disabled until they explicitly opt in.'),
    ];

    $form['debounce_seconds'] = [
      '#type' => 'number',
      '#title' => $this->t('Debounce window (seconds)'),
      '#default_value' => $config->get('debounce_seconds') ?? 5,
      '#min' => 0,
      '#description' => $this->t('Minimum seconds between events before they are recorded unless page progress is positive.'),
    ];

    $form['aggregation_lookback_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Aggregation lookback (days)'),
      '#default_value' => $config->get('aggregation_lookback_days') ?? 30,
      '#min' => 1,
      '#max' => 365,
      '#description' => $this->t('Number of days to scan when rebuilding daily aggregates.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->configFactory()->getEditable('gvquest_analytics.settings')
      ->set('telemetry_enabled', (bool) $form_state->getValue('telemetry_enabled'))
      ->set('default_user_opt_in', (bool) $form_state->getValue('default_user_opt_in'))
      ->set('debounce_seconds', (int) $form_state->getValue('debounce_seconds'))
      ->set('aggregation_lookback_days', (int) $form_state->getValue('aggregation_lookback_days'))
      ->save();
  }

}
