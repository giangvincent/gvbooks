<?php

namespace Drupal\gvquest_streaks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\gvquest_streaks\Service\StreakCalculator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a brief streak summary for dashboards.
 *
 * @Block(
 *   id = "gvquest_streak_summary",
 *   admin_label = @Translation("My streak summary"),
 *   category = @Translation("GVQuest")
 * )
 */
class StreakSummaryBlock extends BlockBase implements ContainerFactoryPluginInterface {

  protected StreakCalculator $calculator;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, StreakCalculator $calculator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->calculator = $calculator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('gvquest_streaks.calculator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $account = $this->currentUser();
    if ($account->isAnonymous()) {
      return [];
    }

    $uid = (int) $account->id();
    $metrics = $this->calculator->getCachedMetrics($uid) ?? $this->calculator->updateCache($uid);

    $build = [
      '#theme' => 'item_list',
      '#items' => [
        $this->t('Current streak: @days days', ['@days' => $metrics['current_streak'] ?? 0]),
        $this->t('Longest streak: @days days', ['@days' => $metrics['longest_streak'] ?? 0]),
        $this->t('Last activity: @date', ['@date' => $metrics['last_active_date'] ?: $this->t('None yet')]),
      ],
      '#attributes' => ['class' => ['gvquest-streaks__summary-block']],
      '#suffix' => Link::fromTextAndUrl($this->t('View streak dashboard'), Url::fromRoute('gvquest_streaks.dashboard'))->toString(),
      '#cache' => [
        'contexts' => ['user'],
      ],
    ];

    return $build;
  }

}
