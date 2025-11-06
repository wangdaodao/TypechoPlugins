<?php
/**
 * 基于 Panorama 的全景图插件，在文章中嵌入全景图
 *
 * @package Panorama
 * @author 王叨叨
 * @version 1.0.0
 * @link https://wangdaodao.com
 */

!defined('__TYPECHO_ROOT_DIR__') && exit();

class Panorama_Plugin implements Typecho_Plugin_Interface
{
  /**
   * 激活插件方法,如果激活失败,直接抛出异常
   */
  public static function activate()
  {
    Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('Panorama_Plugin', 'parse');
    Typecho_Plugin::factory('Widget_Archive')->header = array('Panorama_Plugin', 'header');
    Typecho_Plugin::factory('admin/write-post.php')->bottom = array('Panorama_Plugin', 'insertButton');
    Typecho_Plugin::factory('admin/write-page.php')->bottom = array('Panorama_Plugin', 'insertButton');
    return _t('插件启用成功');
  }

  /**
   * 禁用插件方法,如果禁用失败,直接抛出异常
   */
  public static function deactivate()
  {
    return _t('插件禁用成功');
  }

  /**
   * 获取插件配置面板
   */
  public static function config(Typecho_Widget_Helper_Form $form)
  {
    $loadScope = new Typecho_Widget_Helper_Form_Element_Select(
      'loadScope',
      array(
        'global' => '全局加载（所有页面）',
        'single' => '仅正文页面（文章和独立页面）'
      ),
      'global',
      _t('资源加载范围'),
      _t('选择在哪些页面加载全景图所需的CSS和JavaScript资源')
    );
    $form->addInput($loadScope);

    $width = new Typecho_Widget_Helper_Form_Element_Text(
      'width',
      null,
      '100%',
      _t('全景图宽度'),
      _t('可以使用百分比或具体像素值，例如: 100% 或 800px')
    );
    $form->addInput($width);

    $height = new Typecho_Widget_Helper_Form_Element_Text(
      'height',
      null,
      '400px',
      _t('全景图高度'),
      _t('建议使用像素值，例如: 400px')
    );
    $form->addInput($height);

    $cssUrl = new Typecho_Widget_Helper_Form_Element_Text(
      'cssUrl',
      null,
      'https://cdnjs.cloudflare.com/ajax/libs/pannellum/2.5.6/pannellum.css',
      _t('CSS CDN地址'),
      _t('Pannellum CSS文件的CDN地址')
    );
    $form->addInput($cssUrl);

    $jsUrl = new Typecho_Widget_Helper_Form_Element_Text(
      'jsUrl',
      null,
      'https://cdnjs.cloudflare.com/ajax/libs/pannellum/2.5.6/pannellum.js',
      _t('JavaScript CDN地址'),
      _t('Pannellum JavaScript文件的CDN地址')
    );
    $form->addInput($jsUrl);
  }

  /**
   * 个人用户的配置面板
   */
  public static function personalConfig(Typecho_Widget_Helper_Form $form){}

  /**
   * 添加头部资源
   */
  public static function header()
  {
    $options = Helper::options()->plugin('Panorama');
    $loadScope = isset($options->loadScope) ? $options->loadScope : 'global';

    // 根据配置的加载范围决定是否加载资源
    $shouldLoad = false;

    switch ($loadScope) {
      case 'global':
        $shouldLoad = true;
        break;
      case 'single':
        $shouldLoad = (Typecho_Widget::widget('Widget_Archive')->is('single') ||
                      Typecho_Widget::widget('Widget_Archive')->is('post') ||
                      Typecho_Widget::widget('Widget_Archive')->is('page'));
        break;
    }

    // 只有在需要加载的情况下才输出资源
    if ($shouldLoad) {
      $cssUrl = isset($options->cssUrl) ? $options->cssUrl : 'https://cdnjs.cloudflare.com/ajax/libs/pannellum/2.5.6/pannellum.css';
      $jsUrl = isset($options->jsUrl) ? $options->jsUrl : 'https://cdnjs.cloudflare.com/ajax/libs/pannellum/2.5.6/pannellum.js';
      $pluginUrl = Helper::options()->pluginUrl . '/Panorama/assets/panorama.css';

      echo '<link rel="stylesheet" href="' . $cssUrl . '" />';
      echo '<link rel="stylesheet" href="' . $pluginUrl . '" />';
      echo '<script type="text/javascript" src="' . $jsUrl . '"></script>';
    }
  }

