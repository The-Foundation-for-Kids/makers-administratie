<?php

namespace App\Models;

use LaravelArchivable\Archivable;

class Collector extends Clothing
{
    protected $table = 'clothing';
    use Archivable;
}
