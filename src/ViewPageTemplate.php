<?php

namespace namespaceTemplate;

use Magein\Admin\View\Page;
use App\Models\useModel;

class pageName extends Page
{
    public $model = Model::class;

    public $auth = null;

    public $search = [];

    public $rules = RULES;

    public $message = MESSAGE;
}
