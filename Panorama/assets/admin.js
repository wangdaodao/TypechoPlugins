/**
 * 全景图插件编辑器扩展
 */

$(function () {
  // 等待编辑器加载完成
  setTimeout(function() {
    if ($('#wmd-button-row').length > 0) {
      // 添加全景图按钮到工具栏
      $('#wmd-button-row').append(
        '<li class="wmd-spacer wmd-spacer1"></li><li class="wmd-button" id="panorama-add" title="转换为全景图">📷</li>'
      );

      // 绑定点击事件
      $('#panorama-add').click(function () {
        panoramaShowModal();
      });
    }

    // 初始化模态框事件绑定
    panoramaInitModalEvents();
  }, 100);
});

/**
 * 初始化模态框事件
 */
function panoramaInitModalEvents() {
  // 确认转换按钮
  $('#panoramaConfirm').off('click').on('click', function() {
    panoramaConvertToShortcode();
  });

  // 取消按钮
  $('#panoramaCancel').off('click').on('click', function() {
    panoramaHideModal();
  });

  // 遮罩层点击
  $('#panoramaModalOverlay').off('click').on('click', function() {
    panoramaHideModal();
  });

  // 类型选择变化事件
  $('#panoramaType').off('change').on('change', function() {
    const type = $(this).val();
    if (type === 'cubemap') {
      $('#panoramaSrcGroup').hide();
      $('#panoramaCubeMapGroup').show();
    } else {
      $('#panoramaSrcGroup').show();
      $('#panoramaCubeMapGroup').hide();
    }
  });
}

/**
 * 显示模态框
 */
function panoramaShowModal() {
  // 获取选中的文本
  const selectedText = panoramaGetSelectedText();

  // 尝试解析Markdown图片
  const imageInfo = panoramaParseMarkdownImage(selectedText);

  // 重置表单
  $('#panoramaType').val('equirectangular');
  $('#panoramaSrc').val('');
  $('#panoramaAlt').val('');
  $('#panoramaWidth').val('');
  $('#panoramaHeight').val('');
  $('#panoramaCompass').val('true');
  $('#panoramaAutoLoad').val('true');
  $('#panoramaAutoRotate').val('0');

  // 清空cubemap输入框
  for (let i = 0; i < 6; i++) {
    $('#panoramaCubeMap' + i).val('');
  }

  // 默认显示equirectangular输入框
  $('#panoramaSrcGroup').show();
  $('#panoramaCubeMapGroup').hide();

  if (imageInfo) {
    // 检查是否是多个图片
    const imageUrls = imageInfo.src.split(',').map(url => url.trim());

    if (imageUrls.length > 1) {
      // 如果有多个图片，自动使用cubemap类型
      $('#panoramaType').val('cubemap');
      $('#panoramaSrcGroup').hide();
      $('#panoramaCubeMapGroup').show();

      // 按顺序填充6个输入框
      for (let i = 0; i < 6; i++) {
        if (i < imageUrls.length) {
          $('#panoramaCubeMap' + i).val(imageUrls[i]);
        } else {
          $('#panoramaCubeMap' + i).val('');
        }
      }
    } else {
      // 如果只有1个图片，使用equirectangular类型
      $('#panoramaSrc').val(imageUrls[0]);
    }

    $('#panoramaAlt').val(imageInfo.alt);
  }

  $('#panoramaModal').show();
  $('#panoramaModalOverlay').show();

  // 根据类型聚焦到相应的输入框
  if ($('#panoramaType').val() === 'cubemap') {
    $('#panoramaCubeMap0').focus();
  } else {
    $('#panoramaSrc').focus();
  }

  // 重新绑定事件，确保新添加的按钮有效
  panoramaInitModalEvents();
  return true;
}

/**
 * 隐藏模态框
 */
function panoramaHideModal() {
  $('#panoramaModal').hide();
  $('#panoramaModalOverlay').hide();
}

/**
 * 获取选中的文本
 */
function panoramaGetSelectedText() {
  const myField = document.getElementById('text');
  if (!myField) {
    return '';
  }

  if (document.selection) {
    // IE浏览器
    myField.focus();
    return document.selection.createRange().text;
  } else if (myField.selectionStart || myField.selectionStart === 0) {
    // 现代浏览器
    const startPos = myField.selectionStart;
    const endPos = myField.selectionEnd;
    return myField.value.substring(startPos, endPos);
  } else {
    return '';
  }
}

/**
 * 解析Markdown图片
 */
