<?php

namespace Drupal\tide_migration\Mapper;

class Events {

  /**
   * @param array|null $json_response
   * @return array|null
   */
  public function convert(?array $json_response): ?array {
    if (empty($json_response)) {
      return NULL;
    }

    $event_categories = $this->mapTaxonomies($json_response, 'taxonomy_term--event');
    $event_image_files = $this->mapEventsImageFiles($json_response);
    $event_topics = $this->mapTaxonomies($json_response, 'taxonomy_term--topic');
    $event_featured_images = $this->mapEventsFeaturedImage($json_response, $event_image_files, $event_topics);
    $event_tags = $this->mapTaxonomies($json_response, 'taxonomy_term--tags');
    $event_audience = $this->mapTaxonomies($json_response, 'taxonomy_term--audience');
    $event_requirements = $this->mapTaxonomies($json_response, 'taxonomy_term--event_requirements');
    $event_details = $this->mapEventDetails($json_response, $event_requirements);

    $content['field_event_category'] = $event_categories;
    $content['field_event_requirements'] = $event_requirements;
    $content['field_event_details'] = $event_details;
    $content['field_topic'] = $event_topics;
    $content['field_tags'] = $event_tags;
    $content['field_audience'] = $event_audience;
    $content['field_media_image'] = $event_image_files;
    $content['field_featured_image'] = $event_featured_images;

    $content['events'] = $this->mapEvents(
      $json_response,
      $event_categories,
      $event_topics,
      $event_tags,
      $event_audience,
      $event_details,
      $event_featured_images
    );

    return $content;
  }

  /**
   * @param array|null $content
   * @param array|null $event_categories
   * @param array|null $event_topics
   * @param array|null $event_tags
   * @param array|null $event_audience
   * @param array|null $event_details
   * @param array|null $event_featured_images
   * @return array|null
   */
  private function mapEvents(
    ?array $content,
    ?array $event_categories,
    ?array $event_topics,
    ?array $event_tags,
    ?array $event_audience,
    ?array $event_details,
    ?array $event_featured_images
  ): ?array {
    if (empty($content)) {
      return NULL;
    }

    $events = [];

    foreach ($content['data'] as $data) {
      $attributes = $data['attributes'];
      $event['drupal_internal__nid'] = $attributes['drupal_internal__nid'];
      $event['title'] = $attributes['title'];
      $event['moderation_state'] = $attributes['moderation_state'];
      $event['body'] = $attributes['body'];
      $event['path'] = $attributes['path'];
      $event['metatag_normalized'] = $attributes['metatag_normalized'];
      $event['field_landing_page_show_contact'] = $attributes['field_landing_page_show_contact'];
      $event['field_landing_page_summary'] = $attributes['field_landing_page_summary'];
      $event['field_event_description'] = $attributes['field_event_description'];
      $event['field_news_intro_text'] = $attributes['field_news_intro_text'];
      $event['field_node_author'] = $attributes['field_node_author'];
      $event['field_node_email'] = $attributes['field_node_email'];
      $event['field_node_link'] = $attributes['field_node_link'];
      $event['field_node_phone'] = $attributes['field_node_phone'];
      $event['field_show_content_rating'] = $attributes['field_show_content_rating'];
      $event['field_show_related_content'] = $attributes['field_show_related_content'];
      $event['field_show_social_sharing'] = $attributes['field_show_social_sharing'];
      $event['field_tracking_beacon'] = $attributes['field_tracking_beacon'];
      $event['field_topic'] = $this->lookUpTaxonomy($data['relationships']['field_topic'], $event_topics);
      $event['field_tags'] = $this->lookUpTaxonomy($data['relationships']['field_tags'], $event_tags);
      $event['field_audience'] = $this->lookUpTaxonomy($data['relationships']['field_audience'], $event_audience);
      $event['field_event_category'] = $this->lookUpTaxonomy($data['relationships']['field_event_category'], $event_categories);
      $event['field_event_details'] = $this->lookUpParagraph($data['relationships']['field_event_details'], $event_details);
      $event['field_featured_image'] = $this->lookUpFeaturedImage($data['relationships']['field_featured_image'], $event_featured_images);

      $events[] = $event;
    }

    return $events;
  }

