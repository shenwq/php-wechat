<?php
declare (strict_types=1);

namespace ffhome\wechat;

/**
 * 微信回调类
 * Class WechatCallback
 * @package ffhome\wechat
 */
class WechatCallback
{
    /* 消息类型常量 */
    const MSG_TYPE_TEXT = 'text';
    const MSG_TYPE_IMAGE = 'image';
    const MSG_TYPE_VOICE = 'voice';
    const MSG_TYPE_VIDEO = 'video';
    const MSG_TYPE_MUSIC = 'music';
    const MSG_TYPE_NEWS = 'news';
    const MSG_TYPE_LOCATION = 'location';
    const MSG_TYPE_LINK = 'link';
    const MSG_TYPE_EVENT = 'event';
    const MSG_TRANSFER_CUSTOMER_SERVICE = 'transfer_customer_service';

    /* 事件类型常量 */
    const MSG_EVENT_SUBSCRIBE = 'subscribe';
    const MSG_EVENT_UNSUBSCRIBE = 'unsubscribe';
    const MSG_EVENT_SCAN = 'SCAN';
    const MSG_EVENT_LOCATION = 'LOCATION';
    const MSG_EVENT_CLICK = 'CLICK';
    const MSG_EVENT_MASSSENDJOBFINISH = 'MASSSENDJOBFINISH';
    const MSG_EVENT_TEMPLATESENDJOBFINISH = 'TEMPLATESENDJOBFINISH';

    /**
     * 微信推送过来的数据
     * @var array
     */
    private $data = [];

    /**
     * 构造方法
     * @param string $token 微信后台填写的TOKEN
     */
    public function __construct(string $token)
    {
        if ($token) {
            self::auth($token) || exit;
            if (isset($_GET['echostr'])) {
                exit($_GET['echostr']);
            } else {
                $xml = file_get_contents("php://input");
                $xml = new \SimpleXMLElement($xml);
                $xml || exit;

                foreach ($xml as $key => $value) {
                    $this->data[$key] = strval($value);
                }
            }
        } else {
            throw new \Exception('参数错误！');
        }
    }

    /**
     * 获取微信推送的数据
     * @return array 转换为数组后的数据
     */
    public function request(): array
    {
        return $this->data;
    }

    /**
     * 回复文本消息
     * @param string $text 回复的文字
     */
    public function replyText(string $text)
    {
        return $this->response($text, self::MSG_TYPE_TEXT);
    }

    /**
     * 回复图片消息
     * @param string $media_id 图片ID
     */
    public function replyImage(string $media_id)
    {
        return $this->response($media_id, self::MSG_TYPE_IMAGE);
    }

    /**
     * 回复语音消息
     * @param string $media_id 音频ID
     */
    public function replyVoice(string $media_id)
    {
        return $this->response($media_id, self::MSG_TYPE_VOICE);
    }

    /**
     * 回复视频消息
     * @param string $media_id 视频ID
     * @param string $title 视频标题
     * @param string $discription 视频描述
     */
    public function replyVideo(string $media_id, string $title, string $discription)
    {
        return $this->response(func_get_args(), self::MSG_TYPE_VIDEO);
    }

    /**
     * 回复音乐消息
     * @param string $title 音乐标题
     * @param string $discription 音乐描述
     * @param string $musicurl 音乐链接
     * @param string $hqmusicurl 高品质音乐链接
     * @param string $thumb_media_id 缩略图ID
     */
    public function replyMusic(string $title, string $discription, string $musicurl, string $hqmusicurl, string $thumb_media_id)
    {
        return $this->response(func_get_args(), self::MSG_TYPE_MUSIC);
    }

    /**
     * 回复图文消息，一个参数代表一条信息
     * @param array $news 图文内容 [标题，描述，URL，缩略图]
     * @param array $news1 图文内容 [标题，描述，URL，缩略图]
     * @param array $news2 图文内容 [标题，描述，URL，缩略图]
     *                ...     ...
     * @param array $news9 图文内容 [标题，描述，URL，缩略图]
     */
    public function replyNews(array $news, array $news1, array $news2, array $news3)
    {
        return $this->response(func_get_args(), self::MSG_TYPE_NEWS);
    }

    /**
     * 回复一条图文消息
     * @param string $title 文章标题
     * @param string $discription 文章简介
     * @param string $url 文章连接
     * @param string $picurl 文章缩略图
     */
    public function replyNewsOnce(string $title, string $discription, string $url, string $picurl)
    {
        return $this->response(array(func_get_args()), self::MSG_TYPE_NEWS);
    }

    /**
     * 转移到客服处理
     * @param string $account 客户账号
     */
    public function transferCustomerService(string $account = '')
    {
        /* 基础数据 */
        $data = array(
            'ToUserName' => $this->data['FromUserName'],
            'FromUserName' => $this->data['ToUserName'],
            'CreateTime' => NOW_TIME,
            'MsgType' => self::MSG_TRANSFER_CUSTOMER_SERVICE,
        );
        if (!empty($account)) {
            $data['TransInfo'] = array('KfAccount' => $account);
        }
        /* 转换数据为XML */
        $xml = new \SimpleXMLElement('<xml></xml>');
        self::data2xml($xml, $data);
        exit($xml->asXML());
    }

