<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // Spatie Permission levert zijn migraties als .php.stub (pas na vendor:publish runnable).
        // Kopieer ze naar een tijdelijke map als .php en registreer die bij de migrator, zodat
        // rollen/permissions alleen in de testdatabase worden aangemaakt.
        $stubDir = dirname((new \ReflectionClass(\Spatie\Permission\Models\Role::class))->getFileName(), 3).'/database/migrations';
        $testMigrationDir = sys_get_temp_dir().'/makersadmini-test-migrations';
        if (! is_dir($testMigrationDir)) {
            mkdir($testMigrationDir, 0777, true);
        }
        foreach (glob($stubDir.'/*.php.stub') ?: [] as $stub) {
            $target = $testMigrationDir.'/'.basename($stub, '.stub');
            if (! file_exists($target) || filemtime($stub) > filemtime($target)) {
                copy($stub, $target);
            }
        }
        $app->make('migrator')->path($testMigrationDir);

        return $app;
    }
}
