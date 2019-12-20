<?php
/**
 * Copyright (c) ProVision Media Group Ltd. <https://provision.bg>. All Rights Reserved, 2019
 * Written by Venelin Iliev <venelin@provision.bg>
 */

namespace ProVision\Searchable\Traits;

use ProVision\Searchable\Observers\RelationModelObserver;

/**
 * Trait SearchableRelationTrait
 * @package ProVision\Searchable\Traits
 */
trait SearchableRelationTrait
{

    /**
     * Boot the trait.
     * @return void
     */
    public static function bootSearchableRelationTrait(): void
    {
        static::observe(new RelationModelObserver());
    }

    /**
     * Get relation name
     * @return string
     */
    abstract static function getSearchableRelationName(): string;

}
