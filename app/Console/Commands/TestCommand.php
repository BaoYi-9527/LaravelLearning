<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class TestCommand extends Command
{
    /**
     * 命令名
     * @var string
     * {params : paramsDesc} -> 必要参数 : 参数描述(空格必须)
     * {params?} -> 可选参数 / {params=defaultValue} -> 参数默认值 / {params? : paramsDesc}
     * {--optionName} -> 不带值选项：带有该选项为 true, 反之为 false
     * {--optionName=} -> 要求用户必须为选项指定值
     * {--optionName=defaultValue} -> 选项设置默认值
     * {--optionSimpleName|optionName} -> 选项简写字母(严格区分大小写)|选项名
     * {--optionName : optionDesc}  -> 选项 : 选项描述
     * {arrParams*} -> 参数接受数组输入
     * {--ids=*} -> 可选数组参数
     */
    protected $signature = 'test:test';
//    protected $signature = 'test:test {text} {--U|uppercase}';    #示例1 php artisan test:test HelloWorld -U
//    protected $signature = 'test:test {fruits*}';   #示例2 php artisan test:test apple banana tomato
//    protected $signature = 'test:test {--ids=*}';   #示例2 php artisan test:test apple banana tomato

    /**
     * 命令描述
     * @var string
     */
    protected $description = "This is a test command's description!!!";

    /**
     * 创建一个新的命令实例
     * 9.30--- 12.15 2:45
     * 13:30---18:30 5:00
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 执行 console 命令
     */
    public function handle()
    {
        foreach ((array) 'app' as $item) {
            dump($item);
        }


//        dd($this->laravel->getNamespace());

        $text      = $this->argument('text');       // 特定参数
        $arguments = $this->arguments();                // 所有参数[不含选项]
        $upper     = $this->option('uppercase');    // 选项
        $options   = $this->options();                  // 所有选项

        // 示例1
        $quote = !$upper ? $text : strtoupper($text);
        $this->comment($quote);                         // "HELLOWORLD"

        // 示例2
        $fruits  = $this->argument('fruits');
        $this->comment(json_encode($fruits));         // "["apple","banana","tomato"]"
        $idsOption = $this->option('ids');
        $this->comment(json_encode($idsOption));        // ["1","2"]

        // 示例3 询问/交互式输入
        $name = $this->ask('What is your name?');
        $this->comment($name);
        $pwd = $this->secret('Password:');
        $this->comment($pwd);
        $confirmed = $this->confirm('Are you want to continue?');
        dump($confirmed, gettype($confirmed));  # true Boolean
        $anticipate = $this->anticipate('Where are you from?', ['Beijing', 'Shanghai']);    # 自动补全?
        $gender = $this->choice('<question> What is your gender? </question>', ['male', 'female'], 0); # 0 为数组索引
        dump($gender, gettype($gender));    # male string

        // 示例4 单行输出
        $this->line('line <info>info</info> <error>error</error> <comment>comment</comment> <question>question</question>');
        $this->info('This is info');
        $this->error('This is error');
        $this->comment('This is comment');
        $this->question('This is question');
        $this->line("1\n2\n");

        // 示例5 表格输出
        $headers    = ['日期', '订单数', '备注'];
        $orders     = [
            ['2019-05-20', '75'],
            ['2019-05-21', '76'],
            ['2019-05-22', '77'],
            ['2019-05-23', '78'],
            ['2019-05-24', '79', '促销'],
        ];
        $tableStyle = $this->choice(
            '请选择表格样式',
            ['default', 'borderless', 'compact', 'symfony-style-guide', 'box', 'box-double']
        );
        $this->table($headers, $orders, $tableStyle);

        // 示例6 进度条
        $arr   = [1, 2, 3, 4, 5];
        $total = count($arr);
        $bar   = $this->output->createProgressBar($total);

        if ($total) {
            $bar->setBarCharacter('<info>▦</info>');
            $bar->setEmptyBarCharacter(' ');
            $bar->setProgressCharacter(' ');
            $bar->setFormat('debug');
            $bar->setBarWidth(50);
        }

        foreach ($arr as $item) {
            sleep(1);
            $bar->advance();
        }
        $bar->finish();

        // 示例7 代码中调用 artisan 命令
        Artisan::call('inspire');         // Facade方式
        $this->call('inspire');           // 命令调用命令方式
        $this->callSilent('inspire');     // 静默输出
        $this->callSilently('inspire');   // 静默输出,同上[无区别]

        // 示例8 检测是否在命令行环境下
        $isConsole = app()->runningInConsole();
        dump(php_sapi_name());    // php原生方式[返回 web 服务器和 PHP 之间的接口类型]

        // 示例9 调用外部命令
        $process = new Process(['ls']);
        $process->run();
        $res = $process->isSuccessful();    # windows下 error

        if (!$res) {
            throw new ProcessFailedException($process);
        }

        $outPut = $process->getOutput();
        dump($outPut);
    }
}
