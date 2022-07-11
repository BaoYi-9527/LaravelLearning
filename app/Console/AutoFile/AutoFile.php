<?php

namespace App\Console\Commands\AutoFile;

use Symfony\Component\Console\Input\InputOption;

trait AutoFile
{

    /**
     * Notes: 重载父类方法对类名进行补全
     * User: weicheng
     * DateTime: 2022/1/20 11:02
     * @return string
     */
    protected function getNameInput(): string
    {
        $name = parent::getNameInput();
        $this->type = explode("\040", $this->type)[0];
        if (!str_contains($name,  $this->type)) $name .= $this->type;
        return $name;
    }

    /**
     * Notes:其他类的生成
     * User: weicheng
     * DateTime: 2022/1/20 11:26
     * @param $classType
     * @param array $params
     */
    public function extraClassGenerate($classType, array $params = [])
    {
        $this->call('auto:' . $classType, $params);
    }

    /**
     * Notes:路径解析
     * User: weicheng
     * DateTime: 2022/1/19 15:54
     * @param $path
     * @return string
     */
    protected function parsePath($path): string
    {
        if (preg_match('([^A-Za-z0-9_/\\\\])', $path)) {
            $this->error('Controller path [' . $path . '] contains invalid characters.');
            die();
        }

        return trim(str_replace('/', '\\', $path), '\\');
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param string $stub
     * @return string
     */
    protected function resolveStubPath(string $stub): string
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }

    /**
     * Notes:选项
     * User: weicheng
     * DateTime: 2022/1/19 16:48
     * @return array[]
     */
    protected function getOptions(): array
    {
        # 选项名,简写，选项类型，选项描述，默认值
        return [
            ['force', null, InputOption::VALUE_NONE, 'Create the class even if the controller already exists'],
        ];
    }

    /**
     * Get the class name.
     *
     * @param  string  $name
     * @return string
     */
    protected function getClassName($name)
    {
        return trim(implode('', array_slice(explode('\\', $name), -1)), '\\');
    }

    /**
     * Notes:驼峰转下划线命名
     * User: weicheng
     * DateTime: 2022/1/25 17:36
     * @param $camelCaps
     * @param string $separator
     * @return string
     */
    public function toUnderScore($camelCaps, string $separator = '_'): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $camelCaps));
    }
}
