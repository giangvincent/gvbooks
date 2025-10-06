<?php

namespace Drupal\gvquest_streaks\Service;

use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use DateTimeZone;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\gvquest_streaks\Entity\StreakRule;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for computing streak metrics per user.
 */
class StreakCalculator {

  /**
   * Core services.
   */
  protected EntityTypeManagerInterface $entityTypeManager;
  protected ConfigFactoryInterface $configFactory;
  protected Connection $database;
  protected TimeInterface $time;
  protected LoggerInterface $logger;

  /**
   * Number of days to analyze when computing longest streak.
   */
  protected const MAX_LOOKBACK_DAYS = 365;

  /**
   * Cache table name.
   */
  protected const CACHE_TABLE = 'gvquest_streak_cache';

  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, Connection $database, TimeInterface $time, LoggerInterface $logger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->database = $database;
    $this->time = $time;
    $this->logger = $logger;
  }

  /**
   * Return the cached metrics if available.
   */
  public function getCachedMetrics(int $uid): ?array {
    $query = $this->database->select(self::CACHE_TABLE, 'c')
      ->fields('c')
      ->condition('uid', $uid)
      ->range(0, 1);
    $result = $query->execute()->fetchAssoc();
    if (!$result) {
      return NULL;
    }
    return [
      'current_streak' => (int) $result['current_streak'],
      'longest_streak' => (int) $result['longest_streak'],
      'missed_days_in_window' => (int) $result['missed_days_window'],
      'last_active_date' => $result['last_active'] ?: NULL,
      'updated' => (int) $result['updated'],
    ];
  }

  /**
   * Compute and store streak metrics for a user.
   */
  public function updateCache(int $uid, ?array $precomputed = NULL): array {
    $metrics = $precomputed ?? $this->evaluate($uid);
    $this->database->merge(self::CACHE_TABLE)
      ->key(['uid' => $uid])
      ->fields([
        'current_streak' => $metrics['current_streak'],
        'longest_streak' => $metrics['longest_streak'],
        'missed_days_window' => $metrics['missed_days_in_window'],
        'last_active' => $metrics['last_active_date'],
        'updated' => $this->time->getRequestTime(),
      ])
      ->execute();
    return $metrics;
  }

  /**
   * Run streak evaluation without persisting.
   */
  public function evaluate(int $uid): array {
    $account = $this->loadUser($uid);
    if (!$account) {
      return $this->emptyMetrics();
    }

    $rule = $this->loadRule($uid);
    if (isset($rule['active']) && !$rule['active']) {
      return $this->emptyMetrics();
    }
    $timezone = $this->resolveTimezone($account, $rule);
    $date_format = 'Y-m-d';

    $results = $this->loadLogsForUser($uid);
    if (empty($results)) {
      return $this->emptyMetrics($timezone);
    }
    $successByDay = [];
    $lastActive = NULL;

    foreach ($results as $log) {
      $logDate = $this->convertToUserDate($log['date'], $timezone, $date_format);
      $meetsTarget = $this->logMeetsTarget($log, $rule);
      if ($meetsTarget) {
        $successByDay[$logDate] = TRUE;
        if ($lastActive === NULL || $logDate > $lastActive) {
          $lastActive = $logDate;
        }
      }
      elseif (!isset($successByDay[$logDate])) {
        $successByDay[$logDate] = FALSE;
      }
    }

    if (empty($successByDay)) {
      return $this->emptyMetrics($timezone);
    }

    $today = new DateTimeImmutable('now', new DateTimeZone($timezone));
    $startDate = $this->resolveStartDate($rule, $results, $timezone);

    $grace = max(0, (int) $rule['grace_days']);
    $currentRun = 0;
    $longest = 0;
    $rollingRun = 0;
    $graceCounter = $grace;
    $missedLastThirty = 0;

    $heatmap = [];

    $periodEnd = $today;
    $periodStart = $today->sub(new DateInterval('P' . (self::MAX_LOOKBACK_DAYS - 1) . 'D'));
    if ($startDate && $periodStart < $startDate) {
      $periodStart = $startDate;
    }

    $period = new DatePeriod($periodStart, new DateInterval('P1D'), $periodEnd->add(new DateInterval('P1D')));

    foreach ($period as $day) {
      $dayKey = $day->format($date_format);
      $isSuccess = !empty($successByDay[$dayKey]);
      $heatmap[$dayKey] = $isSuccess;

      if ($isSuccess) {
        $rollingRun++;
        $graceCounter = $grace;
      }
      else {
        if ($graceCounter > 0) {
          $graceCounter--;
          $rollingRun++;
        }
        else {
          $longest = max($longest, $rollingRun);
          $rollingRun = 0;
          $graceCounter = $grace;
        }
      }

      if ($day >= $today->sub(new DateInterval('P29D')) && !$isSuccess) {
        $missedLastThirty++;
      }
    }

    $longest = max($longest, $rollingRun);
    $currentRun = $rollingRun;

    $metrics = [
      'current_streak' => $currentRun,
      'longest_streak' => $longest,
      'last_active_date' => $lastActive,
      'missed_days_in_window' => $missedLastThirty,
      'heatmap' => array_slice($heatmap, -30, 30, TRUE),
    ];

    return $metrics + ['rule' => $rule];
  }

  /**
   * Resolve user timezone.
   */
  protected function resolveTimezone(UserInterface $account, array $rule): string {
    $tz = $rule['timezone'] ?? '';
    if ($tz) {
      return $tz;
    }
    $userTz = $account->getTimezone();
    if ($userTz) {
      return $userTz;
    }
    return $this->configFactory->get('system.date')->get('timezone.default') ?: 'UTC';
  }

  protected function resolveStartDate(array $rule, array $logs, string $timezone): ?DateTimeImmutable {
    $format = 'Y-m-d';
    if (!empty($rule['start_date'])) {
      return new DateTimeImmutable($rule['start_date'], new DateTimeZone($timezone));
    }
    if (!empty($logs)) {
      $first = reset($logs);
      $dt = DateTimeImmutable::createFromFormat('Y-m-d', substr($first['date'], 0, 10), new DateTimeZone($timezone));
      return $dt ?: NULL;
    }
    return NULL;
  }

  protected function convertToUserDate(string $storedDate, string $timezone, string $format): string {
    $dateString = substr($storedDate, 0, 10);
    $dt = DateTimeImmutable::createFromFormat('Y-m-d', $dateString, new DateTimeZone($timezone));
    if (!$dt) {
      $dt = new DateTimeImmutable('now', new DateTimeZone($timezone));
    }
    return $dt->format($format);
  }

  protected function logMeetsTarget(array $log, array $rule): bool {
    $value = 0;
    switch ($rule['daily_target_type']) {
      case 'minutes':
        $value = (int) $log['minutes_read'];
        break;

      case 'percent':
        $value = (float) $log['percent_complete'];
        break;

      case 'pages':
      default:
        $value = (int) $log['pages_read'];
        break;
    }
    return $value >= (float) $rule['daily_target_value'];
  }

  protected function loadLogsForUser(int $uid): array {
    $storage = $this->entityTypeManager->getStorage('reading_log');
    $query = $storage->getQuery()
      ->condition('user_id', $uid)
      ->sort('log_date', 'ASC');
    $ids = $query->execute();
    if (!$ids) {
      return [];
    }
    $logs = [];
    /** @var \Drupal\gvquest_streaks\Entity\ReadingLog $entity */
    foreach ($storage->loadMultiple($ids) as $entity) {
      $logs[] = [
        'date' => $entity->get('log_date')->value,
        'pages_read' => (int) $entity->get('pages_read')->value,
        'minutes_read' => (int) $entity->get('minutes_read')->value,
        'percent_complete' => (float) $entity->get('percent_complete')->value,
      ];
    }
    return $logs;
  }

  protected function emptyMetrics(string $timezone = 'UTC'): array {
    $heatmap = [];
    $today = new DateTimeImmutable('now', new DateTimeZone($timezone));
    for ($i = 29; $i >= 0; $i--) {
      $heatmap[$today->sub(new DateInterval('P' . $i . 'D'))->format('Y-m-d')] = FALSE;
    }

    return [
      'current_streak' => 0,
      'longest_streak' => 0,
      'last_active_date' => NULL,
      'missed_days_in_window' => 0,
      'heatmap' => $heatmap,
      'rule' => [
        'daily_target_type' => 'pages',
        'daily_target_value' => 0,
        'grace_days' => 0,
        'timezone' => $timezone,
      ],
    ];
  }

  protected function loadUser(int $uid): ?UserInterface {
    return $this->entityTypeManager->getStorage('user')->load($uid);
  }

  protected function loadRule(int $uid): array {
    $rule_id = "user_{$uid}";
    $configStorage = $this->entityTypeManager->getStorage('streak_rule');
    /** @var \Drupal\gvquest_streaks\Entity\StreakRule|null $rule */
    $rule = $configStorage->load($rule_id);
    if (!$rule) {
      $defaults = $this->configFactory->get('gvquest_streaks.settings');
      return [
        'daily_target_type' => $defaults->get('daily_target_type') ?: 'pages',
        'daily_target_value' => (float) ($defaults->get('daily_target_value') ?? 0),
        'grace_days' => (int) ($defaults->get('grace_days') ?? 0),
        'timezone' => $defaults->get('timezone') ?: 'UTC',
        'start_date' => $defaults->get('start_date') ?: NULL,
        'active' => TRUE,
      ];
    }

    return [
      'daily_target_type' => $rule->get('daily_target_type') ?? 'pages',
      'daily_target_value' => (float) $rule->get('daily_target_value'),
      'grace_days' => (int) $rule->get('grace_days'),
      'timezone' => $rule->get('timezone'),
      'start_date' => $rule->get('start_date'),
      'active' => (bool) $rule->get('active'),
    ];
  }

}
