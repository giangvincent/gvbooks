<?php

namespace Drupal\gvquest_streaks\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the StreakRule config entity.
 *
 * @ConfigEntityType(
 *   id = "streak_rule",
 *   label = @Translation("Streak rule"),
 *   label_collection = @Translation("Streak rules"),
 *   config_prefix = "streak_rule",
 *   admin_permission = "administer streak rules",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "user_id",
 *     "daily_target_type",
 *     "daily_target_value",
 *     "grace_days",
 *     "timezone",
 *     "start_date",
 *     "active"
 *   }
 * )
 */
class StreakRule extends ConfigEntityBase {

  /**
   * The unique ID.
   *
   * @var string
   */
  protected $id;

  /**
   * Label.
   *
   * @var string
   */
  protected $label;

  /**
   * Associated user ID.
   *
   * @var int
   */
  protected $user_id;

  /**
   * Target type.
   *
   * @var string
   */
  protected $daily_target_type = 'pages';

  /**
   * Target value.
   *
   * @var float
   */
  protected $daily_target_value = 0;

  /**
   * Grace days.
   *
   * @var int
   */
  protected $grace_days = 0;

  /**
   * User timezone.
   *
   * @var string
   */
  protected $timezone = 'UTC';

  /**
   * Start date for streak evaluation.
   *
   * @var string
   */
  protected $start_date;

  /**
   * Whether the streak is active.
   *
   * @var bool
   */
  protected $active = TRUE;

  /**
   * {@inheritdoc}
   */
  public function getUserId(): int {
    return (int) $this->user_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setUserId(int $uid): self {
    $this->user_id = $uid;
    if (!$this->label) {
      $this->label = $this->t('Streak rule for user @uid', ['@uid' => $uid]);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetType(): string {
    return $this->daily_target_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetValue(): float {
    return (float) $this->daily_target_value;
  }

  /**
   * {@inheritdoc}
   */
  public function getGraceDays(): int {
    return (int) $this->grace_days;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimezone(): string {
    return $this->timezone ?: 'UTC';
  }

  /**
   * {@inheritdoc}
   */
  public function getStartDate(): ?string {
    return $this->start_date;
  }

  /**
   * {@inheritdoc}
   */
  public function isActive(): bool {
    return (bool) $this->active;
  }

}
