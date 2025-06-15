<?php

namespace App\Models;

use Core\Model;

class Track extends Model {
  public static function all() {
    return static::get('tracks');
  }
  public static function find($id) {
    return static::get("track/$id");
  }
}