<?php

namespace App\Console\Commands\AutoFile;

use \Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\InputOption;

class AutoModelCommand extends GeneratorCommand
{

    use AutoFile;

    protected $name        = 'auto:model';
    protected $description = 'Generate a Model class.';
    protected $type        = 'Model';

    /**
     * Notes: 重载 AutoFile 中的方法，Model不需要进行类名补全
     * User: weicheng
     * DateTime: 2022/1/20 11:02
     * @return string
     */
    protected function getNameInput(): string
    {
        return parent::getNameInput();
    }

    /**
     * Notes:Get the stub file for the generator.
     * User: weicheng
     * DateTime: 2022/1/19 16:20
     * @return string
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/model.stub');
    }


    /**
     * Notes:Build the Model with the given name.
     * User: weicheng
     * DateTime: 2022/1/19 16:55
     * @param string $name
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildClass($name): string
    {
        $this->type .= "\040" . $name;
        if ($this->option('table')) {
            $tableName = $this->option('table');
        } else {
            # 类名自动转换为表名【驼峰命名->表前缀_蛇形命名】
            $tablePrefix         = config('autoFile.model.table_prefix', 'v4');
            $tableUnderScoreName = $this->toUnderScore($this->getClassName($name));
            $tableName           = $tablePrefix ? $tablePrefix . '_' . $tableUnderScoreName : $tableUnderScoreName;
        }
        # 生成验证类使用
        Cache::driver('array')->set('auto_file_table_name', $tableName);

        $replace = [
            '{{ tableName }}' => $tableName
        ];

        $fillableArr = $this->generateRulesAndAttr($tableName);
        $replace['{{ fillableArr }}']      = $this->arr2str($fillableArr, 2, 30);

        return str_replace(
            array_keys($replace), array_values($replace), parent::buildClass($name)
        );
    }

    /**
     * Notes:数组转字符串并保留数组格式
     * User: weicheng
     * DateTime: 2022/1/26 10:59
     * @param $arr
     * @param int $t //填充长度
     * @param int $keyLength
     * @return string|null
     */
    private function arr2str($arr, int $t = 0, int $keyLength = 0): ?string
    {
        $str = null;
        $pad = str_pad("", $t, "\t");

        foreach ($arr as $k => $v) {
            if (is_array($v)) {
                if (is_string($k)) {
                    $str .= $pad . "'" . $k . "'=>[\n" . $this->arr2str($v, $t + 1) . $pad . "],\n";
                } else {
                    $str .= $pad . "[\n" . $this->arr2str($v, $t + 1) . $pad . "],\n";
                }
            } elseif (is_string($k)) {
                $str .= str_pad($pad . "'" . $k . "'", $keyLength, "\040") . "=>  '" . $v . "',\n";
            } else {
                $str .= $pad . "'" . $v . "',\n";
            }
        }
        $str = ltrim($str, $pad);
        return rtrim($str, ",\n");
    }

    /**
     * Notes:获取表字段信息后生成验证规则
     * User: weicheng
     * DateTime: 2022/1/26 10:37
     * @param $table
     * @param $className
     * @return array[]
     */
    private function generateRulesAndAttr($table): array
    {
        # 获取表字段信息
        $rawSql = <<<'SQL'
                SELECT
                        column_name,
                        data_type,
                        column_comment,
                        character_maximum_length,
                        is_nullable,
                        column_key
                    FROM
                        information_schema.COLUMNS
                    WHERE
                table_name = ?
SQL;
        $tableDesc = DB::select($rawSql, [$table]);
        $fieldsArr =  array_column($tableDesc, 'column_name');
        # 过滤一些非必要字段
        $fillableArr = [];
        foreach ($fieldsArr as $field) {
            if($field == 'id') continue;
            $fillableArr[] = $field;
        }
        return $fillableArr;
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return is_dir(app_path('Models')) ? $rootNamespace.'\\Models' : $rootNamespace;
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
            ['force', null, InputOption::VALUE_NONE, 'Generate the class even if the controller already exists'],
            ['table', 't', InputOption::VALUE_OPTIONAL, 'Define the Model\'s database table.', null],
        ];
    }

}
