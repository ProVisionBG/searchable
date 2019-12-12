<?php
/**
 * Copyright (c) ProVision Media Group Ltd. <https://provision.bg>. All Rights Reserved, 2019
 * Written by Venelin Iliev <venelin@provision.bg>
 */

namespace ProVision\Laravel\Searchable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Query\JoinClause;

/**
 * @property indexedRecord indexedRecord
 */
trait SearchableTrait
{

    /**
     * Boot the trait.
     * @return void
     */
    public static function bootSearchableTrait(): void
    {
        static::observe(new ModelObserver());
    }

    /**
     * @return MorphTo
     */
    public function searchable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope a query to searchable index
     *
     * @param Builder $query
     * @param string $keywords
     * @return Builder
     */
    public function scopeSearch($query, string $keywords): Builder
    {
        $titleWeight = str_replace(',', '.', (float)config('searchable.weight.title', 1.5));
        $contentWeight = str_replace(',', '.', (float)config('searchable.weight.content', 1.0));

        $query
            ->select([$this->getTable() . '.*'])
            ->leftJoin(config('searchable.table_name'), function (JoinClause $join) {
                $join->on(config('searchable.table_name') . '.searchable_id', '=', $this->getTable() . '.id')
                    ->where(config('searchable.table_name') . '.searchable_type', '=', $this->getMorphClass());
            })
            ->whereRaw('MATCH (' . config('searchable.table_name') . '.title, ' . config('searchable.table_name') . '.content) AGAINST (?)', [$keywords])
            ->orderByRaw(
                '(' . $titleWeight . ' * (MATCH (' . config('searchable.table_name') . '.title) AGAINST (?)) +
              ' . $contentWeight . ' * (MATCH (' . config('searchable.table_name') . '.title, ' . config('searchable.table_name') . '.content) AGAINST (?))
             ) DESC',
                [$keywords, $keywords]
            )
            ->groupBy($this->getTable() . '.id');

        return $query;
    }

    /**
     * @return string
     */
    public function getIndexContent(): string
    {
        return $this->getIndexDataFromColumns($this->getSearchableContentColumns());
    }

    /**
     * @param array $columns
     * @return string
     */
    protected function getIndexDataFromColumns(array $columns): string
    {
        $indexData = [];
        foreach ($columns as $column) {
            if ($this->indexDataIsRelation($column)) {
                $indexData[] = $this->getIndexValueFromRelation($column);
            } else {
                $indexData[] = trim($this->{$column});
            }
        }

        return implode(' ', array_filter($indexData));
    }

    /**
     * @param $column
     *
     * @return bool
     */
    protected function indexDataIsRelation($column)
    {
        return (int)strpos($column, '.') > 0;
    }

    /**
     * @param $column
     *
     * @return string
     */
    protected function getIndexValueFromRelation($column)
    {
        list($relation, $column) = explode('.', $column);
        if (is_null($this->{$relation})) {
            return '';
        }

        $relationship = $this->{$relation}();
        if ($relationship instanceof BelongsTo || $relationship instanceof HasOne) {
            return $this->{$relation}->{$column};
        }

        return $this->{$relation}->pluck($column)->implode(', ');
    }

    /**
     * @return array
     */
    abstract protected function getSearchableContentColumns(): array;

    public function getIndexTitle(): string
    {
        return $this->getIndexDataFromColumns($this->getSearchableTitleColumns());
    }

    /**
     * @return array
     */
    abstract protected function getSearchableTitleColumns(): array;

    /**
     * @return MorphOne
     */
    public function indexedRecord(): MorphOne
    {
        return $this->morphOne(IndexedRecord::class, 'searchable');
    }

    /**
     * Update/Insert index record of model
     */
    public function indexRecord(): void
    {

        if (!$this->indexedRecord) {
            $this->indexedRecord = new IndexedRecord();
            $this->indexedRecord->searchable()->associate($this);
        }

        $this->indexedRecord->updateIndex();
    }

    /**
     *
     */
    public function unIndexRecord(): void
    {
        if ($this->indexedRecord) {
            $this->indexedRecord->delete();
        }
    }
}
