# laravel-autoschema
Generate markdown file describing all your laravel application models and their attributes. The schema can be used as a reference for both front- and back-end developers to keep track of properties that should or should not exist for a given model.

![Example output](https://i.imgur.com/AUuH4CM.png)

## Installation
```
composer require nonetallt/laravel-autoschema --dev
```

## Basic usage
```
php artisan schema:create
```

## Properties

#### Attribute  
The name of the attribute (usually column name).

#### Computed    
Properties that have an accessor (getXAttribute) defined but no column in database are considered computed properties.

#### Fillable
Is the attribute mass assignable.

#### Relation
Is the attribute a method describing a relation.

#### Serialized
Is the attribute present after the object is serialized (to array or json). n/a for relations since it's not possible to know wether the object is loaded with a relation by static analysis. Useful for front-end developers since objects are serialized for responses.

## Managing relations
Unfortunately, unlike the other properties, relations for models can't be easily distinguished by method signature or framework alone. To list your relations in the model you need to use @relation annotation in the relation method docblock.

```php
/**
* @relation
*/
public function addresses()
{
    return $this->hasMany('App\Address', 'address_list_name', 'name');
}
```

## Configuration

#### Publishing the configuration file

```
php artisan vendor:publish --provider="Nonetallt\Autoschema\AutoschemaServiceProvider"
```

#### Available options

* output_path
* model_directory
* model_namespace
* yes_string
* no_string
