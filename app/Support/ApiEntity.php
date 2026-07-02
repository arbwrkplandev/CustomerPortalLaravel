<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Support\Arr;

class ApiEntity implements UrlRoutable
{
    public function __construct(protected array $attributes = []) {}

    public static function from(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        if (Arr::isList($value)) {
            return collect(array_map(fn ($item) => self::from($item), $value));
        }

        $normalized = [];
        foreach ($value as $key => $item) {
            $normalized[$key] = self::from($item);

            if (is_string($normalized[$key]) && self::looksLikeDateKey((string) $key) && strtotime($normalized[$key]) !== false) {
                try {
                    $normalized[$key] = Carbon::parse($normalized[$key]);
                } catch (\Throwable) {
                }
            }
        }

        return new self($normalized);
    }

    public function __get(string $name): mixed
    {
        if (array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }

        // Fallback: camelCase access -> snake_case key (matches Eloquent relationship naming)
        $snake = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
        return $this->attributes[$snake] ?? null;
    }

    public function __isset(string $name): bool
    {
        if (array_key_exists($name, $this->attributes)) {
            return true;
        }

        $snake = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
        return array_key_exists($snake, $this->attributes);
    }

    public function toArray(): array
    {
        return $this->attributes;
    }

    public function getRouteKey(): mixed
    {
        return $this->attributes['id'] ?? null;
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    public function resolveRouteBinding($value, $field = null): mixed
    {
        return null;
    }

    public function resolveChildRouteBinding($childType, $value, $field): mixed
    {
        return null;
    }

    protected static function looksLikeDateKey(string $key): bool
    {
        return str_ends_with($key, '_at') || str_ends_with($key, '_date') || in_array($key, ['created_at', 'updated_at', 'deleted_at'], true);
    }
}
