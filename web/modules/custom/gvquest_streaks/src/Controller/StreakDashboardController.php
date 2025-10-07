<?php

namespace Drupal\gvquest_streaks\Controller;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\gvquest_streaks\Service\StreakCalculator;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Controller for streak dashboards and logging endpoint.
 */
class StreakDashboardController extends ControllerBase implements ContainerInjectionInterface {

  protected StreakCalculator $calculator;
  protected $entityTypeManager;
  protected CsrfTokenGenerator $csrfToken;

  public function __construct(StreakCalculator $calculator, EntityTypeManagerInterface $entity_type_manager, CsrfTokenGenerator $csrf_token) {
    $this->calculator = $calculator;
    $this->entityTypeManager = $entity_type_manager;
    $this->csrfToken = $csrf_token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('gvquest_streaks.calculator'),
      $container->get('entity_type.manager'),
      $container->get('csrf_token')
    );
  }

  /**
   * Dashboard page render array.
   */
  public function dashboard() {
    $account = $this->currentUser();
    dd($account);
    $uid = (int) $account->id();

    // Evaluate fresh metrics and persist cache for nightly summaries.
    $evaluation = $this->calculator->evaluate($uid);
    $this->calculator->updateCache($uid, $evaluation);

    $heatmapItems = [];
    $heatmap = $evaluation['heatmap'];
    $counter = 0;
    foreach ($heatmap as $date => $success) {
      $counter++;
      $classes = ['gvquest-streaks__cell', $success ? 'is-success' : 'is-miss'];
      $heatmapItems[] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => '',
        '#attributes' => [
          'class' => $classes,
          'title' => $this->t('@date: @state', [
            '@date' => $date,
            '@state' => $success ? $this->t('Goal met') : $this->t('Goal missed'),
          ]),
          'data-day' => $counter,
        ],
      ];
    }

    $build['#attached']['library'][] = 'gvquest_streaks/dashboard';
    $build['#attached']['drupalSettings']['gvquestStreaks'] = [
      'endpoint' => Url::fromRoute('gvquest_streaks.log')->toString(),
      'csrfToken' => $this->csrfToken->get('gvquest_streaks.log'),
    ];

    $build['summary'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['gvquest-streaks__summary', 'gvquest-card', 'flex', 'flex-col', 'gap-6']],
      'header' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['flex', 'items-center', 'justify-between']],
        'title' => [
          '#markup' => '<h2 class="gvquest-dashboard-heading">' . $this->t('My reading streak') . '</h2>',
        ],
        'cta' => [
          '#type' => 'link',
          '#title' => $this->t('+ Upload Book'),
          '#attributes' => ['class' => ['inline-flex', 'items-center', 'gap-2', 'rounded-full', 'bg-brand-accent', 'px-4', 'py-2', 'text-sm', 'font-semibold', 'text-brand-deep', 'shadow-md', 'hover:bg-orange-400']],
          '#url' => Url::fromRoute('entity.node.add_form', ['node_type' => 'book']),
        ],
      ],
      'metrics' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['grid', 'gap-4', 'sm:grid-cols-3']],
        'current' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['gvquest-streaks__metric', 'p-4', 'rounded-xl', 'bg-white', 'shadow-dashboard']],
          'label' => ['#markup' => '<div class="text-sm uppercase tracking-wide text-slate-500">' . $this->t('Current streak') . '</div>'],
          'value' => ['#markup' => '<div class="mt-2 text-3xl font-bold text-brand-deep">' . (int) $evaluation['current_streak'] . ' ' . $this->t('days') . '</div>'],
        ],
        'longest' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['gvquest-streaks__metric', 'p-4', 'rounded-xl', 'bg-white', 'shadow-dashboard']],
          'label' => ['#markup' => '<div class="text-sm uppercase tracking-wide text-slate-500">' . $this->t('Longest streak') . '</div>'],
          'value' => ['#markup' => '<div class="mt-2 text-3xl font-bold text-brand-deep">' . (int) $evaluation['longest_streak'] . ' ' . $this->t('days') . '</div>'],
        ],
        'last_active' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['gvquest-streaks__metric', 'p-4', 'rounded-xl', 'bg-white', 'shadow-dashboard']],
          'label' => ['#markup' => '<div class="text-sm uppercase tracking-wide text-slate-500">' . $this->t('Last activity') . '</div>'],
          'value' => ['#markup' => '<div class="mt-2 text-xl font-semibold text-brand-deep">' . ($evaluation['last_active_date'] ?: $this->t('No logs yet')) . '</div>'],
        ],
      ],
      'heatmap' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['gvquest-streaks__heatmap', 'grid', 'grid-cols-6', 'gap-2']],
        'cells' => $heatmapItems,
      ],
    ];

    $build['#cache']['contexts'][] = 'user';

    return $build;
  }

  /**
   * Accept POSTed reading progress from JS.
   */
  public function log(Request $request) {
    $account = $this->currentUser();
    if (!$account->hasPermission('log reading progress')) {
      throw new AccessDeniedHttpException();
    }

    $payload = json_decode($request->getContent(), TRUE);
    if (!is_array($payload)) {
      throw new BadRequestHttpException('Invalid payload.');
    }

    $bookNid = (int) ($payload['book_nid'] ?? 0);
    $pages = (int) ($payload['pages_read'] ?? 0);
    $minutes = (int) ($payload['minutes_read'] ?? 0);
    $percent = (float) ($payload['percent_complete'] ?? 0);
    $source = $payload['source'] ?? 'manual';
    if (!in_array($source, ['manual', 'pdfjs', 'epubjs'], TRUE)) {
      $source = 'manual';
    }

    if (!$bookNid) {
      throw new BadRequestHttpException('Missing book identifier.');
    }

    $userTimezone = $this->currentUserTimezone($account);
    $today = (new \DateTimeImmutable('now', new \DateTimeZone($userTimezone)))->format('Y-m-d');

    $storage = $this->entityTypeManager->getStorage('reading_log');
    $query = $storage->getQuery()
      ->condition('user_id', $account->id())
      ->condition('book_nid', $bookNid)
      ->condition('log_date.value', $today)
      ->range(0, 1);
    $ids = $query->execute();

    if ($ids) {
      $log = $storage->load(reset($ids));
    }
    else {
      $log = $storage->create([
        'user_id' => $account->id(),
        'book_nid' => $bookNid,
        'log_date' => $today,
      ]);
    }

    $log->set('pages_read', $pages);
    $log->set('minutes_read', $minutes);
    $log->set('percent_complete', $percent);
    $log->set('source', $source);
    $log->save();

    $metrics = $this->calculator->updateCache((int) $account->id());

    return new JsonResponse([
      'status' => 'ok',
      'metrics' => $metrics,
    ]);
  }

  protected function currentUserTimezone(AccountInterface $account): string {
    if ($account instanceof UserInterface) {
      $tz = $account->getTimezone();
      if ($tz) {
        return $tz;
      }
    }
    return $this->config('system.date')->get('timezone.default') ?: 'UTC';
  }

}
