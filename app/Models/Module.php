<?php

namespace App\Models;

use Core\Model;

class Module extends Model {
  public static function all($trackId) {
    return static::get("track/$trackId/modules");
  }
}