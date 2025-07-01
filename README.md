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
### Note 8: (My question to chat) Why when I provide the mutation query with a non-existing id, in js I get an exception and the code in the catch block is executed, while in php it is not?
> Below is the start of my sent question for him, for your reference.
This code is from `resolvers.js` where I am using apollo-server, on sending an incorrect trackId to the endpoint, the catch block gets executed
```js
	Mutation: {
		incrementTrackNumberOfViews: async (_, {id}, {dataSources}) => {
			try {
			const track = await dataSources.trackAPI.incrementTrackNumberOfViews(id)
			return {
				code: 200,
				success: true,
				message: `successfully increment numberOfViews of track of id: ${id}`,
				track
			}
		} catch(e) {
			return {
				code: e.extensions.response.status,
				success: false,
				message: e.extensions.response.body,
				track: null
			}
		}
	}
}
```

but in this code equivalent in php, the catch block is never executed since a php warning is triggered instead.
What can I do to make the code in the catch block get executed?

```php
$mutationType = new ObjectType([
  'name' => 'Mutation',
  'fields' => [
    'incrementTrackNumberOfViews' => [
      'type' => new IncrementTrackNumberOfViewsResponse(),
      'args' => [
        'id' => Type::id(),
      ],
      'resolve' => function($_, $args) {
        try {
          $track = Track::incrementNumberOfViewsForTrackWithId($args['id']);
          return [
            'code' => 200,
            'success' => true,
            'message' => "Hooray! The numberOfViews for track of id: {$args['id']} were updated successfully",
            'track' => $track
          ];
        } catch( \Error $e) {
          // For debugging
          var_dump('an err happened on fetching from the endpoint!');
          var_dump($e->getMessage());
          return [
            'code' => 500,
            'success' => false,
            'message' => 'An error occurred. Please try again later',
          ];
        }
      }
    ]
  ]
]);
```
I thought about removing try..catch since it is not necessary, and depending on the `$result` variable shape the response 
```php
$mutationType = new ObjectType([
  'name' => 'Mutation',
  'fields' => [
    'incrementTrackNumberOfViews' => [
      'type' => new IncrementTrackNumberOfViewsResponse(),
      'args' => [
        'id' => Type::id(),
      ],
       'resolve' => function($_, $args) {
          $track = Track::incrementNumberOfViewsForTrackWithId($args['id']);
          // var_dump($track);//null
          if(is_null($track)) {
             return [
            'code' => 500,
            'success' => false,
            'message' => 'An error occurred. Please try again later',
            'track' => $track
          ];
          
          return [
            'code' => 200,
            'success' => true,
            'message' => "Hooray! The numberOfViews for track of id: {$args['id']} were updated successfully",
            'track' => null
          ];
         
        }
      }
    ]
  ]
]);
```

This solution works, but I wonder why in js the catch block is executed, while in php it is not, even though I am using the same endpoint
> End of my question

He provided a great answer to me along with some recommendations:
> I wonder why in js the catch block is executed, while in php it is not, even though I am using the same endpoint?  
=> Because in js, even though a returned response with a 404 status code is considered a successful http response, but libraries like `apollo-datasource-rest` **throws an exception** when `response.ok` is false. This is intentional behavior.
```js
throw new ApolloError('Failed', 404, response.body)
```

*My interrupting comment*: I personally searched github and couldn't find a class called `ApolloError` in apollo code, but instead `GraphqlError` which was imported from `graphql-js` package.  
But I get the point that chat wants to explain.

The previously explained behavior is not done automatically for us in php, that's why we get a warning, and `catch` block doesn't get executed.  

Recommended solutions:
1. convert warnings to excpetions using `set_error_handler` (only in our resolve function, we will restore everything to normal after that)
2. Check `$track` if it is null or not (as I did initially)
3. Use `Guzzle` which throws exceptions by default for php errors
```php
$client = new \GuzzleHttp\Client();
try {
    $response = $client->request('GET', $url);
} catch (\GuzzleHttp\Exception\RequestException $e) {
    // This WILL be caught
}
```
I kept the 1st and 2nd approaches in the code for your reference, so feel free to use either in the mutation `incrementTrackNumberOfViews` resolver.
___
### Note 9: A nice note about `Throwable`, `Error` and `Exception`
Quoting [from the docs](https://www.php.net/manual/en/class.throwable.php#throwable.intro):
>Throwable is the base interface for any object that can be thrown via a `throw` statement, including `Error` and `Exception`.   

Another good quote:  
> PHP classes cannot implement the **Throwable** interface directly, and must instead extend `Exception`.
___
### Note 10: Regarding testing the queries using an api client
You can use whatever api client you want, but there is one gotcha I want you to be aware of:   
When you want to make a `PATCH` request to the endpoint that the datsource uses, you still need to make **a `POST` request** to our graphql endpoint, because regardless of what you want to do with other external resources, you always have to call our only endpoint with `POST` as a request method (so don't think that since you want to make a `PATCH` request to the data source that you need to make a `PATCH` request to our graphql endpoint).

Technically you could modify this part in `public/index.php`:
```php
if(
  // airbnb style
  // https://github.com/airbnb/javascript?tab=readme-ov-file#control-statements
  $_SERVER['REQUEST_METHOD'] === 'POST' 
  && $_SERVER['REQUEST_URI'] === "$base_url/graphql"
  ) {
  GraphQLController::handle();
}
```
by removing the conditional, but I think keeping it aligns with graphql standards.

## Scattered notes
### Note 11: moving from `src` to `app`

I wanted to move `src/Controller/GraphQLController.php` to `app/Http/Controllers/GraphQLController.php` to follow laravel's style.

I used this command:
```bash
git mv ./src/Controller/GraphQLController.php ./app/Http/Controllers/GraphQLController.php
```

Why `git mv` ?  
=> So that git can recognize this as a rename operation and not a delete/create a new file operation.

But the command didn't work!

After searching, it seems that the `mv` command can't create subdirectories if they are not present.

So the correct approach is to:
```bash
mkdir -p app/Http/Controllers; mv src/Controller/GraphQLController.php $_
```

What about the variable `$_` ?
It holds the last argument passed to the previous command, it is just easier than retyping the path that we want(which is `app/Http/Controllers`)

I got the command from [this answer on stackoverflow](https://stackoverflow.com/questions/547719/is-there-a-way-to-make-mv-create-the-directory-to-be-moved-to-if-it-doesnt-exis).

___

### Note 12: What happened to `src/Controller` after they became empty?

Since git tracks files and not directories, they were simply discarded by git.   
If you inspect the source code in the branch `change-project-dir-structure`, you will see that these dirs no longer exist.
