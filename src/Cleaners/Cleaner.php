<?php
/**
 * Copyright (c) ProVision Media Group Ltd. <https://provision.bg>. All Rights Reserved, 2019
 * Written by Venelin Iliev <venelin@provision.bg>
 */

namespace ProVision\Searchable\Cleaners;

/**
 * Class Cleaner
 * @package ProVision\Searchable\Cleaners
 */
abstract class Cleaner
{
    /**
     * @var string
     */
    protected $keywords;

    /**
     * @var string|null
     */
    protected $searchMode;

    /*
     * Constructor
     */
    public function __construct(string $keywords, string $searchMode = null)
    {

        $this->keywords = $keywords;
        $this->searchMode = $searchMode;
    }

    /**
     * Return cleaned keywords string
     * @return string
     */
    public function clean(): string
    {
        return $this->keywords;
    }
}