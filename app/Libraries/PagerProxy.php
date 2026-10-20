<?php

namespace App\Libraries;

class PagerProxy
{
    public $page;
    public $offset;

    private $pager;

    public function __construct($pager, $page, $offset)
    {
        $this->pager = $pager;
        $this->page = $page;
        $this->offset = $offset;
    }

    public function __call($name, $arguments)
    {
        return $this->pager->{$name}(...$arguments);
    }
}
