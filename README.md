# Laravel MySQL fulltext search

This package creates a MySQL fulltext index for models and enables you to search through those.

## Requirements

- Laravel >= 5.7
- MySQL >= 5.6 / MariaDB >= 10.0.15

## Install

1. Install with composer ``composer require provision/searchable``.
2. Publish migrations and config ``php artisan vendor:publish --tag=searchable``
3. Migrate the database ``php artisan migrate``

## Usage

The package uses a model observer to update the index when models change. If you want to run a full index you can use the console commands.

### Models

Add the ``SearchableTrait`` trait to the model you want to have indexed and define the columns you'd like to index as title and content.

#### Example
```
class Clients extends Model
{

    use \ProVision\Searchable\SearchableTrait;

    /**
     * @inheritDoc
     */
    protected function getSearchableTitleColumns(): array
    {
        return [
            'name'
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getSearchableContentColumns(): array
    {
        return [
            'description',
            'address',
            'vat_number',
            'contacts.value',
            'contactPersons.first_name',
            'contactPersons.last_name',
            'contactPersons.contacts.value',
        ];
    }

}
```

You can use a dot notation to query relationships for the model, like ``contacts.value``.

### Relation model indexing

On related model for indexing use `SearchableRelationTrait` and method `getSearchableRelationName` to return relation name.

Listen for changes on relation and update parent model

#### Example

```
class Contact extends Model
{
    use SearchableRelationTrait;

     /**
     * @return MorphTo
     */
    public function contactable()
    {
        return $this->morphTo();
    }

    /**
     * @inheritDoc
     */
    static function getSearchableRelationName(): string
    {
        return 'contactable';
    }
}
```

### Searching 

You can search using the `search` method.

```
$clientsCollection = Clients::search('John Doe')->paginate();
```

#### Search with specific fulltext search mode

```
use ProVision\Searchable\SearchableModes;
---
$clientsCollection = Clients::search('John Doe', SearchableModes::Boolean)->paginate();
```

Available modes
- `NaturalLanguage` - IN NATURAL LANGUAGE MODE
- `NaturalLanguageWithQueryExpression` - IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION 
- `Boolean` - IN BOOLEAN MODE
- `QueryExpression` - WITH QUERY EXPANSION

MySQL fulltext search documentation: https://dev.mysql.com/doc/refman/8.0/en/fulltext-search.html

#### Search with relations & additional wheres

```
$clientsCollection = Clients::search('John Doe')->where('active', 1)->with(['contacts'])->paginate();
```

#### Order searchable score

```
$clientsCollection = Clients::search('John Doe')->searchableOrder('asc')->paginate();
```

Available options:

- `ASC`
- `DESC`

### Commands


#### searchable:index

Index all models for a certain class
```
 php artisan  searchable:index
 
Usage:
  searchable:index <model_class> {id?}

Arguments:
  model_class           Classname of the model to index
  id                    Model id to index (optional)

```

##### Example

- Indexing all clients

``php artisan  searchable:index "\App\Models\Client"``
 
- Indexing specific client by id

``php artisan  searchable:index "\App\Models\Client" 1`` 

#### searchable:unindex

UnIndex all models for a certain class
```
 php artisan  searchable:unindex
 
Usage:
  searchable:unindex <model_class> {id?}

Arguments:
  model_class           Classname of the model to index
  id                    Model id to unindex (optional)

```

##### Example

- UnIndexing all clients

``php artisan  searchable:unindex "\App\Models\Client"``
 
- UnIndexing specific client by id

``php artisan  searchable:unindex "\App\Models\Client" 1`` 

## Config options

### `db_connection`

Choose the database connection to use, defaults to the default database connection. When you are NOT using the default database connection, this MUST be set before running the migration to work correctly.

### `table_name`

Table name of index

### `command_prefix`

Prefix of commands
 
### `weight.title`, `weight.content`

Results on ``title`` or ``content`` are weighted in the results. Search result score is multiplied by the weight in this config 

### `cleaners`

Clean searching keywords for prevent breaking the MySQL query.

## Testing

``` bash
$ composer test
```
