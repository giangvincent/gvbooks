<?php

use Drupal\views\Entity\View;

$view_id = 'my_books';

$dependencies = [
    'config' => [
        'field.field.node.book.field_book_file',
        'field.field.node.book.field_cover_image',
        'field.field.node.book.field_description',
        'field.field.node.book.field_tags',
        'field.storage.node.field_book_file',
        'field.storage.node.field_cover_image',
        'field.storage.node.field_description',
        'field.storage.node.field_tags',
        'filter.format.full_html',
        'image.style.large',
        'node.type.book',
    ],
    'module' => [
        'file',
        'image',
        'node',
        'text',
        'user',
        'views',
    ],
];

$default_alter = [
    'alter_text' => FALSE,
    'text' => '',
    'make_link' => FALSE,
    'path' => '',
    'absolute' => FALSE,
    'external' => FALSE,
    'replace_spaces' => FALSE,
    'path_case' => 'none',
    'trim_whitespace' => FALSE,
    'alt' => '',
    'rel' => '',
    'link_class' => '',
    'prefix' => '',
    'suffix' => '',
    'target' => '',
    'nl2br' => FALSE,
    'max_length' => 0,
    'word_boundary' => TRUE,
    'ellipsis' => TRUE,
    'more_link' => FALSE,
    'more_link_text' => '',
    'more_link_path' => '',
    'strip_tags' => FALSE,
    'trim' => FALSE,
    'preserve_tags' => '',
    'html' => FALSE,
];

$default_field_options = [
    'relationship' => 'none',
    'group_type' => 'group',
    'admin_label' => '',
    'label' => '',
    'exclude' => FALSE,
    'alter' => $default_alter,
    'element_type' => '',
    'element_class' => '',
    'element_label_type' => '',
    'element_label_class' => '',
    'element_label_colon' => TRUE,
    'element_wrapper_type' => '',
    'element_wrapper_class' => '',
    'element_default_classes' => TRUE,
    'empty' => '',
    'hide_empty' => FALSE,
    'empty_zero' => FALSE,
    'hide_alter_empty' => TRUE,
    'group_column' => 'value',
    'group_columns' => [],
    'group_rows' => TRUE,
    'delta_limit' => 0,
    'delta_offset' => 0,
    'delta_reversed' => FALSE,
    'delta_first_last' => FALSE,
    'multi_type' => 'separator',
    'separator' => ', ',
    'field_api_classes' => FALSE,
    'plugin_id' => 'field',
];

function build_field(array $overrides, array $base)
{
    return array_replace_recursive($base, $overrides);
}

$fields = [
    'field_cover_image' => build_field([
        'id' => 'field_cover_image',
        'table' => 'node__field_cover_image',
        'field' => 'field_cover_image',
        'entity_type' => 'node',
        'entity_field' => 'field_cover_image',
        'element_class' => 'rounded-2xl overflow-hidden',
        'click_sort_column' => 'target_id',
        'type' => 'image',
        'settings' => [
            'image_style' => 'large',
            'image_link' => '',
            'image_loading' => [
                'attribute' => 'lazy',
            ],
        ],
    ], $default_field_options),
    'title' => build_field([
        'id' => 'title',
        'table' => 'node_field_data',
        'field' => 'title',
        'entity_type' => 'node',
        'entity_field' => 'title',
        'element_class' => 'gvquest-card__title',
        'click_sort_column' => 'value',
        'type' => 'string',
        'settings' => [
            'link_to_entity' => TRUE,
        ],
    ], $default_field_options),
    'created' => build_field([
        'id' => 'created',
        'table' => 'node_field_data',
        'field' => 'created',
        'entity_type' => 'node',
        'entity_field' => 'created',
        'label' => 'Uploaded',
        'element_class' => 'gvquest-card__meta',
        'click_sort_column' => 'value',
        'type' => 'timestamp',
        'settings' => [
            'date_format' => 'custom',
            'custom_date_format' => 'M j, Y',
            'timezone' => '',
        ],
    ], $default_field_options),
    'field_book_file' => build_field([
        'id' => 'field_book_file',
        'table' => 'node__field_book_file',
        'field' => 'field_book_file',
        'entity_type' => 'node',
        'entity_field' => 'field_book_file',
        'label' => 'View / Download',
        'element_class' => 'gvquest-card__actions',
        'click_sort_column' => 'target_id',
        'type' => 'file_link',
        'settings' => [
            'text' => 'View / Download',
            'display' => 'link',
            'rel' => '',
            'target' => '',
        ],
    ], $default_field_options),
];

