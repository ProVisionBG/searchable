<?php
/**
 * Copyright (c) ProVision Media Group Ltd. <https://provision.bg>. All Rights Reserved, 2019
 * Written by Venelin Iliev <venelin@provision.bg>
 */

namespace ProVision\Searchable;

/**
 * Class SearchableModes
 * @package ProVision\Searchable
 */
abstract class SearchableModes
{
    const NaturalLanguage = 'IN NATURAL LANGUAGE MODE';
    const NaturalLanguageWithQueryExpression = 'IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION';
    const Boolean = 'IN BOOLEAN MODE';
    const QueryExpression = 'WITH QUERY EXPANSION';
}