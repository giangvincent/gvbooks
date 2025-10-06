<?php

namespace Drupal\gvquest_analytics\Controller;

use DateTimeImmutable;
use DateTimeZone;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\gvquest_analytics\Service\AnalyticsAggregator;
use Drupal\user\UserDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * REST-like API controller for analytics ingest and summaries.
 */
class AnalyticsApiController extends ControllerBase implements ContainerInjectionInterface {

  protected $entityTypeManager;
  protected AnalyticsAggregator $aggregator;
  protected UserDataInterface $userData;
  protected CsrfTokenGenerator $csrfToken;

  public function __construct(EntityTypeManagerInterface $entity_type_manager, AnalyticsAggregator $aggregator, UserDataInterface $user_data, CsrfTokenGenerator $csrf_token) {
    $this->entityTypeManager = $entity_type_manager;
    $this->aggregator = $aggregator;
    $this->userData = $user_data;
    $this->csrfToken = $csrf_token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('gvquest_analytics.aggregator'),
      $container->get('user.data'),
      $container->get('csrf_token')
    );
  }

  /**
   * Ingest reading events.
   */
  public function ingest(Request $request) {
    $account = $this->currentUser();
    $this->assertTelemetryEnabled($account);

    $payload = json_decode($request->getContent(), TRUE);
    if (!is_array($payload)) {
      throw new BadRequestHttpException('Invalid payload.');
    }

    $bookNid = (int) ($payload['book_nid'] ?? 0);
    $started = $this->parseTimestamp($payload['started_at'] ?? NULL);
    $ended = $this->parseTimestamp($payload['ended_at'] ?? NULL);
    $pagesDelta = (int) ($payload['pages_delta'] ?? 0);
    $currentPage = (int) ($payload['current_page'] ?? 0);
    $percent = (float) ($payload['percent_complete'] ?? 0);
    $source = $this->sanitizeSource($payload['source'] ?? 'manual');

    if (!$bookNid || !$started || !$ended) {
      throw new BadRequestHttpException('Missing book or timestamps.');
    }

    if ($ended < $started) {
      $ended = $started;
    }

    $this->assertBookAccess($bookNid, $account);
    $this->ensureDebounce($account->id(), $bookNid, $started, $ended, $pagesDelta);

    $storage = $this->entityTypeManager->getStorage('reading_event');
    /** @var \Drupal\gvquest_analytics\Entity\ReadingEvent $event */
    $event = $storage->create([
      'user_id' => $account->id(),
      'book_nid' => $bookNid,
      'started_at' => $started,
      'ended_at' => $ended,
      'duration' => max(0, $ended - $started),
      'pages_delta' => $pagesDelta,
      'current_page' => $currentPage,
      'percent_complete' => $percent,
      'source' => $source,
    ]);
    $event->save();

    return new JsonResponse([
      'status' => 'ok',
      'id' => $event->id(),
    ], JsonResponse::HTTP_CREATED);
  }

  /**
   * Provide summary analytics for the current user.
   */
  public function summary() {
    $account = $this->currentUser();
    $this->assertTelemetryEnabled($account);

    $uid = (int) $account->id();
    $this->aggregator->aggregateDate(date('Y-m-d'));
    $kpis = $this->aggregator->getWeeklyKpis($uid);
    $series = $this->aggregator->getRecentSeries($uid, 14);
    $sessions = $this->aggregator->getRecentSessions($uid, 10);

    return new JsonResponse([
      'kpis' => $kpis,
      'series' => $series,
      'sessions' => $sessions,
      'csrfToken' => $this->csrfToken->get('gvquest_analytics.ingest'),
    ]);
  }

  protected function parseTimestamp(?string $input): ?int {
    if (!$input) {
      return NULL;
    }
    try {
      $dt = new DateTimeImmutable($input, new DateTimeZone('UTC'));
      return $dt->getTimestamp();
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  protected function sanitizeSource(string $source): string {
    $allowed = ['manual', 'pdfjs', 'epubjs'];
    return in_array($source, $allowed, TRUE) ? $source : 'manual';
  }

  protected function assertBookAccess(int $nid, AccountInterface $account): void {
    $node = $this->entityTypeManager->getStorage('node')->load($nid);
    if (!$node) {
      throw new BadRequestHttpException('Unknown book.');
    }
    if ($account->hasPermission('view all analytics')) {
      return;
    }
    if ((int) $node->getOwnerId() !== (int) $account->id()) {
      throw new AccessDeniedHttpException('Book not owned by user.');
    }
  }

  protected function ensureDebounce(int $uid, int $book_nid, int $started, int $ended, int $pages_delta): void {
    $config = $this->config('gvquest_analytics.settings');
    $threshold = max(0, (int) $config->get('debounce_seconds') ?? 5);
    if ($threshold === 0 && $pages_delta >= 0) {
      return;
    }

    $storage = $this->entityTypeManager->getStorage('reading_event');
    $query = $storage->getQuery()
      ->condition('user_id', $uid)
      ->condition('book_nid', $book_nid)
      ->sort('ended_at', 'DESC')
      ->range(0, 1);
    $ids = $query->execute();
    if (!$ids) {
      return;
    }
    $existing = $storage->load(reset($ids));
    if (!$existing) {
      return;
    }

    $gap = $started - (int) $existing->get('ended_at')->value;
    if ($gap < $threshold && $pages_delta <= 0) {
      throw new BadRequestHttpException('Duplicate event ignored by debounce.');
    }
  }

  protected function assertTelemetryEnabled(AccountInterface $account): void {
    $config = $this->config('gvquest_analytics.settings');
    if (!$config->get('telemetry_enabled')) {
      throw new AccessDeniedHttpException();
    }

    $userFlag = $this->userData->get('gvquest_analytics', $account->id(), 'opt_in');
    if ($userFlag === NULL) {
      if (!$config->get('default_user_opt_in')) {
        throw new AccessDeniedHttpException();
      }
    }
    elseif (!$userFlag) {
      throw new AccessDeniedHttpException();
    }
  }

}
