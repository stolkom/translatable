<?php

namespace Stolkom\Translatable;

use Illuminate\Database\Eloquent\Model;

trait Translatable
{
    /**
     * Enable auto translations.
     *
     * @var bool
     */
    protected $autoTranslations = true;

    /**
     * Default translations table.
     *
     * @var string
     */
    protected $defaultTranslationsTable = 'translatable';

    /**
     * Array of translatable attributes.
     *
     * @var array
     */
    public $translatableAttributes = [];

    /**
     * Trait boot method.
     *
     * @return void
     */
    public static function bootTranslatable()
    {
        static::deleting(function (Model $model) {
            $model->translations()->delete();
        });
    }

    /**
     * Get translation model name.
     *
     * @return string
     */
    protected static function getTranslationModelName()
    {
        return Translation::class;
    }

    /**
     * Get all translations.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany | \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function translations()
    {
        return isset($this->translationsTable)
            ? $this->hasMany($this->getTranslationModelName())
            : $this->morphMany(Translation::class, $this->defaultTranslationsTable);
    }

    /**
     * Convert model instance to array
     * Translate fields before transform array
     *
     * @return array
     */
    public function toArray(): array
    {
        $attributes = parent::toArray();

        if (!$this->autoTranslations) {
            return $attributes;
        }

        foreach ($this->translatableAttributes as $field) {
            $attributes[$field] = $this->getTranslation($field);
        }

        return $attributes;
    }

    /**
     * Get the field translation.
     *
     * @param  string $field - field name
     * @param  string $locale - locale code
     * @return mixed
     */
    public function getTranslation(string $field, string $locale = null)
    {
        return $this->getTranslationValue($field, $locale)
            ?? parent::getAttribute($field);
    }

    /**
     * Get the field translation value if exists
     *
     * @param  string $field - field name
     * @param  string $locale - locale code
     * @return string | null
     */
    public function getTranslationValue(string $field, ?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();

        $translation = $this->translations
            ->where('field', $field)
            ->where('locale', $locale)
            ->first();

        return $translation ? $translation->text : null;
    }

    /**
     * Save all translations.
     *
     * @param  array $requestTranslations - array of translations from request
     * @return void
     */
    public function saveTranslations(array $requestTranslations): void
    {
        $modelName = $this->getTranslationModelName();
        $translations = [];

        foreach ($requestTranslations as $field => $fieldData) {
            if (!$this->isTranslatableAttribute($field)) {
                continue;
            }

            foreach ($fieldData as $locale => $value) {
                $exists = $this->translations
                    ->where('field', $field)
                    ->where('locale', $locale)
                    ->first();

                // Update if exists
                if ($exists) {
                    $exists->text = $value;
                    $exists->save();
                    continue;
                }

                // Create new
                $translations[] = new $modelName([
                    'locale' => $locale,
                    'field'  => $field,
                    'text'   => $value
                ]);
            }
        }

        // Save all new
        $this->translations()->saveMany($translations);
    }

    /**
     * Get the attribute translation.
     *
     * @param  string $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        if ($this->autoTranslations && $this->isTranslatableAttribute($key)) {
            return $this->getTranslation($key);
        }

        return parent::getAttribute($key);
    }

    /**
     * Check if the attribute is translatable.
     *
     * @param  string $key
     * @return bool
     */
    public function isTranslatableAttribute(string $key): bool
    {
        return !empty($this->translatableAttributes)
            && in_array($key, $this->translatableAttributes);
    }

    /**
     * Enable auto translations.
     *
     * @return void
     */
    public function enableAutoTranslations(): void
    {
        $this->autoTranslations = true;
    }

    /**
     * Disable auto translations.
     *
     * @return void
     */
    public function disableAutoTranslations(): void
    {
        $this->autoTranslations = false;
    }
}
