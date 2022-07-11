<?php

namespace App\Console\Commands\AutoFile;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Arr;
use Symfony\Component\Console\Input\InputOption;

class AutoTranslationCommand extends GeneratorCommand
{
    use AutoFile;

    protected $name        = 'auto:translation';
    protected $description = 'Generate a Translation class.';
    protected $type        = 'Translation';

    /**
     * Notes:获取模板文件
     * User: weicheng
     * DateTime: 2022/1/19 17:08
     * @return string
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/translation.stub');
    }

    /**
     * Get the default namespace for the class.
     * 指定类生成的根目录
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return is_dir(app_path('Translation')) ? $rootNamespace . '\\Translation' : $rootNamespace;
    }

    /**
     * Notes:Build the Translation with the given name.
     * User: weicheng
     * DateTime: 2022/1/19 16:55
     * @param string $name
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildClass($name): string
    {
        $this->type .= ' ' . $name;

        $replace    = [
            'use App\Models\{{ modelPath }};' => '',
            '{{ model }} $list'               => ''
        ];

        if ($this->option('model')) {
            $modelPath = $this->option('model');
            $modelPath = str_replace('/', '\\', $modelPath);
            $model     = Arr::last(explode('\\', $modelPath));
            $replace   = [
                '{{ modelPath }}' => $modelPath,
                '{{ model }}'     => $model,
            ];
        }

        return str_replace(
            array_keys($replace), array_values($replace), parent::buildClass($name)
        );
    }

    protected function getOptions(): array
    {
        # 选项名,简写，选项类型，选项描述，默认值
        return [
            ['force', null, InputOption::VALUE_NONE, 'Create the class even if the controller already exists.'],
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'Define the Translation class\'s Model.', null],
        ];
    }

}
