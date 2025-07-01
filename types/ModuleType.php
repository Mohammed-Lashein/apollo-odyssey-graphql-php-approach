<?php

namespace Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class ModuleType extends ObjectType {
  public function __construct() {
    $config = [
      'fields' => [
        'id' => Type::id(),
        'title' => Type::string(),
        'length' => Type::int()
      ],
    ];
    parent::__construct($config);
  }
}
