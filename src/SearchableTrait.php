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
     * Searchable score for results orders
     * @var string
     */
    protected $searchableScoreKey = 'searchableScore';

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
     * @param SearchableModes|string $searchMode
     * @return Builder
     */
    public function scopeSearch($query, string $keywords, string $searchMode = null): Builder
    {

        /*
         * Set weight of fields
         */
        $titleWeight = str_replace(',', '.', (float)config('searchable.weight.title', 1.5));
        $contentWeight = str_replace(',', '.', (float)config('searchable.weight.content', 1.0));

        /*
         * Clean keywords
         */
        if (!empty(config('searchable.cleaners.' . $searchMode)) && is_array(config('searchable.cleaners.' . $searchMode))) {

            /** @var array $cleaners */
            $cleaners = config('searchable.cleaners.' . $searchMode);

            /** @var string $cleanerClass */
            foreach ($cleaners as $cleanerClass) {
                $keywords = (new $cleanerClass($keywords, $searchMode))->clean();
            }

        }

        return $query->selectRaw(
            $this->getTable() . '.*, ' .
            '(
                ' . $titleWeight . ' * (MATCH (' . config('searchable.table_name') . '.title) AGAINST (? ' . $searchMode . ')) +
                ' . $contentWeight . ' * (MATCH (' . config('searchable.table_name') . '.title, ' . config('searchable.table_name') . '.content) AGAINST (? ' . $searchMode . '))
                ) as ' . $this->searchableScoreKey
            , [$keywords, $keywords])
            ->leftJoin(config('searchable.table_name'), function (JoinClause $join) {
                $join->on(config('searchable.table_name') . '.searchable_id', '=', $this->getTable() . '.id')
                    ->where(config('searchable.table_name') . '.searchable_type', '=', $this->getMorphClass());
            })
            ->whereRaw('MATCH(' . config('searchable.table_name') . '.title, ' . config('searchable.table_name') . '.content) AGAINST(? ' . $searchMode
                . ')', [$keywords])
            ->groupBy($this->getTable() . '.id')
            ->searchableOrder();
    }

    /**
     * Set searchableScore order direction
     *
     * @param Builder $query
     * @param string $direction ASC | DESC
     * @return Builder
     */
    public function scopeSearchableOrder($query, $direction = 'desc')
    {
        return $query->orderBy($this->searchableScoreKey, $direction);
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

        /*
         * Replace all non word characters with spaces
         * Only words are indexed by full text search engine and can be searched.
         * Non word characters isn't indexed, so it does not make sense to leave them in the search string.
         */
        return preg_replace('/[^\p{L}\p{N}_]+/u', ' ', implode(' ', array_filter($indexData)));
    }

    /**
     * @param $column
     *
     * @return bool
     */
    protected function indexDataIsRelation($column): bool
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
