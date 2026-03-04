<?php

namespace App\Models;

use LaravelArchivable\Archivable;


class Management extends Clothing
{
    protected $table = 'clothing';
    use Archivable;
}
