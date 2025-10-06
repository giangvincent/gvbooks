<?php

namespace Drupal\gvquest_streaks\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\gvquest_streaks\Service\StreakCalculator;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes queued streak evaluations.
 *
 * @QueueWorker(
 *   id = "gvquest_streak_eval",
 *   title = @Translation("GVQuest streak evaluation"),
 *   cron = { "time" = 60 }
 * )
 */
class StreakEvalWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  protected StreakCalculator $calculator;
  protected LoggerInterface $logger;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, StreakCalculator $calculator, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->calculator = $calculator;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('gvquest_streaks.calculator'),
      $container->get('logger.channel.gvquest_streaks')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $uid = (int) ($data['uid'] ?? 0);
    if (!$uid) {
      return;
    }
    try {
      $this->calculator->updateCache($uid);
    }
    catch (\Throwable $exception) {
      $this->logger->error('Failed to process streak queue item for user @uid: @message', [
        '@uid' => $uid,
        '@message' => $exception->getMessage(),
      ]);
      throw $exception;
    }
  }

}
