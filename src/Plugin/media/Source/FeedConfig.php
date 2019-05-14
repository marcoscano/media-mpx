<?php

namespace Drupal\media_mpx\Plugin\media\Source;

use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceInterface;
use Lullabot\Mpx\DataService\DataObjectFactory;
use Lullabot\Mpx\DataService\Feeds\FeedConfig as MpxFeedConfig;
use Lullabot\Mpx\Exception\ClientException;

/**
 * Media source for mpx FeedConfig items.
 *
 * @see \Lullabot\Mpx\DataService\Feeds\FeedConfig
 * @see https://docs.theplatform.com/help/feeds-feedconfig-endpoint
 *
 * @todo Change the default thumbnail.
 *
 * @MediaSource(
 *   id = "media_mpx_feedconfig",
 *   label = @Translation("mpx FeedConfig"),
 *   description = @Translation("mpx FeedConfig data."),
 *   allowed_field_types = {"string"},
 *   default_thumbnail_filename = "video.png",
 *   thumbnail_alt_metadata_attribute="title",
 *   thumbnail_title_metadata_attribute="title",
 *   media_mpx = {
 *     "service_name" = "Feeds Data Service",
 *     "object_type" = "FeedConfig",
 *     "schema_version" = "2.2",
 *   },
 * )
 */
class FeedConfig extends MediaSourceBase implements MediaSourceInterface {

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    $extractor = $this->propertyExtractor();

    $metadata = [];
    foreach ($extractor->getProperties(MpxFeedConfig::class) as $property) {
      $metadata[$property] = $extractor->getShortDescription(MpxFeedConfig::class, $property);
    }

    return $metadata;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $attribute_name) {
    /** @var \Drupal\media\MediaTypeInterface $media_type */
    $media_type = $this->entityTypeManager->getStorage('media_type')->load($media->bundle());
    $source_field = $this->getSourceFieldDefinition($media_type);

    if (!$media->get($source_field->getName())->isEmpty()) {
      switch ($attribute_name) {
        case 'thumbnail_uri':
          $this->getThumbnailUri($media);
          break;

        case 'thumbnail_alt':
          return $this->thumbnailAlt($media);

        default:
          $extractor = $this->propertyExtractor();

          if (in_array($attribute_name, $extractor->getProperties(MpxFeedConfig::class))) {
            return $this->getReflectedProperty($media, $attribute_name, $this->getMpxObject($media));
          }
      }
    };

    return parent::getMetadata($media, $attribute_name);
  }

  /**
   * Return the thumbnail for a feed.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The feed config media entity.
   *
   * @return string|null
   *   The thumbnail URL, or NULL if one cannot be found.
   */
  private function getThumbnailUri(MediaInterface $media): ?string {
    /** @var \Lullabot\Mpx\DataService\Feeds\FeedConfig $feed */
    $feed = $this->getMpxObject($media);
    $pinned_ids = $feed->getPinnedIds();
    return $this->getFirstValidThumbnail($pinned_ids);
  }

  /**
   * Return the first valid thumbnail URI if one exists.
   *
   * @param int[] $pinned_ids
   *   An array of pinned video IDs.
   *
   * @return string|null
   *   The thumbnail URL, or NULL if one cannot be found.
   */
  private function getFirstValidThumbnail(array $pinned_ids) {
    // Unlike the rest of the mpx API, these IDs are numeric and don't
    // include the host name.
    $factory = $this->dataObjectFactoryCreator->forObjectType($this->getAccount()->getUserEntity(), 'Media Data Service', 'Media', '1.10');
    foreach ($pinned_ids as $video_id) {
      try {
        $video = $factory->loadByNumericId($video_id)->wait();
        return $this->downloadThumbnail($video);
      }
      catch (ClientException $e) {
        // Mpx doesn't removed pinned videos from feeds if the video is
        // deleted. In that case, we go on to the next video to find a
        // thumbnail.
        if ($e->getCode() != 404) {
          throw $e;
        }
      }
    }

    return NULL;
  }

}
