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
  public static function incrementNumberOfViewsForTrackWithId($id) {
    // the endpoint returns a track with numberOfViews field incremented successfully
    return static::patch("track/$id/numberOfViews");
  }
}