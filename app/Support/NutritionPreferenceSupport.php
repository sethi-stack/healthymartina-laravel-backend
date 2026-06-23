<?php

namespace App\Support;

class NutritionPreferenceSupport
{
    public const RECIPE_PREFERENCES_CUSTOMIZED_KEY = '__recipe_preferences_customized';

    public const RECIPE_DISPLAY_IDS = [94, 99, 96, 97, 213, 180, 102, 103, 106, 107];

    public const RECIPE_DEFAULT_IDS = [94, 99, 96, 97, 213];

    public static function getRecipeNutritionOptions(?array $storedInfo = null): array
    {
        $normalized = self::normalizeNutritionInfo($storedInfo);
        $options = array_filter($normalized, function (array $item) {
            return in_array((int) ($item['id'] ?? 0), self::RECIPE_DISPLAY_IDS, true);
        });

        usort($options, function (array $left, array $right) {
            return array_search((int) ($left['id'] ?? 0), self::RECIPE_DISPLAY_IDS, true)
                <=> array_search((int) ($right['id'] ?? 0), self::RECIPE_DISPLAY_IDS, true);
        });

        return array_values($options);
    }

    public static function normalizeNutritionInfo(?array $storedInfo = null): array
    {
        $base = config('constants.nutritients', []);
        $normalized = [];
        $hasCustomizedRecipePrefs = self::hasCustomizedRecipePreferences($storedInfo);

        foreach ($base as $fdcId => $item) {
            $normalized[(string) $fdcId] = is_array($item) ? $item : (array) $item;
        }

        if (!empty($storedInfo)) {
            foreach ($storedInfo as $fdcId => $item) {
                if ($fdcId === self::RECIPE_PREFERENCES_CUSTOMIZED_KEY) {
                    continue;
                }
                $fdcKey = (string) $fdcId;
                $itemArray = is_array($item) ? $item : (array) $item;
                $normalized[$fdcKey] = array_merge($normalized[$fdcKey] ?? [], $itemArray);
            }
        }

        foreach ($normalized as &$item) {
            $id = (int) ($item['id'] ?? 0);
            if (
                in_array($id, self::RECIPE_DISPLAY_IDS, true) &&
                !$hasCustomizedRecipePrefs
            ) {
                $item['mostrar'] = in_array($id, self::RECIPE_DEFAULT_IDS, true) ? 1 : 0;
            }
        }
        unset($item);

        return $normalized;
    }

    public static function markRecipePreferencesCustomized(array $nutritionInfo): array
    {
        $nutritionInfo[self::RECIPE_PREFERENCES_CUSTOMIZED_KEY] = true;

        return $nutritionInfo;
    }

    public static function hasCustomizedRecipePreferences(?array $storedInfo = null): bool
    {
        return !empty($storedInfo[self::RECIPE_PREFERENCES_CUSTOMIZED_KEY]);
    }

    public static function getSelectedIdsFromInfo(array $info): array
    {
        $selected = [];
        foreach ($info as $item) {
            $itemArray = is_array($item) ? $item : (array) $item;
            if (!empty($itemArray['mostrar']) && !empty($itemArray['id'])) {
                $selected[] = (int) $itemArray['id'];
            }
        }

        return array_values(array_unique($selected));
    }
}
