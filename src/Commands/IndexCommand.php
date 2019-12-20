<?php
/**
 * Copyright (c) ProVision Media Group Ltd. <https://provision.bg>. All Rights Reserved, 2019
 * Written by Venelin Iliev <venelin@provision.bg>
 */

namespace ProVision\Searchable\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use ProVision\Searchable\Traits\SearchableTrait;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Class IndexCommand
 * @package ProVision\Searchable\Commands
 */
class IndexCommand extends Command
{
    protected $signature = 'prefix:all {model_class}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build the searchindex';

    public function __construct()
    {
        $this->signature = config('searchable.command_prefix') . ':index {model_class} {id?}';
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $modelClass = $this->argument('model_class');

        if (!in_array(SearchableTrait::class, class_uses($modelClass), true)) {
            $this->error('Model not using ' . SearchableTrait::class);
            return;
        }

        /** @var Builder $modelQuery */
        $modelQuery = $modelClass::query();

        $modelId = $this->argument('id');

        if (!empty($modelId)) {
            $modelQuery->where('id', $modelId);
        }

        $totalModels = $modelQuery->count();

        if ($totalModels < 1) {
            $this->error('Models of ' . $modelClass . ($modelId ? ' and id ' . $modelId : '') . ' not found!');
            return;
        }

        /** @var ProgressBar $bar */
        $bar = $this->output->createProgressBar($totalModels);
        $bar->start();

        $modelQuery->chunk(100, function (Collection $chunks) use (&$bar) {
            $chunks->each(function ($model) use ($bar) {
                $model->indexRecord();
                $bar->advance();
            });
        });

        $bar->finish();

        $this->info('Indexing finished...');
    }
}
