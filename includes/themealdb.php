<?php
declare(strict_types=1);

/**
 * TheMealDB public API — https://www.themealdb.com/api.php
 * Keys match filter.php?i=… ingredient names (underscores allowed).
 */
const MEALDB_INGREDIENT_FILTERS = [
    'chicken_breast' => 'Chicken breast',
    'beef' => 'Beef',
    'salmon' => 'Salmon',
    'pasta' => 'Pasta',
    'rice' => 'Rice',
    'egg' => 'Egg',
    'pork' => 'Pork',
    'chicken' => 'Chicken',
    'lamb' => 'Lamb',
    'tomato' => 'Tomato',
];

/**
 * @return list<array{idMeal: string, strMeal: string, strMealThumb: string}>
 */
function mealdb_filter_by_ingredient(string $ingredientKey): array
{
    if (!isset(MEALDB_INGREDIENT_FILTERS[$ingredientKey])) {
        $ingredientKey = 'chicken_breast';
    }
    $url = 'https://www.themealdb.com/api/json/v1/1/filter.php?i=' . rawurlencode($ingredientKey);
    $raw = mealdb_http_get($url);
    if ($raw === null) {
        return [];
    }
    $json = json_decode($raw, true);
    if (!is_array($json) || !isset($json['meals']) || !is_array($json['meals'])) {
        return [];
    }
    $out = [];
    foreach ($json['meals'] as $row) {
        if (!is_array($row)) {
            continue;
        }
        $id = (string) ($row['idMeal'] ?? '');
        $name = (string) ($row['strMeal'] ?? '');
        $thumb = (string) ($row['strMealThumb'] ?? '');
        if ($id !== '' && $name !== '') {
            $out[] = ['idMeal' => $id, 'strMeal' => $name, 'strMealThumb' => $thumb];
        }
    }
    return $out;
}

/**
 * Merge several ingredient searches for a fuller browse experience.
 *
 * @return list<array{idMeal: string, strMeal: string, strMealThumb: string}>
 */
function mealdb_browse_many(int $maxMeals = 48): array
{
    $keys = ['chicken_breast', 'beef', 'salmon', 'pasta', 'rice', 'egg', 'pork', 'chicken'];
    $byId = [];
    foreach ($keys as $k) {
        foreach (mealdb_filter_by_ingredient($k) as $m) {
            $id = $m['idMeal'];
            if (!isset($byId[$id])) {
                $byId[$id] = $m;
            }
            if (count($byId) >= $maxMeals) {
                return array_values($byId);
            }
        }
    }
    return array_values($byId);
}

function mealdb_meal_url(string $idMeal): string
{
    return 'https://www.themealdb.com/meal/' . rawurlencode($idMeal);
}

/**
 * Full meal detail for on-site display (lookup.php?i=id).
 *
 * @return array<string, mixed>|null
 */
function mealdb_lookup_by_id(string $idMeal): ?array
{
    if (!preg_match('/^\d{1,10}$/', $idMeal)) {
        return null;
    }
    $url = 'https://www.themealdb.com/api/json/v1/1/lookup.php?i=' . rawurlencode($idMeal);
    $raw = mealdb_http_get($url);
    if ($raw === null) {
        return null;
    }
    $json = json_decode($raw, true);
    if (!is_array($json) || empty($json['meals'][0]) || !is_array($json['meals'][0])) {
        return null;
    }
    return $json['meals'][0];
}

/**
 * @param array<string, mixed> $meal
 * @return list<string>
 */
function mealdb_meal_ingredient_lines(array $meal): array
{
    $out = [];
    for ($i = 1; $i <= 20; $i++) {
        $ing = trim((string) ($meal['strIngredient' . $i] ?? ''));
        $meas = trim((string) ($meal['strMeasure' . $i] ?? ''));
        if ($ing === '' && $meas === '') {
            continue;
        }
        $line = trim($meas . ' ' . $ing);
        if ($line !== '') {
            $out[] = $line;
        }
    }
    return $out;
}

function mealdb_http_get(string $url): ?string
{
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 10,
            'header' => "Accept: application/json\r\nUser-Agent: FoodFusion/1.0 (student project)\r\n",
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);
    $body = @file_get_contents($url, false, $ctx);
    return $body !== false ? $body : null;
}