    /**
     * * 响应微信发送的信息（自动回复）
     * @param array $content 回复信息，文本信息为string类型
     * @param string $type 消息类型
     */
    public function response($content, string $type = self::MSG_TYPE_TEXT)
    {
        /* 基础数据 */
        $data = array(
            'ToUserName' => $this->data['FromUserName'],
            'FromUserName' => $this->data['ToUserName'],
            'CreateTime' => $_SERVER['REQUEST_TIME'],
            'MsgType' => $type,
        );

        /* 按类型添加额外数据 */
        $content = call_user_func(array('self', $type), $content);
        if ($type == self::MSG_TYPE_TEXT || $type == self::MSG_TYPE_NEWS) {
            $data = array_merge($data, $content);
        } else {
            $data[ucfirst($type)] = $content;
        }

        /* 转换数据为XML */
        $xml = new \SimpleXMLElement('<xml></xml>');
        self::data2xml($xml, $data);
        exit($xml->asXML());
    }

    /**
     * 构造文本信息
     * @param string $content 要回复的文本
     */
    private static function text($content)
    {
        $data['Content'] = $content;
        return $data;
    }

    /**
     * 构造图片信息
     * @param integer $media 图片ID
     */
    private static function image($media)
    {
        $data['MediaId'] = $media;
        return $data;
    }

    /**
     * 构造音频信息
     * @param integer $media 语音ID
     */
    private static function voice($media)
    {
        $data['MediaId'] = $media;
        return $data;
    }

    /**
     * 构造视频信息
     * @param array $video 要回复的视频 [视频ID，标题，说明]
     */
    private static function video($video)
    {
        $data = array();
        list(
            $data['MediaId'],
            $data['Title'],
            $data['Description'],
            ) = $video;

        return $data;
    }

    /**
     * 构造音乐信息
     * @param array $music 要回复的音乐[标题，说明，链接，高品质链接，缩略图ID]
     */
    private static function music($music)
    {
        $data = array();
        list(
            $data['Title'],
            $data['Description'],
            $data['MusicUrl'],
            $data['HQMusicUrl'],
            $data['ThumbMediaId'],
            ) = $music;

        return $data;
    }

    /**
     * 构造图文信息
     * @param array $news 要回复的图文内容
     * [
     *      0 => 第一条图文信息[标题，说明，图片链接，全文连接]，
     *      1 => 第二条图文信息[标题，说明，图片链接，全文连接]，
     *      2 => 第三条图文信息[标题，说明，图片链接，全文连接]，
     * ]
     */
    private static function news($news)
    {
        $articles = array();
        foreach ($news as $key => $value) {
            list(
                $articles[$key]['Title'],
                $articles[$key]['Description'],
                $articles[$key]['Url'],
                $articles[$key]['PicUrl']
                ) = $value;

            if ($key >= 9) break; //最多只允许10条图文信息
        }
        $data['ArticleCount'] = count($articles);
        $data['Articles'] = $articles;

        return $data;
    }

    /**
     * 对数据进行签名认证，确保是微信发送的数据
     * @param string $token 微信开放平台设置的TOKEN
     * @return boolean       true-签名正确，false-签名错误
     */
    protected static function auth(string $token)
    {
        /* 获取数据 */
        $data = array($_GET['timestamp'], $_GET['nonce'], $token);
        $sign = $_GET['signature'];

        /* 对数据进行字典排序 */
        sort($data, SORT_STRING);

        /* 生成签名 */
        $signature = sha1(implode($data));
        return $signature === $sign;
    }

    /**
     * 数据XML编码
     * @param object $xml XML对象
     * @param mixed $data 数据
     * @param string $item 数字索引时的节点名称
     * @return string
     */
    protected static function data2xml($xml, $data, $item = 'item')
    {
        foreach ($data as $key => $value) {
            /* 指定默认的数字key */
            is_numeric($key) && $key = $item;

            /* 添加子元素 */
            if (is_array($value) || is_object($value)) {
                $child = $xml->addChild($key);
                self::data2xml($child, $value, $item);
            } else {
                if (is_numeric($value)) {
                    $child = $xml->addChild($key, $value);
                } else {
                    $child = $xml->addChild($key);
                    $node = dom_import_simplexml($child);
                    $cdata = $node->ownerDocument->createCDATASection($value);
                    $node->appendChild($cdata);
                }
            }
        }
    }

    /**
     * 将模板消息转为字符串
     * @param $info
     * @return string
     */
    public static function convertTemplateInfo($info)
    {
        $ret = array();
        foreach ($info as $key => $value) {
            if (is_array($value)) {
                $ret[] = "{$key}=>{$value['value']}";
            }
        }
        return implode(',', $ret);
    }
}