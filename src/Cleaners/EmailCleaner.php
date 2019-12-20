<?php
/**
 * Copyright (c) ProVision Media Group Ltd. <https://provision.bg>. All Rights Reserved, 2019
 * Written by Venelin Iliev <venelin@provision.bg>
 */

namespace ProVision\Searchable\Cleaners;


/**
 * Class EmailCleaner
 * @package ProVision\Searchable\Cleaners
 */
class EmailCleaner extends Cleaner
{
    /**
     * @return string
     */
    public function clean(): string
    {
        return str_ireplace('@', ' ', $this->keywords);
    }
}