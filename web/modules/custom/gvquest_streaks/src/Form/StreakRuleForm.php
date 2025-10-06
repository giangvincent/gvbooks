<?php

namespace Drupal\gvquest_streaks\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for streak defaults.
 */
class StreakRuleForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['gvquest_streaks.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gvquest_streaks_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('gvquest_streaks.settings');

    $form['daily_target_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Default target type'),
      '#options' => [
        'pages' => $this->t('Pages'),
        'minutes' => $this->t('Minutes'),
        'percent' => $this->t('Percent complete'),
      ],
      '#default_value' => $config->get('daily_target_type') ?: 'pages',
    ];

    $form['daily_target_value'] = [
      '#type' => 'number',
      '#title' => $this->t('Default daily target value'),
      '#default_value' => $config->get('daily_target_value') ?? 10,
      '#min' => 0,
      '#step' => 1,
      '#description' => $this->t('Number of pages, minutes, or percent required per day.'),
    ];

    $form['grace_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Grace days'),
      '#default_value' => $config->get('grace_days') ?? 0,
      '#min' => 0,
      '#step' => 1,
      '#description' => $this->t('How many misses may be ignored before a streak resets.'),
    ];

    $form['timezone'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default timezone'),
      '#default_value' => $config->get('timezone') ?: date_default_timezone_get(),
      '#description' => $this->t('IANA timezone (e.g. America/New_York). Users can override this in their streak rule.'),
    ];

    $form['start_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Default streak start date'),
      '#default_value' => $config->get('start_date'),
      '#description' => $this->t('Optional. Evaluate streaks from this date onwards when no user-specific rule exists.'),
    ];

    $form['email_reminder_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable daily reminder emails (placeholder)'),
      '#default_value' => (bool) $config->get('email_reminder_enabled'),
    ];

    $form['email_reminder_time'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Reminder send time'),
      '#default_value' => $config->get('email_reminder_time') ?: '18:00',
      '#description' => $this->t('24-hour format HH:MM in default timezone.'),
      '#states' => [
        'visible' => [
          ':input[name="email_reminder_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->configFactory()->getEditable('gvquest_streaks.settings')
      ->set('daily_target_type', $form_state->getValue('daily_target_type'))
      ->set('daily_target_value', (float) $form_state->getValue('daily_target_value'))
      ->set('grace_days', (int) $form_state->getValue('grace_days'))
      ->set('timezone', $form_state->getValue('timezone'))
      ->set('start_date', $form_state->getValue('start_date'))
      ->set('email_reminder_enabled', (bool) $form_state->getValue('email_reminder_enabled'))
      ->set('email_reminder_time', $form_state->getValue('email_reminder_time'))
      ->save();
  }

}
