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
___
### Note 4: Is it important for the `args` field to come before the `resolve` field?
```php
    $queryType = new ObjectType([
      'name' => 'Query', 
      'fields' => [
        // ...
        'track' => [
          'type' => new TrackType(),
          // is it mandatory for the args to come before the resolve field ? Probably
          
          'resolve' => function($_ , $args) {
            if (!isset($args['trackId'])) {
                throw new \Exception("trackId was not provided in the query arguments.");
            }
            return Track::find($args['trackId']);
          },
          'args' => [
            'trackId' => Type::id()
          ],
        ]
      ]
    ]);
```
NO.The `args` needn't come before the `resolve` (this is logical because fields access isn't restricted by order. As long as the field exists, you can access it with no problems).

___
### Note 5: If you want to pass an argument to the query, you MUST give it a name
>[!warning]
> The below code won't work! It will also throw `Variable \"$my_track_id\" is not defined by operation`.
```graphql
  {
    track(trackId: $my_track_id) {
      id
      title
      modules {
        id
        title
        length
      }
    }
  }
```
Then in the variables panel: 
```json
  {
    "my_track_id": "c_0"
  }
```
>[!note]
> Below is the correct approach.
We need to explicitly mention that a variable will be passed to our query:
```graphql
  query GetTrack($my_track_id: ID) {
    track(trackId: $my_track_id) {
       id
      title
      modules {
        id
        title
        length
      }
    }
  }
```
___
### Note 6: As obvious as it is, but you should remember to pass the variables to `GraphQL::executeQuery`
I have spent a couple of minutes wondering why the `$args` array was always empty!