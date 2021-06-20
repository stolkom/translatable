Пакет **Translatable** предназначен для внедрения перевода полей модели. По сути, **Translatable** не является заменой lang-файлов, а служит для перевода значений, хранящихся в БД.

## Установка
Установить пакет можно с помощью composer-а:
```
composer require stolkom/translatable
```
Если планируется использовать общую таблицу для хранения переводов, необходимо её создать.
Для этого необходимо выполнить следующие команды:

```
php artisan vendor:publish --provider="Stolkom\Translatable\TranslatableServiceProvider"
php artisan migrate
```

## Использование

Подключить Trait можно, добавив следующую строку в тело модели:
```php
use Stolkom\Translatable\Translatable;
```

Список переводимых полей указывается в массиве translatableAttributes, который нужно объявить в теле модели:
```php
public $translatableAttributes = ['name', 'description'];
```

Для всех моделей, использующих **Translatable** трейт, по умолчанию включен автоперевод.
Таким образом, все поля, указанные в массиве **translatableAttributes**,
переводятся на язык текущей локали пользователя автоматически при обращении к ним.
Если перевод для значения поля на выбранный язык отсутствует в таблице переводов, возвращается непереведенное значение.

```php
$exampleModel->name // будет переведено, если значение перевода есть в БД 
```

### Получение перевода на конкретный язык

Если необходимо получить перевод значения поля не на текущий язык пользователя,
а на какой-то другой, можно воспользоваться функцией:

```php
public function getTranslation(string $field, string $locale = null)
```

`getTranslation` возвращает перевод выбранного поля на выбранный язык.
Если перевода нет в таблице БД, возвращает непереведенное значение поля.

* **field** - тип string. Наименование поля модели, значение которого нужно перевести.
* **locale** - тип string. Двухбуквенное обозначение локали, перевод на язык которой нужно получить.
Если не задана, используется текущая локаль пользователя.

*Возвращает: string*

### Отключение автоматического перевода

Если в какой-то момент необходимо отключить автоперевод полей (например на страницах create/edit),
нужно вызвать метод модели `disableAutoTranslations`:

```php
$exampleModel->disableAutoTranslations(); 
```

Чтобы включить автоперевод обратно, нужно вызвать метод `enableAutoTranslations`.

### Общая таблица переводов
По умолчанию для хранения переводов полей модели используется общая таблица **translations** с полиморфными связями.

### Отдельная таблица переводов

Если для модели предполагается большое количество записей,
то в целях предотвращения чрезмерного разрастания общей таблицы и снижения нагрузки на БД
можно использовать отдельную таблицу переводов для этой модели.

Для этого нужно создать миграцию со следующими полями:

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

Где `example_model_translations` - имя таблицы переводов.

Чтобы указать, что модель использует отдельную таблицу,
в тело модели необходимо добавить переменную `translationTable`,
содержащую название используемой таблицы:

```php
public $translationsTable = 'example_model_translations';
```

Кроме того, необходимо создать отдельную модель для переводов:

```php
class ExampleModelTranslation extends Model
{
   public $timestamps = false;
   protected $guarded = [];
}
```

и переопределить метод `getTranslationModelName` в модели, использующей Translatable трейт,
так, чтобы он возвращал имя созданной модели переводов:

```php
protected static function getTranslationModelName()
{
   return ExampleModelTranslation::class;
}
```

После этого **Translatable** трейт автоматически будет использовать созданную таблицу
для хранения переводов этой модели (в нашем случае ExampleModel).

### Сохранение переводов

Для сохранения переводов модели можно воспользоваться методом:

```php
public function saveTranslations(array $translations)
```

Метод `saveTranslations` принимает в качестве атрибута массив следующего вида:

```php
['field_name' => ['locale' => 'value']] 
```

*Пример:*
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

### Нетерпеливая загрузка (Eager loading)
Стоит учитывать, что по умолчанию Laravel использует ленивую загрузку (Lazy loading)
и вывод списка из 100 записей с переводами создаст 101 запрос.
Для решения этой проблемы следует использовать нетерпеливую загрузку.

```php
ModelName::with('translations')->get(); 
```

Кроме того, можно автоматически применять нетерпеливую загрузку для модели.
Для этого достаточно добавить в тело модели следующую строку:

```php
protected $with = ['translations'];
```
