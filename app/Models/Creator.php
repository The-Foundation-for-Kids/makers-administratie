<?php

namespace App\Models;

use LaravelArchivable\Archivable;


class Creator extends Clothing
{
    protected $table = 'clothing';
    use Archivable;
}