  /**
   * @param array $config
   * @param array $event_featured_images
   * @return array
   */
  private function lookUpFeaturedImage(array $config, array $event_featured_images): array {
    $data = $config['data'];
    $featured_image = [];

    $id = $data['id'];

    foreach ($event_featured_images as $event_featured_image) {
      if ($id === $event_featured_image['id']) {
        $featured_image = $event_featured_image;
      }
    }

    return $featured_image;
  }

  /**
   * @param array $config
   * @param array $details
   * @return array
   */
  private function lookUpParagraph(array $config, array $details): array {
    $data = $config['data'];
    $paragraphs = [];

    foreach ($data as $item) {
      $id = $item['id'];

      foreach ($details as $detail) {
        if ($id === $detail['id']) {
          $paragraphs[] = $detail;
        }
      }
    }

    return $paragraphs;
  }

  /**
   * @param array $config
   * @param array $image_files
   * @return array
   */
  private function lookUpImageFile(array $config, array $image_files): array {
    $data = $config['data'];
    $media = [];

    $id = $data['id'];

    foreach ($image_files as $image_file) {
      if ($id === $image_file['id']) {
        $media = $image_file;
      }
    }

    return $media;
  }

  /**
   * @param array $config
   * @param array $taxonomies_data
   * @return array
   */
  private function lookUpTaxonomy(array $config, array $taxonomies_data): array {
    $data = $config['data'];
    $taxonomies = [];

    if (isset($data['id'])) {
      if (isset($taxonomies_data['id'])) {
        if ($data['id'] === $taxonomies_data['id']) {
          $taxonomies[$taxonomies_data['name']] = $taxonomies_data;
        }
      } else {
        foreach ($taxonomies_data as $taxonomy) {
          if ($data['id'] === $taxonomy['id']) {
            $taxonomies[$taxonomy['name']] = $taxonomy;
          }
        }
      }
    } else {
      foreach ($data as $item) {
        if (isset($taxonomies_data['id'])) {
          if ($item['id'] === $taxonomies_data['id']) {
            $taxonomies[$taxonomies_data['name']] = $taxonomies_data;
          }
        } else {
          foreach ($taxonomies_data as $taxonomy) {
            if ($item['id'] === $taxonomy['id']) {
              $taxonomies[$taxonomy['name']] = $taxonomy;
            }
          }
        }
      }
    }

    return $taxonomies;
  }

  /**
   * @param array $content
   * @return array|null
   */
  private function mapEventsImageFiles(array $content): ?array {
    if (empty($content)) {
      return NULL;
    }

    $image_files = [];

    foreach ($content['included'] as $data) {
      if ($data['type'] === 'file--file') {
        $image_files[] = [
          'id' => $data['id'],
          'drupal_internal__fid' => $data['attributes']['drupal_internal__fid'],
          'filename' => pathinfo($data['attributes']['filename'], PATHINFO_FILENAME),
          'langcode' => $data['attributes']['langcode'],
          'url' => $data['attributes']['url'],
          'uri' => $data['attributes']['uri'],
          'status' => $data['attributes']['status'],
        ];
      }
    }

    return $image_files;
  }

  /**
   * @param array $content
   * @param string $type
   * @return array|null
   */
  private function mapTaxonomies(array $content, string $type): ?array {
    if (empty($content)) {
      return NULL;
    }

    $taxonomies = [];

    foreach ($content['included'] as $data) {
      if ($data['type'] === $type) {
        $taxonomies = array_merge($this->mapEventTaxonomy($data), $taxonomies);
      }
    }

    return $taxonomies;
  }

