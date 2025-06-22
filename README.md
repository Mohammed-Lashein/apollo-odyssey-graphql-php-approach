Here I make the api done in [Apollo Odyssey graphql course](https://www.apollographql.com/tutorials/lift-off-part1) using php (to train on using graphql in php).

You can also find the notes I took during this journey, and I believe that you will learn a thing or two as I have learnt a lot!

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
___
### Note 7: How to send a patch request from php to an endpoint?

On my way to answer this question I have found great resources.

Here the journey starts:

1. I asked chat to generate me a method that will be similar to the [patch method from the `RestDataSource` class we used in apollo odyssey liftoff-4](https://www.apollographql.com/tutorials/lift-off-part4/04-updating-our-trackapi-data-source)

*An interrupting question :* If you inspect the source code of the `patch` method, you will find:
```ts
  protected async patch<TResult = any>(
    path: string,
    request?: PatchRequest<CO>,
  ): Promise<TResult> {
    return (
      await this.fetch<TResult>(path, {
        method: 'PATCH',
        ...request,
      })
    ).parsedBody;
  }
```
So the `patch` method is simply a wrapper for `fetch`.

The challenge was to mimic this behavior in php.
Instead of re-inventing the wheel, I asked chat to go and generate some code. 
With providing more context, he could granularly create a prototype for the method:
```php
protected static function patch($endpoint) {
  $url = static::$basePath . $endpoint;

  $options = [
    'http' => [
      'method' => 'PATCH',
      'header' => "Content-Type: application/json\r\n",
      'content' => '', // No body required
      'ignore_errors' => true,
    ],
  ];

  $context = stream_context_create($options);
  $res = file_get_contents($url, false, $context);

  if ($res === false) {
    $error = error_get_last();
    throw new Exception("PATCH request failed: " . $error['message']);
  }

  return json_decode($res, true);
}
```

Oh! What's the `stream_contenxt_create` ?

2. Now I had to go
   1.  read in the docs about it (where the explanation doesn't make sense the first time you read it)
   2.  [search stackoverflow](https://stackoverflow.com/questions/17394619/stream-context-in-php-what-is-it)
   3.  [tinker around the suggested links in stackoverflow question's comments](https://stackoverflow.com/a/29359199/16385537)
   4.  ask chat for an explanation

Links from the docs you can read as a quickstart:  
https://www.php.net/manual/en/function.stream-context-create.php  
https://www.php.net/manual/en/context.http.php  
https://www.php.net/manual/en/intro.stream.php  


Regarding the article mentioned in [this comment](https://stackoverflow.com/a/29359199/16385537), the article was published on a wordpress website whose theme is [created by this author](https://wordpress.org/themes/author/samikeijonen/).
I may download the themes and navigate them when I want to learn from real world wordpress themes that are both functioning and well designed!

### TL;DR
the `stream_context_create` is like passing `options` object to `fetch` in js.
It simply adds metadata to the request we are sending to the target resource, instead of the basic functionality of just fetching data from the resource (using `GET` request, the default http method).
______
