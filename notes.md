### Note 1: How does a graphql server parse a query when we remove the **\n** we are used to writing in our queries ?
=> The new lines are just syntacitc sugar to enhance queries readability, but any graphql server depends on whitespaces to parse our queries. 

I haven't spent much time learning about how does a graphql server parse the query, maybe it is a topic for another time.
___
### Note 2: Can we add the `resolve` field within the `ObjectType` config array?
```php
class ModuleType extends ObjectType {
  public function __construct() {
    $config = [
      'fields' => [
        'id' => Type::id(),
        'title' => Type::string(),
        'length' => Type::int()
        // We don't write the resolve here. It is written in the parent type
        /* Technically can we write it here ? */
      ],
      'resolve' => fn($parent) => Module::all($parent['id'])
    ];
    parent::__construct($config);
  }
}
```

=> NO. The `resolve` field is not to be mixed with `fields` declaration on extending `ObjectType`.
This will not work and you will get null instead in the response of the graphql server.
___
### Note 3: When you declare a field type that is different from the result of the `resolve` function:
In the following code: 
```php
class TrackType extends ObjectType {
  public function __construct() {
    $config = [
      'fields' => [
        // .... other fields
        'modules' => [
          'type' => new ModuleType(), // Error! should be Type::listOf(new ModuleType()) instead.
          'resolve' => fn($parent) => Module::all($parent['id'])
        ]
      ]
      ];
    parent::__construct($config);
  }
}
```
Since the API returns an array of modules, the graphql server will return the value of each queried field as `null`.
