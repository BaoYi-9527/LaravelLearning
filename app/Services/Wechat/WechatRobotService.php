<?php

namespace App\Services\Wechat;

use App\Services\Singleton;
use GuzzleHttp\Client;

class WechatRobotService extends Singleton
{
    protected $client;
    protected $api    = '';
    protected $data   = [];
    protected $method = self::REQUEST_METHOD_GET;
    protected $level  = self::MSG_LEVEL_INFO;

    # 企微推送机器人 key
    const LOG_WECHAT_ROBOT_KEY = '';                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         # 默认使用的机器人

    const WECHAT_ROBOT_MAP = [
        'default' => self::LOG_WECHAT_ROBOT_KEY
    ];

    const FONT_COLORS = [
        'error' => 'warning',
        'info'  => 'comment'
    ];

    const AT_ALL_LEVELS = [
        'emergency', 'alert', 'critical'
    ];
    # 请求方式
    const REQUEST_METHOD_GET  = 'GET';
    const REQUEST_METHOD_POST = 'POST';
    # 推送级别
    const MSG_LEVEL_INFO  = 'info';                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           # 普通消息【提示性消息】
    const MSG_LEVEL_ERROR = 'error';                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         # 异常消息【程序/业务发生异常需排查类消息】

    protected function __construct()
    {
        parent::__construct();
    }

    /**
     * Notes:指定链接的推送机器人
     * @param string $robot
     * @return $this
     */
    public function connection(string $robot = 'default'): WechatRobotService
    {
        $robotKey     = self::WECHAT_ROBOT_MAP[$robot];
        $webHook  = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=' . $robotKey;
        $this->client = new Client([
            'base_uri' => $webHook,
            'verify'   => false,
            'timeout'  => 30
        ]);

        return $this;
    }


    /**
     * Notes:请求
     * @return mixed
     */
    public function request($api, $data, $method)
    {
        # 若没有初始化 client 使用默认
        if (is_null($this->client)) $this->connection();

        if ($method === 'GET') {
            $options = ['query' => $data];
        } else {
            $options = ['json' => $data];
        }
        $response = $this->client->request($method, $api, $options);
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Notes:推送文本消息
     * @param $content // 文本内容
     * @param array $mentionList // @all 提醒人员 user_id 列表
     * @param array $mentionMobileList // 提醒群成员 手机号 列表
     * @return mixed
     */
    public function textMessage($content, array $mentionList = [], array $mentionMobileList = [])
    {
        $msgFormat = [
            'msgtype' => 'text',
            'text'    => [
                'content'               => $content,
                'mentioned_list' => $mentionList,
                'mentioned_mobile_list' => $mentionMobileList
            ]
        ];

        return $this->request($this->api, $msgFormat, self::REQUEST_METHOD_POST);
    }

    /**
     * Notes:markdown 类型消息
     * @param $content
     * @return mixed
     */
    public function markdownMessage($content)
    {
        $msgFormat = [
            'msgtype'  => 'markdown',
            'markdown' => [
                'content' => $content,
            ]
        ];

        return $this->request($this->api, $msgFormat, self::REQUEST_METHOD_POST);
    }

    /**
     * Notes:图片类型
     * @param $base64 // 图片内容的 base64 编码
     * @param $md5 // 图片内容 (base64编码前) 的 md5 值
     * @return mixed
     */
    public function imageMessage($base64, $md5)
    {
        $msgFormat = [
            'msgtype' => 'image',
            'image'   => [
                'base64' => $base64,
                'md5'    => $md5
            ]
        ];
        return $this->request($this->api, $msgFormat, self::REQUEST_METHOD_POST);
    }

    /**
     * Notes:图文消息
     * @param $articles
     * @paramsExample
     * $title -> 标题,$desc -> 描述,$url -> 点击后跳转的链接,$picUrl -> 图文消息的图片链接
     * [{"title":"中秋节礼品领取","description":"今年中秋节公司有豪礼相送","url":"www.qq.com","picurl":"http://res.mail.qq.com/node/ww/wwopenmng/images/independent/doc/test_pic_msg1.png"}]
     * @return mixed
     */
    public function newMessage($articles)
    {
        $msgFormat = [
            'msgtype' => 'news',
            'text'    => [
                'articles' => $articles
            ]
        ];
        return $this->request($this->api, $msgFormat, self::REQUEST_METHOD_POST);
    }

    public function error(): WechatRobotService
    {
        $this->level = self::MSG_LEVEL_ERROR;
        return $this;
    }

    public function info(): WechatRobotService
    {
        $this->level = self::MSG_LEVEL_INFO;
        return $this;
    }

    /**
     * Notes: 推送 markdown 类型企微消息
     * @param $msg
     * @param \Exception|null $exception 异常上下文
     * @return bool
     */
    public function logWechatPush($msg, \Exception $exception = null): bool
    {
        $upperLevel  = strtoupper($this->level);
        $env        = strtoupper(env('APP_ENV'));
        $currentTime = date('Y-m-d H:i:s');
        $color       = self::FONT_COLORS[$this->level];

        # 构建 markdown 格式
        $title    = <<<MARKDOWN
### <font color="{$color}">【{$env}】 $upperLevel: $msg</font>
MARKDOWN;
        $markdown = $title . PHP_EOL;

        if (!is_null($exception)) {
            $markdown .= <<<MARKDOWN
> time: <font color="comment">{$currentTime}</font>
> code: <font color="comment">{$exception->getCode()}</font>
> message: <font color="comment">{$exception->getMessage()}</font>
> line: <font color="comment">{$exception->getLine()}</font>
> file: <font color="comment">{$exception->getFile()}</font>
MARKDOWN;
        }

        $markdown = $markdown . PHP_EOL;
        # 是否在CLI环境下
        if (is_cli()) {
            $command  = implode(' ', $GLOBALS['argv']);
            $markdown .= <<<MARKDOWN
> type: <font color="comment">console</font>
> comamnd: <font color="comment">{$command}</font>
MARKDOWN;
        } else {
            $requestParams = json_encode($GLOBALS['_REQUEST']);
            $markdown      .= <<<MARKDOWN
> type: <font color="comment">api</font>
> uri: <font color="comment">{$_SERVER['REQUEST_URI']}</font>
> params: <font color="comment">{$requestParams}</font>
MARKDOWN;
        }

        $markdownRes = $this->markdownMessage($markdown);

        if ($markdownRes['errcode'] != 0) {
            $errorInfo = [
                'title'   => $title,
                'err_msg' => $markdownRes['errmsg']
            ];
            $this->textMessage(json_encode($errorInfo), ['@all']);
        } elseif ($this->level == self::MSG_LEVEL_ERROR) {
            $this->textMessage('', ['@all']);
        }

        return true;
    }
}
