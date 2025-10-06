<?php

namespace Drupal\gvquest_streaks\Cron;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Psr\Log\LoggerInterface;

/**
 * Cron job that queues streak evaluations for active users.
 */
class StreakCron {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected QueueFactory $queueFactory;
  protected LoggerInterface $logger;

  public function __construct(EntityTypeManagerInterface $entity_type_manager, QueueFactory $queue_factory, LoggerInterface $logger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->queueFactory = $queue_factory;
    $this->logger = $logger;
  }

  /**
   * Cron callback.
   */
  public function run() {
    $storage = $this->entityTypeManager->getStorage('streak_rule');
    $query = $storage->getQuery()->condition('active', TRUE);
    $ids = $query->execute();

    if (!$ids) {
      return;
    }

    $queue = $this->queueFactory->get('gvquest_streak_eval');
    $queued = 0;

    foreach ($storage->loadMultiple($ids) as $rule) {
      /** @var \Drupal\gvquest_streaks\Entity\StreakRule $rule */
      $uid = (int) $rule->getUserId();
      if (!$uid) {
        continue;
      }
      $queue->createItem(['uid' => $uid]);
      $queued++;
    }

    if ($queued) {
      $this->logger->info('Queued streak recalculation for @count users.', ['@count' => $queued]);
    }
  }

}
