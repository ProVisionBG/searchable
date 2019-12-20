<?php
/**
 * Copyright (c) ProVision Media Group Ltd. <https://provision.bg>. All Rights Reserved, 2019
 * Written by Venelin Iliev <venelin@provision.bg>
 */

namespace ProVision\Searchable\Observers;

use Exception;
use Illuminate\Support\Facades\Log;
use ProVision\Searchable\Traits\SearchableTrait;

/**
 * Class ModelObserver
 * @package ProVision\Searchable\Observers
 */
class ModelObserver
{

    /**
     * Handle the updated event for the model.
     *
     * @param SearchableTrait $model
     */
    public function updated($model)
    {
        Log::info('updated base');
        $this->created($model);
    }

    /**
     * Handle the created event for the model.
     *
     * @param SearchableTrait $model
     */
    public function created($model)
    {
        $model->indexRecord();
    }


    /**
     * Handle the deleted event for the model.
     *
     * @param SearchableTrait $model
     * @throws Exception
     */
    public function deleted($model)
    {
        $model->unIndexRecord();
    }

    /**
     * Handle the restored event for the model.
     *
     * @param SearchableTrait $model
     */
    public function restored($model)
    {
        $this->created($model);
    }
}
