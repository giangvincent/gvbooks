<?php

namespace Drupal\gvquest_analytics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\gvquest_analytics\Service\AnalyticsAggregator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Dashboard page for analytics.
 */
class AnalyticsDashboardController extends ControllerBase implements ContainerInjectionInterface {

  protected AnalyticsAggregator $aggregator;

  public function __construct(AnalyticsAggregator $aggregator) {
    $this->aggregator = $aggregator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('gvquest_analytics.aggregator')
    );
  }

  /**
   * Render dashboard.
   */
  public function dashboard() {
    $account = $this->currentUser();
    $uid = (int) $account->id();

    $this->aggregator->aggregateDate(date('Y-m-d'));
    $kpis = $this->aggregator->getWeeklyKpis($uid);
    $series = $this->aggregator->getRecentSeries($uid, 14);
    $sessions = $this->aggregator->getRecentSessions($uid, 10);

    $build['#attached']['library'][] = 'gvquest_analytics/dashboard';
    $build['#attached']['drupalSettings']['gvquestAnalytics'] = [
      'series' => $series,
    ];

    $build['header'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['gvquest-analytics__header', 'flex', 'flex-col', 'gap-4', 'md:flex-row', 'md:items-center', 'md:justify-between']],
      'title' => ['#markup' => '<h2 class="gvquest-dashboard-heading">' . $this->t('Reading analytics') . '</h2>'],
      'actions' => [
        '#type' => 'link',
        '#title' => $this->t('View streaks'),
        '#attributes' => ['class' => ['inline-flex', 'items-center', 'gap-2', 'rounded-full', 'bg-brand-accent', 'px-4', 'py-2', 'text-sm', 'font-semibold', 'text-brand-deep', 'shadow-md', 'hover:bg-orange-400']],
        '#url' => Url::fromRoute('gvquest_streaks.dashboard'),
      ],
    ];

    $build['kpis'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['grid', 'gap-4', 'md:grid-cols-4']],
      'minutes' => $this->kpiCard($this->t('Minutes this week'), $kpis['total_minutes']),
      'pages' => $this->kpiCard($this->t('Pages this week'), $kpis['total_pages']),
      'days' => $this->kpiCard($this->t('Active days'), $kpis['active_days']),
      'streak' => $this->kpiCard($this->t('Current streak'), $kpis['current_streak']),
    ];

    $build['charts'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['grid', 'gap-6', 'md:grid-cols-2']],
      'minutes' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['gvquest-analytics__chart-card']],
        'title' => ['#markup' => '<h3 class="text-lg font-semibold text-brand-deep mb-2">' . $this->t('Minutes per day (14d)') . '</h3>'],
        'chart' => ['#markup' => '<div class="gvquest-analytics__sparkline" data-series="minutes"></div>'],
      ],
      'pages' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['gvquest-analytics__chart-card']],
        'title' => ['#markup' => '<h3 class="text-lg font-semibold text-brand-deep mb-2">' . $this->t('Pages per day (14d)') . '</h3>'],
        'chart' => ['#markup' => '<div class="gvquest-analytics__barchart" data-series="pages"></div>'],
      ],
    ];

    $rows = [];
    foreach ($sessions as $session) {
      $rows[] = [
        'data' => [
          $this->formatDate($session['started_at']),
          $this->formatDate($session['ended_at']),
          $session['minutes'],
          $session['pages'],
          $session['source'],
        ],
      ];
    }

    $build['sessions'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Start'),
        $this->t('End'),
        $this->t('Minutes'),
        $this->t('Pages'),
        $this->t('Source'),
      ],
      '#rows' => $rows,
      '#empty' => $this->t('No sessions recorded yet.'),
      '#attributes' => ['class' => ['gvquest-analytics__table', 'gvquest-card']],
    ];

    $build['#cache']['contexts'][] = 'user';

    return $build;
  }

  protected function kpiCard($label, $value): array {
    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['gvquest-card', 'p-4', 'rounded-xl', 'bg-white', 'shadow-dashboard']],
      'label' => ['#markup' => '<div class="text-sm uppercase tracking-wide text-slate-500">' . $label . '</div>'],
      'value' => ['#markup' => '<div class="mt-2 text-3xl font-bold text-brand-deep">' . (int) $value . '</div>'],
    ];
  }

  protected function formatDate(int $timestamp): string {
    return $this->dateFormatter()->format($timestamp, 'custom', 'Y-m-d H:i');
  }

}
