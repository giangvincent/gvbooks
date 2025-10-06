<?php

namespace Drupal\gvquest_analytics\Service;

use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use DateTimeZone;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\gvquest_streaks\Service\StreakCalculator;
use Psr\Log\LoggerInterface;

/**
 * Aggregates reading telemetry into daily summaries.
 */
class AnalyticsAggregator {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected Connection $database;
  protected TimeInterface $time;
  protected ConfigFactoryInterface $configFactory;
  protected LoggerInterface $logger;
  protected StreakCalculator $streakCalculator;

  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $database, TimeInterface $time, ConfigFactoryInterface $config_factory, LoggerInterface $logger, StreakCalculator $streakCalculator) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->time = $time;
    $this->configFactory = $config_factory;
    $this->logger = $logger;
    $this->streakCalculator = $streakCalculator;
  }

  /**
   * Aggregate within the configured lookback window from now.
   */
  public function aggregateRecent() {
    $config = $this->configFactory->get('gvquest_analytics.settings');
    $days = max(1, (int) $config->get('aggregation_lookback_days') ?? 30);
    $end = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    $start = $end->sub(new DateInterval('P' . ($days - 1) . 'D'));
    $this->aggregateRange($start, $end);
  }

  /**
   * Aggregate within a date range inclusive (UTC).
   */
  public function aggregateRange(?DateTimeImmutable $start = NULL, ?DateTimeImmutable $end = NULL) {
    $end = $end ?: new DateTimeImmutable('now', new DateTimeZone('UTC'));
    $start = $start ?: $end->sub(new DateInterval('P6D'));

    $period = new DatePeriod($start->setTime(0, 0), new DateInterval('P1D'), $end->add(new DateInterval('P1D')));

    foreach ($period as $day) {
      $this->aggregateDate($day->format('Y-m-d'));
    }
  }

  /**
   * Aggregate a specific day (YYYY-mm-dd) for all users/books.
   */
  public function aggregateDate(string $date) {
    $dayStart = strtotime($date . ' 00:00:00 UTC');
    $dayEnd = strtotime($date . ' 23:59:59 UTC');

    $query = $this->database->select('gvquest_reading_event', 'e')
      ->fields('e', ['uid', 'book_nid', 'duration', 'pages_delta', 'percent_complete', 'started_at', 'ended_at'])
      ->condition('started_at', $dayEnd, '<=')
      ->condition('ended_at', $dayStart, '>=');

    $results = $query->execute();
    $totals = [];

    foreach ($results as $row) {
      $uid = (int) $row->uid;
      $book = (int) $row->book_nid;
      if (!$uid || !$book) {
        continue;
      }
      $key = $uid . ':' . $book;
      if (!isset($totals[$key])) {
        $totals[$key] = [
          'uid' => $uid,
          'book_nid' => $book,
          'minutes' => 0,
          'pages' => 0,
          'last_percent' => 0,
        ];
      }
      $duration = max(0, (int) $row->duration);
      if (!$duration) {
        $duration = max(0, (int) $row->ended_at - (int) $row->started_at);
      }
      $totals[$key]['minutes'] += (int) round($duration / 60);
      $totals[$key]['pages'] += (int) $row->pages_delta;
      $totals[$key]['last_percent'] = max($totals[$key]['last_percent'], (float) $row->percent_complete);
    }

    $transaction = $this->database->startTransaction();
    try {
      $this->database->delete('gvquest_analytics_daily')
        ->condition('date', $date)
        ->execute();
      foreach ($totals as $total) {
        $this->database->merge('gvquest_analytics_daily')
          ->key([
            'uid' => $total['uid'],
            'book_nid' => $total['book_nid'],
            'date' => $date,
          ])
          ->fields([
            'minutes' => $total['minutes'],
            'pages' => $total['pages'],
            'last_percent' => $total['last_percent'],
            'updated' => $this->time->getRequestTime(),
          ])
          ->execute();
      }
    }
    catch (\Throwable $e) {
      $transaction->rollBack();
      $this->logger->error('Failed to aggregate analytics for @date: @message', ['@date' => $date, '@message' => $e->getMessage()]);
      throw $e;
    }

    return $totals;
  }

  /**
   * Fetch last N days summary for dashboard.
   */
  public function getRecentSeries(int $uid, int $days = 14): array {
    $end = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    $start = $end->sub(new DateInterval('P' . ($days - 1) . 'D'));
    $dates = [];
    $period = new DatePeriod($start, new DateInterval('P1D'), $end->add(new DateInterval('P1D')));
    foreach ($period as $day) {
      $dates[$day->format('Y-m-d')] = ['minutes' => 0, 'pages' => 0];
    }

    $query = $this->database->select('gvquest_analytics_daily', 'd')
      ->fields('d', ['date', 'minutes', 'pages'])
      ->condition('uid', $uid)
      ->condition('date', array_keys($dates), 'IN')
      ->orderBy('date', 'ASC');
    $results = $query->execute();
    foreach ($results as $row) {
      $dates[$row->date] = ['minutes' => (int) $row->minutes, 'pages' => (int) $row->pages];
    }

    $seriesMinutes = [];
    $seriesPages = [];
    foreach ($dates as $date => $values) {
      $seriesMinutes[] = ['date' => $date, 'value' => $values['minutes']];
      $seriesPages[] = ['date' => $date, 'value' => $values['pages']];
    }

    return ['minutes' => $seriesMinutes, 'pages' => $seriesPages];
  }

  /**
   * Retrieve weekly KPIs for dashboard.
   */
  public function getWeeklyKpis(int $uid): array {
    $today = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    $start = $today->sub(new DateInterval('P6D'))->format('Y-m-d');
    $end = $today->format('Y-m-d');

    $query = $this->database->select('gvquest_analytics_daily', 'd')
      ->fields('d', ['minutes', 'pages', 'date'])
      ->condition('uid', $uid)
      ->condition('date', [$start, $end], 'BETWEEN');
    $results = $query->execute();

    $totalMinutes = 0;
    $totalPages = 0;
    $activeDays = [];

    foreach ($results as $row) {
      $minutes = (int) $row->minutes;
      $pages = (int) $row->pages;
      $totalMinutes += $minutes;
      $totalPages += $pages;
      if ($minutes > 0 || $pages > 0) {
        $activeDays[$row->date] = TRUE;
      }
    }

    $streak = $this->streakCalculator->getCachedMetrics($uid) ?? ['current_streak' => 0];

    return [
      'total_minutes' => $totalMinutes,
      'total_pages' => $totalPages,
      'active_days' => count($activeDays),
      'current_streak' => $streak['current_streak'] ?? 0,
    ];
  }

  /**
   * Return recent session events for display.
   */
  public function getRecentSessions(int $uid, int $limit = 10): array {
    $query = $this->database->select('gvquest_reading_event', 'e')
      ->fields('e', ['started_at', 'ended_at', 'duration', 'pages_delta', 'source', 'book_nid'])
      ->condition('uid', $uid)
      ->orderBy('ended_at', 'DESC')
      ->range(0, $limit);

    $sessions = [];
    foreach ($query->execute() as $row) {
      $duration = (int) ($row->duration ?: max(0, $row->ended_at - $row->started_at));
      $sessions[] = [
        'started_at' => (int) $row->started_at,
        'ended_at' => (int) $row->ended_at,
        'minutes' => (int) round($duration / 60),
        'pages' => (int) $row->pages_delta,
        'source' => $row->source,
        'book_nid' => (int) $row->book_nid,
      ];
    }
    return $sessions;
  }

}