  /**
   * @param array $config
   * @return array|array[]
   */
  private function mapEventTaxonomy(array $config): array {
    $parent_config = $config['relationships']['parent']['data'];
    $parent_initial = array_shift($parent_config);
    $term_explode = explode('--', $parent_initial['type']);
    $parent = $term_explode[1];

    return [
      $config['attributes']['name'] => [
        'id' => $config['id'],
        'drupal_internal__tid' => $config['attributes']['drupal_internal__tid'],
        'name' => $config['attributes']['name'],
        'parent' => $parent,
        'langcode' => $config['attributes']['langcode'],
        'description' => $config['attributes']['description'],
        'weight' => $config['attributes']['weight'],
      ]
    ];
  }

  /**
   * @param array $content
   * @param $event_requirements
   * @return array|null
   */
  private function mapEventDetails(array $content, $event_requirements): ?array {
    if (empty($content)) {
      return NULL;
    }

    $event_details = [];

    foreach ($content['included'] as $data) {
      if ($data['type'] === 'paragraph--event_details') {
        $event_details[] = $this->mapEventDetail($data, $event_requirements);
      }
    }

    return $event_details;
  }

  /**
   * @param array $content
   * @param array $image_files
   * @param array $topics
   * @return array|null
   */
  private function mapEventsFeaturedImage(array $content, array $image_files, array $topics): ?array {
    if (empty($content)) {
      return NULL;
    }

    $featured_images = [];

    foreach ($content['included'] as $data) {
      if ($data['type'] === 'media--image') {
        $featured_images[] = $this->mapFeaturedImage($data, $image_files, $topics);
      }
    }

    return $featured_images;
  }

  /**
   * @param array $content
   * @param array $event_requirements
   * @return array
   */
  private function mapEventDetail(array $content, array $event_requirements): array {
    return [
      'id' => $content['id'],
      'type' => $content['type'],
      'drupal_internal__id' => $content['attributes']['drupal_internal__id'],
      'langcode' => $content['attributes']['langcode'],
      'status' => $content['attributes']['status'],
      'field_paragraph_date_range' => $content['attributes']['field_paragraph_date_range'],
      'field_paragraph_event_price_from' => $content['attributes']['field_paragraph_event_price_from'],
      'field_paragraph_event_price_to' => $content['attributes']['field_paragraph_event_price_to'],
      'field_paragraph_link' => $content['attributes']['field_paragraph_link'],
      'field_paragraph_location' => $content['attributes']['field_paragraph_location'],
      'field_show_time' => $content['attributes']['field_show_time'],
      'field_event_requirements' => $this->lookUpTaxonomy($content['relationships']['field_event_requirements'], $event_requirements)
    ];
  }

  /**
   * @param array $content
   * @param array $image_files
   * @param array $topics
   * @return array
   */
  private function mapFeaturedImage(array $content, array $image_files, array $topics): array {
    $topic = NULL;

    $field_media_topic = $content['relationships']['field_media_topic'];

    if (!empty($field_media_topic['data'])) {
      $topic = $this->lookUpTaxonomy($field_media_topic, $topics);
    }

    return [
      'id' => $content['id'],
      'type' => $content['type'],
      'drupal_internal__mid' => $content['attributes']['drupal_internal__mid'],
      'langcode' => $content['attributes']['langcode'],
      'status' => $content['attributes']['status'],
      'name' => $content['attributes']['name'],
      'field_media_alignment' => $content['attributes']['field_media_alignment'],
      'field_media_caption' => $content['attributes']['field_media_caption'],
      'field_media_restricted' => $content['attributes']['field_media_restricted'],
      'field_media_image' => $this->lookUpImageFile($content['relationships']['field_media_image'], $image_files),
      'meta' => $content['relationships']['field_media_image']['data']['meta'],
      'field_media_topic' => $topic
    ];
  }
}
