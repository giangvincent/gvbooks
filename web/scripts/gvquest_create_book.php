<?php

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Vocabulary;

$entityTypeManager = \Drupal::entityTypeManager();

$bookTypeId = 'book';
$nodeTypeStorage = $entityTypeManager->getStorage('node_type');
if (!$nodeTypeStorage->load($bookTypeId)) {
    $nodeType = $nodeTypeStorage->create([
        'type' => $bookTypeId,
        'name' => 'Book',
        'description' => 'Content type for uploading and cataloging books.',
        'new_revision' => TRUE,
        'display_submitted' => TRUE,
    ]);
    $nodeType->save();
}

$vocabularyId = 'tags';
$vocabularyStorage = $entityTypeManager->getStorage('taxonomy_vocabulary');
if (!$vocabularyStorage->load($vocabularyId)) {
    $vocabulary = $vocabularyStorage->create([
        'vid' => $vocabularyId,
        'description' => 'Reusable tags for labeling books.',
        'name' => 'Tags',
    ]);
    $vocabulary->save();
}

$fieldStorages = [
    'field_book_file' => [
        'type' => 'file',
        'settings' => [
            'uri_scheme' => 'public',
            'target_type' => 'file',
        ],
        'cardinality' => 1,
        'translatable' => FALSE,
    ],
    'field_cover_image' => [
        'type' => 'image',
        'settings' => [
            'uri_scheme' => 'public',
            'default_image' => [
                'uuid' => NULL,
                'alt' => '',
                'title' => '',
                'width' => NULL,
                'height' => NULL,
            ],
        ],
        'cardinality' => 1,
        'translatable' => FALSE,
    ],
    'field_description' => [
        'type' => 'text_with_summary',
        'settings' => [
            'max_length' => 0,
        ],
        'cardinality' => 1,
        'translatable' => TRUE,
    ],
    'field_tags' => [
        'type' => 'entity_reference',
        'settings' => [
            'target_type' => 'taxonomy_term',
        ],
        'cardinality' => -1,
        'translatable' => TRUE,
    ],
];

foreach ($fieldStorages as $fieldName => $definition) {
    if (!FieldStorageConfig::loadByName('node', $fieldName)) {
        $storage = FieldStorageConfig::create([
            'field_name' => $fieldName,
            'entity_type' => 'node',
            'type' => $definition['type'],
            'settings' => $definition['settings'],
            'cardinality' => $definition['cardinality'],
            'translatable' => $definition['translatable'],
        ]);
        $storage->save();
    }
}

$fieldConfigs = [
    'field_book_file' => [
        'label' => 'Book file',
        'description' => 'Upload the book file (PDF, DOCX, or TXT).',
        'required' => TRUE,
        'settings' => [
            'description_field' => TRUE,
            'display_field' => FALSE,
            'display_default' => FALSE,
            'file_directory' => 'books/files',
            'file_extensions' => 'pdf doc docx txt',
            'max_filesize' => '',
        ],
    ],
    'field_cover_image' => [
        'label' => 'Cover image',
        'description' => 'Optional cover artwork.',
        'required' => FALSE,
        'settings' => [
            'alt_field' => TRUE,
            'title_field' => FALSE,
            'default_image' => [
                'uuid' => NULL,
                'alt' => '',
                'title' => '',
                'width' => NULL,
                'height' => NULL,
            ],
            'file_directory' => 'books/covers',
            'max_filesize' => '',
            'file_extensions' => 'png jpg jpeg gif webp',
        ],
    ],
    'field_description' => [
        'label' => 'Description',
        'description' => 'Detailed synopsis for the book.',
        'required' => TRUE,
        'settings' => [
            'display_summary' => TRUE,
        ],
    ],
    'field_tags' => [
        'label' => 'Tags',
        'description' => 'Categorize this book.',
        'required' => FALSE,
        'settings' => [
            'handler' => 'default:taxonomy_term',
            'handler_settings' => [
                'target_bundles' => [
                    'tags' => 'tags',
                ],
                'auto_create' => FALSE,
                'sort' => [
                    'field' => 'name',
                    'direction' => 'asc',
                ],
            ],
        ],
    ],
];

foreach ($fieldConfigs as $fieldName => $definition) {
    if (!FieldConfig::loadByName('node', $bookTypeId, $fieldName)) {
        $field = FieldConfig::create([
            'field_name' => $fieldName,
            'entity_type' => 'node',
            'bundle' => $bookTypeId,
            'label' => $definition['label'],
            'description' => $definition['description'],
            'required' => $definition['required'],
            'settings' => $definition['settings'],
        ]);
        $field->save();
    }
}

$formDisplay = EntityFormDisplay::load('node.' . $bookTypeId . '.default');
if (!$formDisplay) {
    $formDisplay = EntityFormDisplay::create([
        'targetEntityType' => 'node',
        'bundle' => $bookTypeId,
        'mode' => 'default',
        'status' => TRUE,
    ]);
}

$formDisplay->setComponent('title', [
    'type' => 'string_textfield',
    'weight' => -20,
    'settings' => [
        'size' => 60,
        'placeholder' => '',
    ],
]);
$formDisplay->setComponent('field_cover_image', [
    'type' => 'image_image',
    'weight' => -10,
    'settings' => [
        'progress_indicator' => 'throbber',
        'preview_image_style' => 'thumbnail',
        'show_default_image' => TRUE,
    ],
]);
$formDisplay->setComponent('field_description', [
    'type' => 'text_textarea_with_summary',
    'weight' => 0,
    'settings' => [
        'rows' => 9,
        'summary_rows' => 3,
    ],
]);
$formDisplay->setComponent('field_book_file', [
    'type' => 'file_generic',
    'weight' => 10,
    'settings' => [
        'progress_indicator' => 'throbber',
    ],
]);
$formDisplay->setComponent('field_tags', [
    'type' => 'entity_reference_autocomplete_tags',
    'weight' => 20,
    'settings' => [
        'match_operator' => 'CONTAINS',
        'size' => 60,
        'autocomplete_type' => 'tags',
        'placeholder' => '',
    ],
]);
$formDisplay->save();

$viewDisplay = EntityViewDisplay::load('node.' . $bookTypeId . '.default');
if (!$viewDisplay) {
    $viewDisplay = EntityViewDisplay::create([
        'targetEntityType' => 'node',
        'bundle' => $bookTypeId,
        'mode' => 'default',
        'status' => TRUE,
    ]);
}

$viewDisplay->setComponent('field_cover_image', [
    'label' => 'hidden',
    'type' => 'image',
    'weight' => -10,
    'settings' => [
        'image_style' => 'large',
        'image_link' => '',
    ],
]);
$viewDisplay->setComponent('field_description', [
    'label' => 'hidden',
    'type' => 'text_default',
    'weight' => 0,
    'settings' => [],
]);
$viewDisplay->setComponent('field_book_file', [
    'label' => 'inline',
    'type' => 'file_default',
    'weight' => 10,
    'settings' => [
        'display_description' => TRUE,
    ],
]);
$viewDisplay->setComponent('field_tags', [
    'label' => 'inline',
    'type' => 'entity_reference_label',
    'weight' => 20,
    'settings' => [
        'link' => TRUE,
    ],
]);
$viewDisplay->save();

echo "Book content type and fields configured." . PHP_EOL;
