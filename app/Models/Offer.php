<?php

namespace App\Models;

use LaravelArchivable\Archivable;

class Offer extends Clothing
{
    protected $table = 'clothing';
    use Archivable;
}