  /**
   * 解析内容
   */
  public static function parse($content, $widget, $lastResult)
  {
    $content = empty($lastResult) ? $content : $lastResult;

    // 匹配全景图短代码
    if (preg_match_all('/\[panorama\s+([^\]]*)\]/i', $content, $matches)) {
      $options = Helper::options()->plugin('Panorama');
      $defaultWidth = isset($options->width) ? $options->width : '100%';
      $defaultHeight = isset($options->height) ? $options->height : '400px';

      foreach ($matches[0] as $key => $match) {
        $params = array();

        // 解析所有参数
        if (preg_match_all('/(\w+)=["\']([^"\']*)["\']/', $matches[1][$key], $paramMatches, PREG_SET_ORDER)) {
          foreach ($paramMatches as $paramMatch) {
            $params[$paramMatch[1]] = $paramMatch[2];
          }
        }

        // 获取参数值，使用默认值
        $src = isset($params['src']) ? $params['src'] : '';
        $alt = isset($params['alt']) ? $params['alt'] : '';
        $width = isset($params['width']) ? $params['width'] : $defaultWidth;
        $height = isset($params['height']) ? $params['height'] : $defaultHeight;
        $autoLoad = isset($params['autoload']) ? $params['autoload'] : 'true';
        $autoRotate = isset($params['autorotate']) ? $params['autorotate'] : '0';
        $compass = isset($params['compass']) ? $params['compass'] : 'true';
        $type = isset($params['type']) ? $params['type'] : 'equirectangular';

        // 如果没有src参数，跳过
        if (empty($src)) {
          continue;
        }

        $panoramaId = 'panorama-' . uniqid();

        $replacement = '<div class="panorama-container">';
        $replacement .= '<div id="' . $panoramaId . '" class="panorama-viewer" style="width:' . $width . '; height:' . $height . ';"></div>';
        $replacement .= '</div>';

        $replacement .= '<script>
document.addEventListener("DOMContentLoaded", function() {
  pannellum.viewer("' . $panoramaId . '", {';

        if ($type === 'cubemap') {
          // 处理cubemap类型，将逗号分隔的图片URL转换为数组
          $cubeMapImages = explode(',', $src);
          $replacement .= '
    "type": "cubemap",
    "cubeMap": [';

          for ($i = 0; $i < count($cubeMapImages); $i++) {
            $imageUrl = trim($cubeMapImages[$i]);
            $replacement .= '
      "' . $imageUrl . '"' . ($i < count($cubeMapImages) - 1 ? ',' : '');
          }

          $replacement .= '
    ]';
        } else {
          // 默认equirectangular类型
          $replacement .= '
    "type": "equirectangular",
    "panorama": "' . $src . '"';
        }

        $replacement .= ',
    "autoLoad": ' . ($autoLoad === 'true' ? 'true' : 'false') . ',
    "autoRotate": ' . $autoRotate . ',
    "compass": ' . ($compass === 'true' ? 'true' : 'false') . ',
    "northOffset": 0,
    "title": "' . $alt . '"
  });
});
</script>';

        $content = str_replace($match, $replacement, $content);
      }
    }

    return $content;
  }

