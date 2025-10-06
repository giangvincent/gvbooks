# GVQuest Analytics

Captures fine-grained reading telemetry, generates daily aggregates, and exposes dashboards and APIs for the GvBooks platform.

## API

`POST /api/analytics/reading-event`

```
{
  "book_nid": 123,
  "started_at": "2025-01-10T14:35:00Z",
  "ended_at": "2025-01-10T14:48:30Z",
  "pages_delta": 12,
  "current_page": 220,
  "percent_complete": 56.5,
  "source": "pdfjs"
}
```

- Requires CSRF token and `post analytics events` permission.
- Validates the book exists and belongs to the requesting user (or shared per access).
- Debounces events under the configured threshold unless `pages_delta` is positive.

`GET /api/analytics/summary`

Returns recent KPIs and chart payloads for the current user.

## Cron & Aggregation

`AggregationCron` collects active users and calls `AnalyticsAggregator` to roll up the latest events into `gvquest_analytics_daily`.
Run manually with `drush php:eval 'Drupal::service("gvquest_analytics.aggregator")->aggregateRange();'`.

```
drush en gvquest_analytics -y
drush entity:updates -y
drush cr
drush php:eval 'Drupal::service("gvquest_analytics.aggregator")->aggregateDate(date("Y-m-d"));'
```

## Views

- **My Daily Reading** – per-day aggregates with contextual filter on the current user.
- **My Sessions** – raw reading events for auditing.

## Configuration

Visit `/admin/config/gvquest/analytics` to toggle telemetry, adjust debounce, or set aggregation window.

Per-user preference is stored in `user.data` using the key `gvquest_analytics:opt_in` (set to `0` to opt out).
