**Translatable** package injects model translation fields which stores in DB.

## Installation

Composer:
```
composer require stolkom/translatable
```

Publish provider and execute migrations:
```
php artisan vendor:publish --provider="Stolkom\Translatable\TranslatableServiceProvider"
php artisan migrate
```

## Usage:

Add trait to model:

```php
use Stolkom\Translatable\Translatable;
```

Translatable fields should be specified at **$translatableAttributes** as array property:

```php
public $translatableAttributes = ['name', 'description'];
```

All __translatable__ models is **autotranslatable**. That means, all attributes from **$translatableAttributes** will replace original model attributes and translates to user locale.
If translation does not exists in the translation table, original attribute value will returns.

```php
$exampleModel->name
```

### Language translation

Get language translation:

```php
public function getTranslation(string $field, string $locale = null): string
```

`getTranslation` method returns field translation in passed locale.

* *string* **$field** - model field to translate.
* *string* **$locale** - `ISO 639-1` locale. Default locale is current user locale.

### Disable autotranslation

In some cases (e.g. create/edit form) you should **disable autotranslation**:

```php
$exampleModel->disableAutoTranslations(); 
```

To enable **autotranslation** call `enableAutotranslations` method.

### General translations table

By default `Translatable` trait works with general **translations** table by _polymorphic_ relations.

### Separate translations table

When your model should contains many records, you can create separate model table:

1. Create migration with following fields:

```php
Schema::create('example_model_translations', function (Blueprint $table) {
   $table->increments('id');
   $table->unsignedInteger('example_model_id')->index();
   $table->string('field');
   $table->string('locale', 2);
   $table->text('text')->nullable();

   $table->index(['example_model_id', 'field', 'locale']);
});
```
* `example_model_translations` - translations table name
* `example_model_id` - foreign key to model linked table id

2. Create translations model with following configs:

```php
class ExampleModelTranslation extends Model
{
   public $timestamps = false;
   protected $guarded = [];
}
```

3. Link your model with translations table:

```php
public $translationTable = 'example_model_translations';
```

4. Override `getTranslationModelName` method to return model translations name:

```php
protected static function getTranslationModelName()
{
   return ExampleModelTranslation::class;
}
```

### Saving translations

For saving translations use `saveTranslations` method:

```php
public function saveTranslations(array $translations)
```

`$translations` array structure:

```php
['field_name' => ['locale' => 'value']] 
```

*Example:*
```php
$exampleModel->saveTranslations([
   'name' => [
      'en' => 'Example',
      'ru' => 'Пример'
   ],
   'description' => [
      'en' => 'Description example',
      'ru' => 'Пример описания'
   ],
]);
```

### Eager loading

By default Laravel use Lazy loading, which leads to N+1 query problem.
Solve this problem by _eager loading_ usage:

```php
ModelName::with('translations')->get(); 
```

You can apply translations relation to all queries:

```php
protected $with = ['translations'];
```