<?php
/**
 * Copyright (c) ProVision Media Group Ltd. <https://provision.bg>. All Rights Reserved, 2019
 * Written by Venelin Iliev <venelin@provision.bg>
 */

namespace ProVision\Searchable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use ProVision\Searchable\Traits\SearchableTrait;

/**
 * Class SearchableIndex
 * @property SearchableTrait searchable
 * @package ProVision\Searchable\Models
 */
class SearchableIndex extends Model
{

    /**
     * SearchableIndex constructor.
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->connection = config('searchable.db_connection');
        $this->table = config('searchable.table_name');

        parent::__construct($attributes);
    }

    /**
     * @return MorphTo
     */
    public function searchable(): MorphTo
    {
        return $this->morphTo()->withoutGlobalScopes();
    }

    /**
     * Update index data
     * @return void
     */
    public function updateIndex(): void
    {
        $this->setAttribute('title', $this->searchable->getIndexTitle());
        $this->setAttribute('content', $this->searchable->getIndexContent());
        $this->save();
    }
}
