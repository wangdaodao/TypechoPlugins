<?php
/**
 * Bilibili播放器插件
 *
 * @package BilibiliPlayer
 * @author  王叨叨
 * @version 1.0.0
 * @link    https://wangdaodao.com
 * @description 将Bilibili默认播放器替换为HTML5移动端播放器，并提供更多自定义选项
 */

!defined('__TYPECHO_ROOT_DIR__') && exit();

class BilibiliPlayer_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('BilibiliPlayer_Plugin', 'replacePlayer');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('BilibiliPlayer_Plugin', 'replacePlayer');
        return _t('插件已激活，将在内容渲染时替换Bilibili播放器');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate()
    {
        return _t('插件已禁用，Bilibili播放器将恢复默认状态');
    }

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        /** 视频宽度 */
        $width = new Typecho_Widget_Helper_Form_Element_Text(
            'width',
            null,
            '100%',
            _t('视频宽度'),
            _t('设置视频播放器的宽度，例如：100%, 800px')
        );
        $form->addInput($width);

        /** 视频高度 */
        $height = new Typecho_Widget_Helper_Form_Element_Text(
            'height',
            null,
            '500px',
            _t('视频高度'),
            _t('设置视频播放器的高度，例如：500px, 400px')
        );
        $form->addInput($height);

        /** 是否自动播放 */
        $autoplay = new Typecho_Widget_Helper_Form_Element_Radio(
            'autoplay',
            array('0' => _t('关闭'), '1' => _t('开启')),
            '0',
            _t('是否自动播放'),
            _t('设置视频是否自动播放（注意：大多数浏览器会阻止自动播放）')
        );
        $form->addInput($autoplay);

        /** 默认弹幕开关 */
        $danmaku = new Typecho_Widget_Helper_Form_Element_Radio(
            'danmaku',
            array('0' => _t('关闭'), '1' => _t('开启')),
            '1',
            _t('默认弹幕开关'),
            _t('设置弹幕是否默认开启')
        );
        $form->addInput($danmaku);

        /** 是否默认静音 */
        $muted = new Typecho_Widget_Helper_Form_Element_Radio(
            'muted',
            array('0' => _t('关闭'), '1' => _t('开启')),
            '0',
            _t('是否默认静音'),
            _t('设置视频是否默认静音')
        );
        $form->addInput($muted);

        /** 一键静音按钮是否显示 */
        $hasMuteButton = new Typecho_Widget_Helper_Form_Element_Radio(
            'hasMuteButton',
            array('0' => _t('不显示'), '1' => _t('显示')),
            '0',
            _t('一键静音按钮是否显示'),
            _t('设置是否显示一键静音按钮')
        );
        $form->addInput($hasMuteButton);

        /** 视频封面下方是否显示播放量弹幕量等信息 */
        $hideCoverInfo = new Typecho_Widget_Helper_Form_Element_Radio(
            'hideCoverInfo',
            array('0' => _t('显示'), '1' => _t('隐藏')),
            '0',
            _t('视频封面下方信息显示'),
            _t('设置是否隐藏视频封面下方的播放量、弹幕量等信息')
        );
        $form->addInput($hideCoverInfo);

        /** 是否隐藏弹幕按钮 */
        $hideDanmakuButton = new Typecho_Widget_Helper_Form_Element_Radio(
            'hideDanmakuButton',
            array('0' => _t('不隐藏'), '1' => _t('隐藏')),
            '0',
            _t('是否隐藏弹幕按钮'),
            _t('设置是否隐藏弹幕按钮')
        );
        $form->addInput($hideDanmakuButton);

        /** 是否隐藏全屏按钮 */
        $noFullScreenButton = new Typecho_Widget_Helper_Form_Element_Radio(
            'noFullScreenButton',
            array('0' => _t('显示'), '1' => _t('隐藏')),
            '0',
            _t('是否隐藏全屏按钮'),
            _t('设置是否隐藏全屏按钮')
        );
        $form->addInput($noFullScreenButton);

        /** 是否开始记忆播放 */
        $fjw = new Typecho_Widget_Helper_Form_Element_Radio(
            'fjw',
            array('0' => _t('关闭'), '1' => _t('开启')),
            '1',
            _t('是否开始记忆播放'),
            _t('设置是否开启记忆播放功能')
        );
        $form->addInput($fjw);
    }

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
        // 个人用户配置，如果需要的话
    }

    /**
     * 替换Bilibili播放器
     *
     * @access public
     * @param string $content 文章内容
     * @param Widget_Abstract_Contents $widget 内容对象
     * @param string $lastResult 上一次处理结果
     * @return string
     */
    public static function replacePlayer($content, $widget, $lastResult)
    {
        $content = empty($lastResult) ? $content : $lastResult;
        
        // 获取插件配置
        $options = Helper::options();
        $config = $options->plugin('BilibiliPlayer');

        if (!$config) {
            return $content;
        }

        // 获取配置参数
        $width = $config->width ?: '100%';
        $height = $config->height ?: '500px';
        $autoplay = $config->autoplay ?: '0';
        $danmaku = $config->danmaku ?: '1';
        $muted = $config->muted ?: '0';
        $hasMuteButton = $config->hasMuteButton ?: '0';
        $hideCoverInfo = $config->hideCoverInfo ?: '0';
        $hideDanmakuButton = $config->hideDanmakuButton ?: '0';
        $noFullScreenButton = $config->noFullScreenButton ?: '0';
        $fjw = $config->fjw ?: '1';

        // 构建参数字符串
        $params = array();
        if ($autoplay) $params[] = 'autoplay=' . $autoplay;
        if ($danmaku) $params[] = 'danmaku=' . $danmaku;
        if ($muted) $params[] = 'muted=' . $muted;
        if ($hasMuteButton) $params[] = 'hasMuteButton=' . $hasMuteButton;
        if ($hideCoverInfo) $params[] = 'hideCoverInfo=' . $hideCoverInfo;
        if ($hideDanmakuButton) $params[] = 'hideDanmakuButton=' . $hideDanmakuButton;
        if ($noFullScreenButton) $params[] = 'noFullScreenButton=' . $noFullScreenButton;
        if ($fjw) $params[] = 'fjw=' . $fjw;

        $paramString = empty($params) ? '' : '&' . implode('&', $params);

        // 正则匹配Bilibili播放器的iframe
        $pattern = '/<iframe[^>]*src\s*=\s*["\']\/\/player\.bilibili\.com\/player\.html([^"\']*)["\'][^>]*>.*?<\/iframe>/is';

        // 替换函数
        $content = preg_replace_callback($pattern, function($matches) use ($width, $height, $paramString) {
            // 获取原始参数
            $originalParams = $matches[1];

            // 构建新的src
            $newSrc = '//www.bilibili.com/blackboard/html5mobileplayer.html' . $originalParams . $paramString;

            // 获取完整的iframe标签
            $originalIframe = $matches[0];

            // 替换src属性
            $newIframe = preg_replace('/src\s*=\s*["\']\/\/player\.bilibili\.com\/player\.html([^"\']*)["\']/', 'src="' . $newSrc . '"', $originalIframe);

            // 添加或替换width和height属性
            if (preg_match('/width\s*=\s*["\'][^"\']*["\']/', $newIframe)) {
                $newIframe = preg_replace('/width\s*=\s*["\'][^"\']*["\']/', 'width="' . $width . '"', $newIframe);
            } else {
                $newIframe = preg_replace('/<iframe/', '<iframe width="' . $width . '"', $newIframe);
            }

            if (preg_match('/height\s*=\s*["\'][^"\']*["\']/', $newIframe)) {
                $newIframe = preg_replace('/height\s*=\s*["\'][^"\']*["\']/', 'height="' . $height . '"', $newIframe);
            } else {
                $newIframe = preg_replace('/<iframe/', '<iframe height="' . $height . '"', $newIframe);
            }

            return $newIframe;
        }, $content);

        return $content;
    }
}