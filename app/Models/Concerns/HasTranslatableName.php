<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasTranslatableName
{
    protected function name(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value) {
                $translations = is_string($value) ? json_decode($value, true) : $value;

                if (! is_array($translations)) {
                    return $value;
                }

                $locale = app()->getLocale();

                return $translations[$locale] ?? $translations['en'] ?? array_values($translations)[0] ?? $value;
            },
            set: function (mixed $value) {
                if (is_array($value)) {
                    return json_encode($value);
                }

                return json_encode(['en' => $value]);
            },
        );
    }

    public function getNameTranslations(): array
    {
        $raw = $this->attributes['name'] ?? '{}';

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : ['en' => $raw];
    }

    public function setNameTranslation(string $locale, string $value): void
    {
        $translations = $this->getNameTranslations();
        $translations[$locale] = $value;

        $this->attributes['name'] = json_encode($translations);
    }
}
