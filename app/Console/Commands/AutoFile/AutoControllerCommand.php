<?php

namespace App\Console\Commands\AutoFile;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Console\Input\InputOption;

class AutoControllerCommand extends GeneratorCommand
{
    use AutoFile;

    protected $name        = 'auto:controller';
    protected $description = 'Generate a Controller class.';
    protected $type        = 'Controller';

    /**
     * Notes:Define the controller namespace
     * User: weicheng
     * DateTime: 2022/1/19 10:43
     * @param string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\Http\Controllers';
    }

    /**
     * Notes:获取指定模板文件
     * User: weicheng
     * DateTime: 2022/1/25 16:21
     * @return string
     */
    protected function getStub(): string
    {
        if ($this->option('template')) {
            $stub = '/stubs/controller.template.stub';
        } elseif ($this->option('resource')) {
            $stub = '/stubs/controller.resource.stub';
        } else {
            $stub = '/stubs/controller.plain.stub';
        }

        return $this->resolveStubPath($stub);
    }



    /**
     * Build the class with the given name.
     *
     * Remove the base controller import if we are already in base namespace.
     *
     * @param string $name
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildClass($name): string
    {
        $this->type          .= ' ' . $name;
        # 指定继承父类
        $replace = $this->buildBaseControllerReplacements($name);

        # 是否需要创建其他类
        if ($this->option('template')) {
            $controllerNamespace = $this->getNamespace($name);
            $subDir              = implode(
                DIRECTORY_SEPARATOR,
                array_slice(explode('\\', str_replace('App\Http\Controllers\\', '', $controllerNamespace)), 1)
            );
            $replace['{{ subDir }}'] = $subDir;
            # 创建 Request 类
            $templateRequestArr = [
                'CreateRequest',
                'DeleteRequest',
                'DetailRequest',
                'EditRequest',
                'ImportRequest',
                'ExportRequest',
                'ListRequest'
            ];

            $tableName = Cache::driver('array')->get('auto_file_table_name', '');
            # controller 选项指定 数据库表
            if ($this->option('table') && !$tableName) $tableName = $this->option('table');

            foreach ($templateRequestArr as $templateRequest) {
                $params = ['name' => $subDir . DIRECTORY_SEPARATOR . $templateRequest];
                if ($tableName) $params['--table'] = $tableName;
                $this->extraClassGenerate('request', $params);
            }
        }

        return str_replace(
            array_keys($replace), array_values($replace), parent::buildClass($name)
        );
    }


    /**
     * Notes:指定控制器基类
     * User: weicheng
     * DateTime: 2022/1/19 15:15
     * @param $name
     * @return array
     */
    protected function buildBaseControllerReplacements($name): array
    {
        $application         = array_slice(explode('\\', str_replace('App\Http\Controllers\\', '', $name)), 0, 1);
        $definedRelation     = config('autoFile.controller.base_class', []);
        $definedApplications = array_keys($definedRelation);
        if (empty($application) || !in_array($application[0], $definedApplications)) {
            $baseControllerPath = $definedRelation['default'] ?? 'App\Http\Controllers\Controller';
        } else {
            $baseControllerPath = $definedRelation[$application[0]];
        }
        $baseControllerName = Arr::last(explode('\\', $baseControllerPath));

        return [
            '{{ baseController }}'     => $baseControllerName,
            '{{ baseControllerPath }}' => $baseControllerPath
        ];
    }

    /**
     * Notes:command options
     * 命令选项
     * User: weicheng
     * DateTime: 2022/1/19 10:43
     * @return array[]
     */
    public function getOptions(): array
    {
        # 选项名,简写，选项类型，选项描述，默认值
        return [
            ['force', null, InputOption::VALUE_NONE, 'Generate the class even if the controller already exists'],
            ['template', 't', InputOption::VALUE_NONE, 'Generate a template controller class.'],
            ['resource', 'r', InputOption::VALUE_NONE, 'Generate a resource controller class.'],
            ['table', null, InputOption::VALUE_OPTIONAL, 'Define the Request\'s table.', null],
        ];
    }
}
