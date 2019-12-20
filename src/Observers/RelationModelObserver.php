<?php
/**
 * Copyright (c) ProVision Media Group Ltd. <https://provision.bg>. All Rights Reserved, 2019
 * Written by Venelin Iliev <venelin@provision.bg>
 */

namespace ProVision\Searchable\Observers;


use Illuminate\Database\Eloquent\Model;
use ProVision\Searchable\Exceptions\RelationNotFoundException;

/**
 * Class RelationModelObserver
 * @package ProVision\Searchable\Observers
 */
class RelationModelObserver
{

    /**
     * Handle the Model "updated" event.
     *
     * @param Model $contact
     * @return void
     * @throws RelationNotFoundException
     */
    public function updated(Model $contact)
    {
        $this->updateParent($contact);
    }

    /**
     * @param Model $model
     * @return void
     * @throws RelationNotFoundException
     */
    private function updateParent(Model $model): void
    {
        /*
        * Update relation model for fire update events (searchable observer)
        */
        if ($model->{$model::getSearchableRelationName()}) {
            event('eloquent.updated: ' . get_class($model->{$model::getSearchableRelationName()}), $model->{$model::getSearchableRelationName()});
            return;
        }

        throw new RelationNotFoundException('SearchableRelation not found: ' . $model::getSearchableRelationName());
    }

    /**
     * Handle the Model "created" event.
     *
     * @param Model $contact
     * @return void
     * @throws RelationNotFoundException
     */
    public
    function created(Model $contact)
    {
        $this->updateParent($contact);
    }

    /**
     * Handle the Model "deleted" event.
     *
     * @param Model $contact
     * @return void
     * @throws RelationNotFoundException
     */
    public
    function deleted(Model $contact)
    {
        $this->updateParent($contact);
    }

    /**
     * Handle the Model "restored" event.
     *
     * @param Model $contact
     * @return void
     * @throws RelationNotFoundException
     */
    public
    function restored(Model $contact)
    {
        $this->updateParent($contact);
    }
}