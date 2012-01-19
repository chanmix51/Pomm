<?php

namespace Pomm\FilterChain;

use Pomm\Exception\Exception;
use Pomm\FilterChain\QueryFilterChain;

interface FilterInterface
{
    public function execute(QueryFilterChain $query_filter_chain);
}
