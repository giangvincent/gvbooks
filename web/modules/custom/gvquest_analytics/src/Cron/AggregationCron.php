<?php

namespace Drupal\gvquest_analytics\Cron;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\gvquest_analytics\Service\AnalyticsAggregator;
use Psr\Log\LoggerInterface;

/**
 * Cron callback for analytics aggregation.
 */
class AggregationCron {

  protected AnalyticsAggregator $aggregator;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected LoggerInterface $logger;

  public function __construct(AnalyticsAggregator $aggregator, EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger) {
    $this->aggregator = $aggregator;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
  }

  /**
   * Execute cron.
   */
  public function run() {
    try {
      $this->aggregator->aggregateRecent();
      $this->logger->info('GVQuest analytics aggregated recent activity.');
    }
    catch (\Throwable $exception) {
      $this->logger->error('Analytics aggregation failed: @message', ['@message' => $exception->getMessage()]);
    }
  }

}
