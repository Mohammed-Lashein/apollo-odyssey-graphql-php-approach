<?php

namespace Core;

use GraphQL\Type\Definition\ObjectType;

class TypeRegistry {
  private static array $types = [];
  public static function type(string $classname): ObjectType {
    // used in https://github.com/webonyx/graphql-php/blob/master/examples/01-blog/Blog/TypeRegistry.php
    // but I prefer the verbose approach

    // return self::$types[$classname] ??= new $classname();

    if(!isset(self::$types[$classname])) {
      self::$types[$classname] = new $classname();
    }
    return self::$types[$classname];
  }
}