function panoramaParseMarkdownImage(text) {
  // 尝试匹配直接链接格式：![alt](url)
  const directMatch = text.match(/^!\[([^\]]*)\]\(([^)]+)\)$/);
  if (directMatch) {
    return {
      alt: directMatch[1],
      src: directMatch[2]
    };
  }

  // 尝试匹配引用链接格式：![alt][ref]
  const refMatch = text.match(/^!\[([^\]]*)\]\[(\d+)\]$/);
  if (refMatch) {
    const refId = refMatch[2];
    // 在全文中查找引用定义
    const refDefMatch = new RegExp('\\[' + refId + '\\]:\\s*(.+)$', 'm').exec($('#text').val());
    if (refDefMatch) {
      return {
        alt: refMatch[1],
        src: refDefMatch[1].trim()
      };
    }
  }

  // 尝试匹配多张图片的引用链接格式
  // 检查是否包含多张图片引用链接，如 ![alt][2] ![alt][3] 等
  const multiImageMatch = text.match(/!\[[^\]]*\]\[(\d+)\]/g);
  if (multiImageMatch && multiImageMatch.length > 1) {
    const imageUrls = [];
    let altText = '';
    
    // 提取所有引用ID
    for (let i = 0; i < multiImageMatch.length; i++) {
      const match = multiImageMatch[i].match(/!\[[^\]]*\]\[(\d+)\]/);
      if (match) {
        const refId = match[1];
        // 在全文中查找引用定义
        const refDefMatch = new RegExp('\\[' + refId + '\\]:\\s*(.+)$', 'm').exec($('#text').val());
        if (refDefMatch) {
          imageUrls.push(refDefMatch[1].trim());
          // 使用第一张图片的alt文本
          if (i === 0) {
            const altMatch = multiImageMatch[i].match(/!\[([^\]]*)\]/);
            if (altMatch) {
              altText = altMatch[1];
            }
          }
        }
      }
    }
    
    if (imageUrls.length > 0) {
      return {
        alt: altText,
        src: imageUrls.join(',')
      };
    }
  }

  return null;
}

/**
 * 转换为全景图短代码
 */
function panoramaConvertToShortcode() {
  const type = $('#panoramaType').val();
  const alt = $('#panoramaAlt').val().trim();
  const width = $('#panoramaWidth').val().trim();
  const height = $('#panoramaHeight').val().trim();
  const compass = $('#panoramaCompass').val();
  const autoLoad = $('#panoramaAutoLoad').val();
  const autoRotate = $('#panoramaAutoRotate').val();

  // 生成短代码
  let shortcode = '[panorama';

  if (type === 'cubemap') {
    // 处理cubemap类型
    let cubeMapImages = [];
    for (let i = 0; i < 6; i++) {
      let imageUrl = $('#panoramaCubeMap' + i).val().trim();
      if (imageUrl) {
        // 移除URL中的协议部分（http:或https:）
        imageUrl = imageUrl.replace(/^https?:/, '');
        cubeMapImages.push(imageUrl);
      } else {
        // 如果有任何一个图片为空，提醒用户
        alert('请填写所有立方体贴图图片URL！');
        return false;
      }
    }

    shortcode += ' src="' + cubeMapImages.join(',') + '"';
    shortcode += ' type="cubemap"';
  } else {
    // 处理equirectangular类型
    let src = $('#panoramaSrc').val().trim();
    if (!src) {
      alert('请输入有效的图片URL！');
      return false;
    }

    // 移除URL中的协议部分（http:或https:）
    src = src.replace(/^https?:/, '');

    shortcode += ' src="' + src + '"';
    shortcode += ' type="equirectangular"';
  }

  if (alt) {
    shortcode += ' alt="' + alt + '"';
  }

  if (width) {
    shortcode += ' width="' + width + '"';
  }

  if (height) {
    shortcode += ' height="' + height + '"';
  }

  // 添加compass参数（总是添加，包括true和false）
  shortcode += ' compass="' + compass + '"';

  // 添加autoLoad参数（总是添加，包括true和false）
  shortcode += ' autoload="' + autoLoad + '"';

  // 添加autoRotate参数（所有值都添加，包括0）
  shortcode += ' autorotate="' + autoRotate + '"';

  shortcode += ']';

  // 替换选中的文本
  panoramaReplaceSelectedText(shortcode);
  panoramaHideModal();
  return true;
}

/**
 * 替换选中的文本
 */
function panoramaReplaceSelectedText(text) {
  const myField = document.getElementById('text');
  if (!myField) {
    alert('无法找到编辑器！');
    return false;
  }

  if (document.selection) {
    // IE浏览器
    myField.focus();
    const sel = document.selection.createRange();
    sel.text = text;
    myField.focus();
  } else if (myField.selectionStart || myField.selectionStart === 0) {
    // 现代浏览器
    const startPos = myField.selectionStart;
    const endPos = myField.selectionEnd;
    const cursorPos = startPos;
    myField.value = myField.value.substring(0, startPos) + text + myField.value.substring(endPos, myField.value.length);
    myField.focus();
    myField.selectionStart = cursorPos + text.length;
    myField.selectionEnd = cursorPos + text.length;
  } else {
    // 备用方案
    myField.value += text;
    myField.focus();
  }
}

// ESC键关闭
$(document).on('keydown', function(e) {
  if (e.keyCode === 27) {
    panoramaHideModal();
  }
});

// 输入框回车键支持
$(document).on('keypress', '#panoramaAlt', function(e) {
  if (e.which === 13) {
    e.preventDefault();
    panoramaConvertToShortcode();
  }
});