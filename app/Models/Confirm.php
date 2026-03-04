<?php

namespace App\Models;

use LaravelArchivable\Archivable;

class Confirm extends Clothing
{
    protected $table = 'clothing';
    use Archivable;
}
