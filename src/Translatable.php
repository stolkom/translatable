<?php

namespace Stolkom\Translatable;

use Stolkom\Translatable\Translation;
use Illuminate\Database\Eloquent\Model;

trait Translatable
{
	protected static $autoTranslations = true;

	/**
	 * Trait boot method
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
	 * Get translation model name
	 *
	 * @return string
	 */
	protected static function getTranslationModelName()
	{
		return Translation::class;
	}

	/**
	 * Get all translations
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\hasMany | \Illuminate\Database\Eloquent\Relations\morphMany
	 */
	public function translations()
	{
		if (isset($this->translationsTable)) {
			return $this->hasMany($this->getTranslationModelName());
		}
		return $this->morphMany(Translation::class, 'translatable');
	}

	/**
	 * Convert model instance to array
	 * Fields translation before array transformation
	 *
	 * @return array
	 */
	public function toArray()
	{
		$attributes = parent::toArray();

		if (! self::$autoTranslations) return $attributes;

		foreach ($this->translatableAttributes as $field) {
			$attributes[$field] = $this->getTranslation($field);
		}

		return $attributes;
	}

	/**
	 * Get the field translation
	 *
	 * @param  string $field - field name
	 * @param  string $locale - locale code
	 * @return string
	 */
	public function getTranslation(string $field, string $locale = null)
	{
		return $this->getTranslationValue($field, $locale) ?? parent::getAttribute($field);
	}

	/**
	 * Get the field translation value if exists
	 *
	 * @param  string $field - field name
	 * @param  string $locale - locale code
	 * @return string | null
	 */
	public function getTranslationValue(string $field, string $locale = null)
	{
		$locale = $locale ?? app()->getLocale();

		$translation = $this->translations
			->where('field', $field)
			->where('locale', $locale)
			->first();

		return $translation ? $translation->text : null;
	}

	/**
	 * Save all translations
	 *
	 * @param  array $requestTranslations - translations array from request
	 * @return void
	 */
	public function saveTranslations(array $requestTranslations)
	{
		$modelName = $this->getTranslationModelName();
		$translations = [];
		foreach ($requestTranslations as $field => $fieldData) {
			if (! $this->isTranslatableAttribute($field)) continue;

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
	 * Get attribute translation
	 *
	 * @param  string $key
	 * @return mixed
	 */
	public function getAttribute($key)
	{
		if (self::$autoTranslations && $this->isTranslatableAttribute($key)) {
			return $this->getTranslation($key);
		}

		return parent::getAttribute($key);
	}

	/**
	 * Check if the attribute is translatable
	 *
	 * @param  string $key
	 * @return boolean
	 */
	public function isTranslatableAttribute(string $key)
	{
		return !empty($this->translatableAttributes)
			&& in_array($key, $this->translatableAttributes);
	}

	/**
	 * Enable the auto translations
	 *
	 * @return void
	 */
	public static function enableAutoTranslations()
	{
		self::$autoTranslations = true;
	}

	/**
	 * Disable the auto translations
	 *
	 * @return void
	 */
	public static function disableAutoTranslations()
	{
		self::$autoTranslations = false;
	}
}