  /**
   * 在编辑器页面插入资源
   */
  public static function insertButton()
  {
    $pluginUrl = Helper::options()->pluginUrl . '/Panorama/assets/admin.js';

    echo <<<HTML
<!-- 全景图转换模态框 -->
<div id="panoramaModal" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); background:#fff; padding:20px; border:1px solid #ccc; border-radius:8px; z-index:10000; box-shadow:0 4px 20px rgba(0,0,0,0.15); width: 400px;">
  <h3 style="margin:0 0 20px 0; color:#333; border-bottom:1px solid #eee; padding-bottom:10px;">转换为全景图</h3>
  <p>您可以选择Markdown图片代码自动填充，或手动输入图片URL。</p>
  <div class="panorama-form">
    <div class="form-group">
      <label for="panoramaType" style="display:inline-block; width:85px;">类型：</label>
      <select id="panoramaType" style="border:1px solid #ddd; border-radius:4px; width:300px;">
        <option value="equirectangular" selected>等距圆柱投影 (equirectangular)</option>
        <option value="cubemap">立方体贴图 (cubemap)</option>
      </select>
    </div>
    <div class="form-group" id="panoramaSrcGroup">
      <label for="panoramaSrc" style="display:inline-block; width:85px;">图片URL：</label>
      <input type="text" id="panoramaSrc" placeholder="请输入图片URL，如：https://example.com/image.jpg" style="border:1px solid #ddd; border-radius:4px; width:300px;">
    </div>
    <div class="form-group" id="panoramaCubeMapGroup" style="display:none;">
      <label style="float: left;margin-top: 5px;">立方体贴图：</label>
      <div style="margin-left: 85px;">
        <span style="display:inline-block;">
          <label style="display:inline-block; width:60px;">+Z (前):</label>
          <input type="text" id="panoramaCubeMap0" placeholder="正面图片URL" style="border:1px solid #ddd; border-radius:4px; width:240px;">
        </span>
        <span style="display:inline-block;">
          <label style="display:inline-block; width:60px;">+X (右):</label>
          <input type="text" id="panoramaCubeMap1" placeholder="右侧图片URL" style="border:1px solid #ddd; border-radius:4px; width:240px;">
        </span>
        <span style="display:inline-block;">
          <label style="display:inline-block; width:60px;">-Z (后):</label>
          <input type="text" id="panoramaCubeMap2" placeholder="背面图片URL" style="border:1px solid #ddd; border-radius:4px; width:240px;">
        </span>
        <span style="display:inline-block;">
          <label style="display:inline-block; width:60px;">-X (左):</label>
          <input type="text" id="panoramaCubeMap3" placeholder="左侧图片URL" style="border:1px solid #ddd; border-radius:4px; width:240px;">
        </span>
        <span style="display:inline-block;">
          <label style="display:inline-block; width:60px;">+Y (上):</label>
          <input type="text" id="panoramaCubeMap4" placeholder="顶部图片URL" style="border:1px solid #ddd; border-radius:4px; width:240px;">
        </span>
        <span style="display:inline-block;">
          <label style="display:inline-block; width:60px;">-Y (下):</label>
          <input type="text" id="panoramaCubeMap5" placeholder="底部图片URL" style="border:1px solid #ddd; border-radius:4px; width:240px;">
        </span>
      </div>
    </div>
    <div class="form-group">
      <label for="panoramaAlt" style="display:inline-block; width:85px;">图片描述：</label>
      <input type="text" id="panoramaAlt" placeholder="图片描述" style="border:1px solid #ddd; border-radius:4px; width:300px;">
    </div>
    <div class="form-group">
      <label for="panoramaWidth" style="display:inline-block; width:85px;">宽度：</label>
      <input type="text" id="panoramaWidth" placeholder="如: 100% 或 800px" style="border:1px solid #ddd; border-radius:4px; width:300px;">
    </div>
    <div class="form-group">
      <label for="panoramaHeight" style="display:inline-block; width:85px;">高度：</label>
      <input type="text" id="panoramaHeight" placeholder="如: 400px" style="border:1px solid #ddd; border-radius:4px; width:300px;">
    </div>
    <div class="form-group">
      <label for="panoramaCompass" style="display:inline-block; width:85px;">指南针：</label>
      <select id="panoramaCompass" style="border:1px solid #ddd; border-radius:4px; width:300px;">
        <option value="true" selected>显示</option>
        <option value="false">隐藏</option>
      </select>
    </div>
    <div class="form-group">
      <label for="panoramaAutoLoad" style="display:inline-block; width:85px;">自动加载：</label>
      <select id="panoramaAutoLoad" style="border:1px solid #ddd; border-radius:4px; width:300px;">
        <option value="true" selected>是</option>
        <option value="false">否</option>
      </select>
    </div>
    <div class="form-group">
      <label for="panoramaAutoRotate" style="display:inline-block; width:85px;">自动旋转：</label>
      <select id="panoramaAutoRotate" style="border:1px solid #ddd; border-radius:4px; width:300px;">
        <option value="-2">逆时针慢速</option>
        <option value="-1">逆时针快速</option>
        <option value="0" selected>不旋转</option>
        <option value="1">顺时针快速</option>
        <option value="2">顺时针慢速</option>
      </select>
    </div>

    <div style="margin-top:20px; padding-top:15px; border-top:1px solid #eee; text-align:right;">
      <button id="panoramaConfirm" style="background:#467B96; color:white; border:none; padding:8px 16px; border-radius:4px; cursor:pointer; margin-left:8px;">确认转换</button>
      <button id="panoramaCancel" style="background:#f5f5f5; border:1px solid #ddd; padding:8px 16px; border-radius:4px; cursor:pointer; margin-left:8px;">取消</button>
    </div>
  </div>
</div>
<div id="panoramaModalOverlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999;"></div>

<script src="{$pluginUrl}"></script>
HTML;
  }
}