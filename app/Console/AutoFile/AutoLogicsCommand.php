<?php

namespace App\Console\Commands\AutoFile;

use \Illuminate\Console\GeneratorCommand;

class AutoLogicsCommand extends GeneratorCommand
{

    use AutoFile;

    protected $name        = 'auto:logics';
    protected $description = 'Generate a Logics class.';
    protected $type        = 'Logics';

    /**
     * @inheritDoc
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/logics.stub');
    }

    protected function buildClass($name): string
    {
        $this->type .= "\040" . $name;
        return parent::buildClass($name);
    }

    /**
     * Get the default namespace for the class.
     * 指定类生成的根目录
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return is_dir(app_path('Logics')) ? $rootNamespace . '\\Logics' : $rootNamespace;
    }
}
