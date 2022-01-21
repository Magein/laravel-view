<?php

namespace namespaceTemplate;

use Magein\Admin\View\Page;
use useModel;

class pageName extends Page
{
    public $model = useModel::class;

    public $auth = [];

    public $search = [];

    public $rules = '';

    public $message = '';
}
