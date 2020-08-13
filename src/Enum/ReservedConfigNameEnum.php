<?php

namespace Drupal\tide_migration\Enum;

class ReservedConfigNameEnum {
  const SITE = 'site';

  private function getReservedNames(){
    return [
      self::SITE,
    ];
  }

  public function validate($name) {
    return in_array($name, $this->getReservedNames());
  }
}
