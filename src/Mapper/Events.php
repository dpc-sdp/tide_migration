<?php
namespace Drupal\tide_migration\Mapper;

class Events {

  /**
   * @param array $json_response
   * @return null
   */
  public function convert(?array $json_response): ?array {
    if (empty($json_response)) {
      return NULL;
    }

    $event_categories = $this->mapEventsCategories($json_response);
    $event_image_files = $this->mapEventsImageFiles($json_response);
    $event_topics = $this->mapEventsTopics($json_response);
    $event_featured_images = $this->mapEventsFeaturedImage($json_response, $event_image_files, $event_topics);
    $event_tags = $this->mapEventsTags($json_response);
    $event_audience = $this->mapEventsAudience($json_response);
    $event_requirements = $this->mapEventsRequirements($json_response);
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

  private function lookUpFeaturedImage(array $config, array $event_featured_images) {
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

  private function lookUpParagraph(array $config, array $details) {
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

  private function lookUpImageFile(array $config, array $image_files) {
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

  private function lookUpTaxonomy(array $config, array $taxonomies_data) {
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

  private function mapEventsRequirements(array $content) {
    if (empty($content)) {
      return NULL;
    }

    $requirements = [];

    foreach ($content['included'] as $data) {
      if ($data['type'] === 'taxonomy_term--event_requirements') {
        $requirements = array_merge($this->mapEventTaxonomy($data), $requirements);
      }
    }

    return $requirements;
  }

  private function mapEventsImageFiles(array $content) {
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

  private function mapEventsCategories(array $content) {
    if (empty($content)) {
      return NULL;
    }

    $categories = [];

    foreach ($content['included'] as $data) {
      if ($data['type'] === 'taxonomy_term--event') {
        $categories = array_merge($this->mapEventTaxonomy($data), $categories);
      }
    }

    return $categories;
  }

  private function mapEventsTopics(array $content) {
    if (empty($content)) {
      return NULL;
    }

    $topics = [];

    foreach ($content['included'] as $data) {
      if ($data['type'] === 'taxonomy_term--topic') {
        $topics = array_merge($this->mapEventTaxonomy($data), $topics);
      }
    }

    return $topics;
  }

  private function mapEventsTags(array $content) {
    if (empty($content)) {
      return NULL;
    }

    $tags = [];

    foreach ($content['included'] as $data) {
      if ($data['type'] === 'taxonomy_term--tags') {
        $tags = array_merge($this->mapEventTaxonomy($data), $tags);
      }
    }

    return $tags;
  }

  private function mapEventsAudience(array $content) {
    if (empty($content)) {
      return NULL;
    }

    $audience = [];

    foreach ($content['included'] as $data) {
      if ($data['type'] === 'taxonomy_term--audience') {
        $audience = array_merge($this->mapEventTaxonomy($data), $audience);
      }
    }

    return $audience;
  }

  private function mapEventsFeaturedImage(array $content, array $image_files, array $topics) {
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

  private function mapEventDetails(array $content, $event_requirements) {
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

  private function mapEventTaxonomy(array $config) {
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

  private function mapEventDetail(array $content, array $event_requirements) {
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

  private function mapFeaturedImage(array $content, array $image_files, array $topics) {
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
