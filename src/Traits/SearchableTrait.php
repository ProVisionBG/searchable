<?php
/**
 * Copyright (c) ProVision Media Group Ltd. <https://provision.bg>. All Rights Reserved, 2019
 * Written by Venelin Iliev <venelin@provision.bg>
 */

namespace ProVision\Searchable\Traits;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Query\JoinClause;
use ProVision\Searchable\Models\SearchableIndex;
use ProVision\Searchable\Observers\ModelObserver;
use ProVision\Searchable\SearchableModes;

/**
 * @property SearchableIndex|Model $searchableIndex
 */
trait SearchableTrait
{

    /**
     * Searchable score for results orders
     *
     * @var string
     */
    protected $searchableScoreKey = 'searchableScore';

    /**
     * Boot the trait.
     *
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
        return $this->morphTo()->withoutGlobalScopes();
    }

    /**
     * Scope a query to searchable index
     *
     * @param Builder                     $query
     * @param string                      $keywords
     * @param SearchableModes|string|null $searchMode
     *
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
                $join->on(config('searchable.table_name') . '.searchable_id', '=', $this->getTable() . '.' . $this->primaryKey)
                    ->where(config('searchable.table_name') . '.searchable_type', '=', $this->getMorphClass());
            })
            ->whereRaw('MATCH(' . config('searchable.table_name') . '.title, ' . config('searchable.table_name') . '.content) AGAINST(? ' . $searchMode
                . ')', [$keywords])
            ->groupBy($this->getTable() . '.' . $this->primaryKey)
            ->searchableOrder();
    }

    /**
     * Set searchableScore order direction
     *
     * @param Builder $query
     * @param string  $direction ASC | DESC
     *
     * @return Builder
     */
    public function scopeSearchableOrder($query, $direction = 'desc')
    {
        return $query->orderBy($this->searchableScoreKey, $direction);
    }

    /**
     * @return string|null
     */
    public function getIndexContent()
    {
        return $this->getIndexDataFromColumns($this->getSearchableContentColumns());
    }

    /**
     * @param array $columns
     *
     * @return string|null
     */
    protected function getIndexDataFromColumns(array $columns)
    {
        $indexData = [];
        foreach ($columns as $column) {
            if ($this->indexDataIsRelation($column)) {
                $indexData[] = $this->searchableGetIndexValueFromRelation($column);
            } else {
                $columnValue = $this->{$column};
                $indexData[] = $this->convertDataToString($columnValue);
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
     * @return string|null
     */
    protected function searchableGetIndexValueFromRelation($column)
    {
        $relations = explode('.', $column);
        $columnSelect = array_pop($relations);

        return $this->searchableExtractDataFromRelation($this, $relations, $columnSelect);
    }

    /**
     * Extract data on nested relation with unlimited level
     *
     * @param Model  $currentRelation
     * @param array  $relations
     * @param string $column
     *
     * @return string|null
     */
    protected function searchableExtractDataFromRelation(
        Model $currentRelation,
        array $relations,
        string $column
    ): ?string
    {
        $relationship = $currentRelation;
        $remainingRelations = $relations;

        foreach ($relations as $relation) {

            array_shift($remainingRelations);

            $relationship = $relationship->{$relation};

            if (is_countable($relationship)) {
                $values = collect();
                foreach ($relationship as $subRelation) {
                    $values->push($this->searchableExtractDataFromRelation($subRelation, $remainingRelations, $column));
                }
                return $values->implode(', ');
            }
        }

        if (!is_countable($relationship)) {
            try {
                $relationValue = $relationship->{$column};
            } catch (Exception $exception) {
                $relationValue = '';
            }
            return $this->convertDataToString($relationValue);
        }

        return $relationship->pluck($column)->implode(', ');

    }

    /**
     * @param string|array|null $data
     *
     * @return string|null
     */
    protected function convertDataToString($data): ?string
    {
        if (is_array($data)) {
            return trim(implode(' ', $data));
        }
        return trim($data);
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
    public function searchableIndex(): MorphOne
    {
        return $this->morphOne(SearchableIndex::class, 'searchable');
    }

    /**
     * Update/Insert index record of model
     *
     * @return void
     */
    public function indexRecord(): void
    {
        if (!$this->searchableIndex) {
            $this->searchableIndex = new SearchableIndex();
            $this->searchableIndex->searchable()->associate($this);
        }

        $this->searchableIndex->updateIndex();
    }

    /**
     *
     * @throws Exception
     */
    public function unIndexRecord(): void
    {
        if ($this->searchableIndex) {
            $this->searchableIndex->delete();
        }
    }
}
