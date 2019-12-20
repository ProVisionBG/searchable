<?php
/**
 * Copyright (c) ProVision Media Group Ltd. <https://provision.bg>. All Rights Reserved, 2019
 * Written by Venelin Iliev <venelin@provision.bg>
 */

namespace ProVision\Searchable\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use ProVision\Searchable\Models\SearchableIndex;
use ProVision\Searchable\Traits\SearchableTrait;
use Symfony\Component\Console\Helper\ProgressBar;

class UnIndexCommand extends Command
{
    protected $signature = 'prefix:unindex {model_class} {id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove record/records from the searchindex';

    public function __construct()
    {
        $this->signature = config('searchable.command_prefix') . ':unindex {model_class} {id?}';
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     * @throws Exception
     */
    public function handle(): void
    {
        /** @var string $modelClass */
        $modelClass = $this->argument('model_class');

        if (!in_array(SearchableTrait::class, class_uses($modelClass), true)) {
            $this->error('Model not using ' . SearchableTrait::class);
            return;
        }

        /** @var Builder $indexRecordQuery */
        $indexRecordQuery = SearchableIndex::where('searchable_type', (new $modelClass)->getMorphClass());

        $modelId = $this->argument('id');
        if (!empty($modelId)) {
            $indexRecordQuery->where('searchable_id', $modelId);
        }

        /** @var integer $indexRecordsCount */
        $indexRecordsCount = $indexRecordQuery->count();

        if ($indexRecordsCount < 1) {
            $this->error('Indexed records of ' . $modelClass . ($modelId ? ' and id ' . $modelId : '') . ' not found!');
            return;
        }

        /** @var ProgressBar $bar */
        $bar = $this->output->createProgressBar($indexRecordsCount);
        $bar->start();

        $indexRecordQuery->chunk(100, function (Collection $chunks) use (&$bar) {
            $chunks->each(function (SearchableIndex $indexRecord) use ($bar) {
                $indexRecord->delete();
                $bar->advance();
            });
        });

        $bar->finish();

        $this->info('UnIndexing finished...');
    }
}
