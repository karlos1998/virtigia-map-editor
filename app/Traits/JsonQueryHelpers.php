<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait JsonQueryHelpers
{
    /**
     * Add a where condition to check if a JSON field contains a specific value.
     *
     * @param Builder $query The query builder instance
     * @param string $field The JSON field to check
     * @param string $path The JSON path to check
     * @param mixed $value The value to check for
     * @param string $boolean The boolean operator (and/or)
     * @return Builder
     */
    public function scopeWhereJsonContains(Builder $query, string $field, string $path, $value, string $boolean = 'and'): Builder
    {
        $method = $boolean === 'or' ? 'orWhereRaw' : 'whereRaw';

        // For direct value match (string)
        return $query->$method("JSON_CONTAINS($field, ?, '$path')", ['"'.$value.'"']);
    }

    /**
     * Add a where condition to check if a JSON field contains a value in an array.
     *
     * @param Builder $query The query builder instance
     * @param string $field The JSON field to check
     * @param string $path The JSON path to check
     * @param mixed $value The value to check for
     * @param string $boolean The boolean operator (and/or)
     * @return Builder
     */
    public function scopeWhereJsonContainsArray(Builder $query, string $field, string $path, $value, string $boolean = 'and'): Builder
    {
        $method = $boolean === 'or' ? 'orWhereRaw' : 'whereRaw';

        // For array value match
        return $query->$method("JSON_CONTAINS($field, JSON_ARRAY(?), '$path')", [$value]);
    }

    /**
     * Add where conditions to check if a JSON field contains a value in any of the specified paths.
     *
     * @param Builder $query The query builder instance
     * @param string $field The JSON field to check
     * @param array $paths The JSON paths to check
     * @param mixed $value The value to check for
     * @param string $boolean The boolean operator (and/or)
     * @return Builder
     */
    public function scopeWhereJsonContainsInPaths(Builder $query, string $field, array $paths, $value, string $boolean = 'and'): Builder
    {
        $query->where(function($q) use ($field, $paths, $value) {
            foreach ($paths as $index => $path) {
                $boolOperator = $index === 0 ? 'and' : 'or';
                $this->scopeWhereJsonContains($q, $field, $path, $value, $boolOperator);
                $this->scopeWhereJsonContainsArray($q, $field, $path, $value, 'or');
            }
        }, null, null, $boolean);

        return $query;
    }

    /**
     * Add where conditions to check if a JSON field contains any of the specified values in any of the specified paths.
     *
     * @param Builder $query The query builder instance
     * @param string $field The JSON field to check
     * @param array $paths The JSON paths to check
     * @param array $values The values to check for
     * @param string $boolean The boolean operator (and/or)
     * @return Builder
     */
    public function scopeWhereJsonContainsAnyInPaths(Builder $query, string $field, array $paths, array $values, string $boolean = 'and'): Builder
    {
        $query->where(function($q) use ($field, $paths, $values) {
            foreach ($values as $valueIndex => $value) {
                $valueBoolOperator = $valueIndex === 0 ? 'and' : 'or';
                $this->scopeWhereJsonContainsInPaths($q, $field, $paths, $value, $valueBoolOperator);
            }
        }, null, null, $boolean);

        return $query;
    }
}
