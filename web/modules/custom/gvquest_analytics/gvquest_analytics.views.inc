<?php

/**
 * Implements hook_views_data().
 */
function gvquest_analytics_views_data() {
  $data = [];

  $data['gvquest_analytics_daily']['table'] = [
    'group' => t('GVQuest analytics'),
    'provider' => 'gvquest_analytics',
    'base' => [
      'field' => 'date',
      'title' => t('Daily analytics'),
      'help' => t('Daily reading aggregates.'),
    ],
  ];

  $data['gvquest_analytics_daily']['uid'] = [
    'title' => t('User'),
    'help' => t('User ID'),
    'field' => ['id' => 'numeric'],
    'filter' => ['id' => 'numeric'],
    'sort' => ['id' => 'standard'],
    'relationship' => [
      'id' => 'standard',
      'base' => 'users_field_data',
      'base field' => 'uid',
      'label' => t('User'),
    ],
  ];

  $data['gvquest_analytics_daily']['book_nid'] = [
    'title' => t('Book'),
    'help' => t('Book node ID'),
    'field' => ['id' => 'numeric'],
    'filter' => ['id' => 'numeric'],
    'sort' => ['id' => 'standard'],
    'relationship' => [
      'id' => 'standard',
      'base' => 'node_field_data',
      'base field' => 'nid',
      'label' => t('Book'),
    ],
  ];

  $data['gvquest_analytics_daily']['date'] = [
    'title' => t('Date'),
    'help' => t('Aggregation date'),
    'field' => ['id' => 'string'],
    'filter' => ['id' => 'string'],
    'sort' => ['id' => 'standard'],
  ];

  $data['gvquest_analytics_daily']['minutes'] = [
    'title' => t('Minutes'),
    'help' => t('Total minutes read'),
    'field' => ['id' => 'numeric'],
    'filter' => ['id' => 'numeric'],
    'sort' => ['id' => 'standard'],
  ];

  $data['gvquest_analytics_daily']['pages'] = [
    'title' => t('Pages'),
    'help' => t('Total pages read'),
    'field' => ['id' => 'numeric'],
    'filter' => ['id' => 'numeric'],
    'sort' => ['id' => 'standard'],
  ];

  $data['gvquest_analytics_daily']['last_percent'] = [
    'title' => t('Last percent'),
    'help' => t('Last recorded percent complete'),
    'field' => ['id' => 'numeric'],
    'filter' => ['id' => 'numeric'],
    'sort' => ['id' => 'standard'],
  ];

  return $data;
}
