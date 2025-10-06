<?php

namespace Drupal\gvquest_analytics\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the ReadingEvent content entity.
 *
 * @ContentEntityType(
 *   id = "reading_event",
 *   label = @Translation("Reading event"),
 *   label_collection = @Translation("Reading events"),
 *   label_singular = @Translation("reading event"),
 *   handlers = {
 *     "access" = "Drupal\gvquest_analytics\ReadingEventAccessControlHandler"
 *   },
 *   base_table = "gvquest_reading_event",
 *   admin_permission = "view all analytics",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *   }
 * )
 */
class ReadingEvent extends ContentEntityBase implements ContentEntityInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(static::class . '::getCurrentUserId')
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -10,
      ]);

    $fields['book_nid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Book'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'node')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -9,
      ]);

    $fields['started_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Started at'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => -8,
      ]);

    $fields['ended_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Ended at'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => -7,
      ]);

    $fields['duration'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Duration (seconds)'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -6,
      ]);

    $fields['pages_delta'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Pages read'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -5,
      ]);

    $fields['current_page'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Current page'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -4,
      ]);

    $fields['percent_complete'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Percent complete'))
      ->setSetting('precision', 5)
      ->setSetting('scale', 2)
      ->setDefaultValue('0.00')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -3,
        'settings' => ['placeholder' => '0-100'],
      ]);

    $fields['source'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Source'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'manual' => t('Manual'),
        'pdfjs' => t('PDF.js'),
        'epubjs' => t('ePub.js'),
      ])
      ->setDefaultValue('manual')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -2,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }

  /**
   * Default owner callback.
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
    if (!$this->get('duration')->value) {
      $duration = max(0, (int) $this->get('ended_at')->value - (int) $this->get('started_at')->value);
      $this->set('duration', $duration);
    }
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
  public function getOwnerId() {
    return (int) $this->get('user_id')->target_id;
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
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

}
