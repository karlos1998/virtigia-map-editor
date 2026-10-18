<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableDropdownColumn;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableDropdownOptions\TableDropdownOption;

class ActivityLogWorldColumn extends TableDropdownColumn
{
    public function findOption($value): TableDropdownOption
    {
        $worlds = collect(is_array($value) ? $value : [])
            ->filter(fn ($slug): bool => is_string($slug))
            ->intersect(array_column($this->options, 'value'))
            ->values()
            ->all();

        return new TableDropdownOption(
            'Wybrane światy',
            fn (Builder $query): Builder => $query->whereIn('world', $worlds),
        );
    }
}
