<?php

namespace App\Console\Commands\AutoFile;

use \Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class AutoModuleCommand extends Command
{
    use AutoFile;

    protected $signature   = 'auto:module {--c|controller=} {--m|model=0}';
    protected $description = 'Generate a application module, include Controller、Model、Request、Logic、Translation.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $model      = $this->option('model');
        $controller = $this->option('controller');

        $path = implode(DIRECTORY_SEPARATOR, array_slice(explode(DIRECTORY_SEPARATOR, $controller), 1));
        if ($model) {
            $this->extraClassGenerate('model', ['name' => $model]);
            $this->extraClassGenerate('translation', [
                'name'    => str_replace('Controller', '', $path),
                '--model' => $model
            ]);
        } else {
            $this->extraClassGenerate('translation', [
                'name' => str_replace('Controller', '', $path),
            ]);
        }

        $this->extraClassGenerate('logics', [
            'name' => str_replace('Controller', '', $path),
        ]);

        $this->extraClassGenerate('controller', [
            'name'       => $controller,
            '--template' => true
        ]);
    }


}
