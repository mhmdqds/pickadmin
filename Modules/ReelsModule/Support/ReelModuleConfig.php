<?php

namespace Modules\ReelsModule\Support;

class ReelModuleConfig
{
    public static function isMultiModule(): bool
    {
        return (bool) config('reelsmodule.is_multi_module', true);
    }

    public static function allowedTypes(): array
    {
        return (array) config('reelsmodule.allowed_module_types', []);
    }

    public static function isAllowedType(?string $type): bool
    {
        if (!self::isMultiModule()) {
            return true;
        }

        return $type !== null && in_array($type, self::allowedTypes(), true);
    }

    public static function defaultModuleId(): int
    {
        return (int) config('reelsmodule.default_module_id', 0);
    }

    public static function defaultModuleType(): string
    {
        return (string) config('reelsmodule.default_module_type', 'default');
    }

    public static function currentModuleId(?int $fallback = null): ?int
    {
        if (!self::isMultiModule()) {
            return self::defaultModuleId();
        }

        $resolved = config('module.current_module_id');
        if (is_numeric($resolved)) {
            return (int) $resolved;
        }

        return $fallback;
    }

    public static function currentModuleType(?string $fallback = null): ?string
    {
        if (!self::isMultiModule()) {
            return self::defaultModuleType();
        }

        $resolved = config('module.current_module_type');

        return $resolved ? (string) $resolved : $fallback;
    }
}
