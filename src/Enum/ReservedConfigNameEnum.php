<?php

namespace Drupal\tide_migration\Enum;

class ReservedConfigNameEnum {
  const SITE = 'site';
  const EVENT_URLS = 'event_urls';

  private function getReservedNames(){
    return [
      self::SITE,
      self::EVENT_URLS
    ];
  }

  public function validate($name) {
    return in_array($name, $this->getReservedNames());
  }
}
