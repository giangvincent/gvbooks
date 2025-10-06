<?php

namespace Drupal\gvquest_streaks\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the ReadingLog content entity.
 *
 * @ContentEntityType(
 *   id = "reading_log",
 *   label = @Translation("Reading log"),
 *   label_collection = @Translation("Reading logs"),
 *   label_singular = @Translation("reading log"),
 *   handlers = {
 *     "access" = "Drupal\gvquest_streaks\ReadingLogAccessControlHandler"
 *   },
 *   base_table = "gvquest_reading_log",
 *   admin_permission = "view all reading logs",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *   }
 * )
 */
class ReadingLog extends ContentEntityBase implements ContentEntityInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setDefaultValueCallback(static::class . '::getCurrentUserId')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -10,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['book_nid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Book'))
      ->setSetting('target_type', 'node')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -9,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => t('Search for a book node'),
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['log_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Log date'))
      ->setRequired(TRUE)
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['pages_read'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Pages read'))
      ->setSetting('min', 0)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['minutes_read'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Minutes read'))
      ->setSetting('min', 0)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['percent_complete'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Percent complete'))
      ->setSetting('precision', 5)
      ->setSetting('scale', 2)
      ->setDefaultValue('0.00')
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -5,
        'settings' => [
          'placeholder' => '0-100',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['source'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Source'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'manual' => t('Manual'),
        'pdfjs' => t('PDF.js'),
        'epubjs' => t('ePub.js'),
      ])
      ->setDefaultValue('manual')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setTranslatable(FALSE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setTranslatable(FALSE);

    return $fields;
  }

  /**
   * Default value callback for user_id.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      $this->setOwnerId(\Drupal::currentUser()->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

}
