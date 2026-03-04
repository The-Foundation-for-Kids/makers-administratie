<?php

namespace App\Models;

use LaravelArchivable\Archivable;

class CreatorHistory extends Clothing
{
    use Archivable;
    protected $table = 'clothing';
}
