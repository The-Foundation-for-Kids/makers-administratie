<?php

namespace App\Models;

use App\Models\Clothing;
use LaravelArchivable\Archivable;


class Completed extends Clothing
{
    protected $table = 'clothing';
    use Archivable;
}