$filters = [
    'status' => [
        'id' => 'status',
        'table' => 'node_field_data',
        'field' => 'status',
        'entity_type' => 'node',
        'entity_field' => 'status',
        'plugin_id' => 'boolean',
        'value' => '1',
        'group' => 1,
        'expose' => [
            'operator' => '',
        ],
    ],
    'type' => [
        'id' => 'type',
        'table' => 'node_field_data',
        'field' => 'type',
        'entity_type' => 'node',
        'entity_field' => 'type',
        'plugin_id' => 'bundle',
        'value' => [
            'book' => 'book',
        ],
        'group' => 1,
        'expose' => [
            'operator' => '',
        ],
    ],
    'uid_current' => [
        'id' => 'uid_current',
        'table' => 'node_field_data',
        'field' => 'uid_current',
        'entity_type' => 'node',
        'plugin_id' => 'uid_current',
        'value' => '1',
    ],
];

$sorts = [
    'created' => [
        'id' => 'created',
        'table' => 'node_field_data',
        'field' => 'created',
        'entity_type' => 'node',
        'entity_field' => 'created',
        'plugin_id' => 'date',
        'order' => 'DESC',
    ],
];

$header = [
    'area' => [
        'id' => 'area',
        'table' => 'views',
        'field' => 'area',
        'plugin_id' => 'text',
        'empty' => FALSE,
        'content' => [
            'value' => '<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><h2 class="gvquest-dashboard-heading mb-0">My Books</h2><a class="inline-flex items-center gap-2 rounded-full bg-brand-accent px-4 py-2 text-sm font-semibold text-brand-deep shadow-md hover:bg-orange-400" href="/node/add/book">+ Upload Book</a></div>',
            'format' => 'full_html',
        ],
    ],
];

$display_default = [
    'id' => 'default',
    'display_title' => 'Default',
    'display_plugin' => 'default',
    'position' => 0,
    'display_options' => [
        'title' => 'My Books',
        'use_more' => FALSE,
        'access' => [
            'type' => 'perm',
            'options' => [
                'perm' => 'access content',
            ],
        ],
        'cache' => [
            'type' => 'tag',
        ],
        'query' => [
            'type' => 'views_query',
            'options' => [],
        ],
        'exposed_form' => [
            'type' => 'basic',
        ],
        'pager' => [
            'type' => 'mini',
            'options' => [
                'items_per_page' => 12,
                'offset' => 0,
            ],
        ],
        'style' => [
            'type' => 'default',
            'options' => [
                'grouping' => [],
                'row_class' => 'gvquest-card flex flex-col gap-4',
                'default_row_class' => FALSE,
            ],
        ],
        'row' => [
            'type' => 'fields',
            'options' => [],
        ],
        'fields' => $fields,
        'filters' => $filters,
        'sorts' => $sorts,
        'header' => $header,
        'footer' => [],
        'empty' => [],
        'relationships' => [],
        'display_extenders' => [],
    ],
];

$display_page = [
    'id' => 'page_1',
    'display_title' => 'Dashboard',
    'display_plugin' => 'page',
    'position' => 1,
    'display_options' => [
        'path' => 'dashboard',
        'menu' => [
            'type' => 'none',
            'title' => '',
            'description' => '',
            'weight' => 0,
            'expanded' => FALSE,
            'menu_name' => 'main',
            'parent' => '',
            'context' => 0,
        ],
        'display_extenders' => [],
    ],
];

$view_config = [
    'langcode' => 'en',
    'status' => TRUE,
    'dependencies' => $dependencies,
    'id' => $view_id,
    'label' => 'My Books',
    'module' => 'views',
    'description' => 'Shows books uploaded by the current user.',
    'tag' => '',
    'base_table' => 'node_field_data',
    'base_field' => 'nid',
    'display' => [
        'default' => $display_default,
        'page_1' => $display_page,
    ],
];

$view = View::load($view_id);
if (!$view) {
    $view = View::create($view_config);
} else {
    foreach ($view_config as $key => $value) {
        $view->set($key, $value);
    }
}
$view->save();

echo "My Books view configured." . PHP_EOL;
