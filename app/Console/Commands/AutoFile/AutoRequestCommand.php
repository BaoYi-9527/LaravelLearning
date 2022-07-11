<?php

namespace App\Console\Commands\AutoFile;

use \Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Input\InputOption;

class AutoRequestCommand extends GeneratorCommand
{
    use AutoFile;

    protected $name        = 'auto:request';
    protected $description = 'Generate a Request class.';
    protected $type        = 'Request';

    # Request 验证 解析为 string 的数据类型
    const STRING_FILED_TYPE  = [
        'char', 'enum', 'longtext', 'mediumtext', 'set', 'text', 'tinytext', 'varchar', # 字符串
        'date', 'datetime', 'time', 'year', # 日期与时间类型
    ];
    # Request 验证 解析为 numeric 的数据类型
    const NUMBER_FILED_TYPE  = [
        'bit', 'bigint', 'decimal', 'double', 'float', 'int', 'mediumint', 'real', 'smallint', 'tinyint',
        'timestamp'
    ];
    # Request 验证 解析为 boolean 的数据类型
    const BOOLEAN_FILED_TYPE = [
        'boolean'
    ];

    /**
     * Notes:获取模板文件
     * User: weicheng
     * DateTime: 2022/1/19 17:08
     * @return string
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/request.stub');
    }

    /**
     * Notes:构建类文件
     * User: weicheng
     * DateTime: 2022/1/26 11:27
     * @param string $name
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildClass($name): string
    {
        $this->type .= "\040" . $name;

        $replace = [
            '{{ rules }}'      => '',
            '{{ attributes }}' => ''
        ];

        # 读取表结构信息，并生成对应的验证规则
        if ($this->option('table')) {
            $table       = $this->option('table');
            $tableExists = Schema::hasTable($table);
            if (!$tableExists) {
                $this->error('The database table ' . $table . ' is not exists!');
                die();
            }

            $className = $this->getClassName($name);
            list($rules, $attributes) = $this->generateRulesAndAttr($table, $className);

            $replace['{{ rules }}']      = $this->arr2str($rules, 3, 30);
            $replace['{{ attributes }}'] = $this->arr2str($attributes, 3, 30);
        }

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
        $str = "\040\040\040\040" . ltrim($str, $pad);
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
    private function generateRulesAndAttr($table, $className): array
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

        $rules = $attributes = [];

        # 特殊 Request 模板类的处理
        if ($className == 'ListRequest') {
            $rules = [
                'page'     => 'required|int',
                'per_page' => 'required|int',
            ];
            $attributes = [
                'page'     => '请求页码',
                'per_page' => '每页数据条数',
            ];
            return [$rules, $attributes];
        }

        foreach ($tableDesc as $column) {

            # 特殊 Request 模板类的处理
            if (
                ($className == 'DeleteRequest' || $className == 'DetailRequest') &&
                $column['column_key'] !== 'PRI'
            ) continue;

            $attributes[$column['column_name']] = $column['column_comment'] ?: $column['column_name'];
            # 是否必填
            if ($column['is_nullable'] === 'NO') {
                $rules[$column['column_name']] = 'required';
            } else {
                $rules[$column['column_name']] = 'sometimes|nullable';
            }
            # 类型
            if (in_array($column['data_type'], self::NUMBER_FILED_TYPE)) {
                $rules[$column['column_name']] .= '|numeric';
            } elseif (in_array($column['data_type'], self::BOOLEAN_FILED_TYPE)) {
                $rules[$column['column_name']] .= '|boolean';
            } else {
                $rules[$column['column_name']] .= '|string';
            }
            # 字段长度
            if ($column['character_maximum_length']) {
                $rules[$column['column_name']] .= '|max:' . $column['character_maximum_length'];
            }
        }

        return [$rules, $attributes];
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return is_dir(app_path('Http' . DIRECTORY_SEPARATOR . 'Requests')) ?
            $rootNamespace . '\\' . 'Http' . '\\' . 'Requests' :
            $rootNamespace;
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
            ['table', 't', InputOption::VALUE_OPTIONAL, 'Define the Request\'s table.', null],
        ];
    }

}
