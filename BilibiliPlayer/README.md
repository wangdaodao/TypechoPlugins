# Bilibili播放器插件

这是一个Typecho插件，用于将Bilibili默认播放器替换为HTML5移动端播放器，并提供更多自定义选项。

## 功能特点

- 将Bilibili默认播放器替换为HTML5移动端播放器
- 提供丰富的播放器配置选项
- 支持自定义播放器尺寸和行为
- 仅在渲染时替换，不影响原始内容

## 安装方法

1. 将整个`BilibiliPlayer`文件夹上传到Typecho的`usr/plugins/`目录下
2. 登录Typecho后台，进入"控制台" -> "插件"
3. 找到"Bilibili播放器"插件，点击"启用"
4. 启用后，在插件设置中配置默认参数

## 配置选项

| 参数用途 | 参数名 | 默认值 | 使用方法 |
|----------|--------|--------|----------|
| 视频宽度 | width | 100% | 例如：100%, 800px |
| 视频高度 | height | 500px | 例如：500px, 400px |
| 是否自动播放 | autoplay | 0 | 1: 开启, 0: 关闭 |
| 默认弹幕开关 | danmaku | 1 | 1: 开启, 0: 关闭 |
| 是否默认静音 | muted | 0 | 1: 开启, 0: 关闭 |
| 一键静音按钮是否显示 | hasMuteButton | 0 | 1: 开启, 0: 关闭 |
| 视频封面下方信息显示 | hideCoverInfo | 0 | 1: 隐藏, 0: 显示 |
| 是否隐藏弹幕按钮 | hideDanmakuButton | 0 | 1: 隐藏, 0: 显示 |
| 是否隐藏全屏按钮 | noFullScreenButton | 0 | 1: 隐藏, 0: 显示 |
| 是否开始记忆播放 | fjw | 1 | 1: 开启, 0: 关闭 |

## 使用方法

1. 在文章中插入Bilibili视频（使用默认的嵌入代码）
2. 插件会自动检测并替换播放器
3. 播放器将使用您在插件设置中配置的默认参数

### 示例

原始嵌入代码：
```html
<iframe src="//player.bilibili.com/player.html?isOutside=true&aid=114256280356575&bvid=BV1YnZnYAENC&cid=29162276248&p=1" scrolling="no" border="0" frameborder="no" framespacing="0" allowfullscreen="true"></iframe>
```

插件处理后：
```html
<iframe src="//www.bilibili.com/blackboard/html5mobileplayer.html?isOutside=true&aid=114256280356575&bvid=BV1YnZnYAENC&cid=29162276248&p=1&autoplay=0&danmaku=1&muted=0&hasMuteButton=0&hideCoverInfo=0&hideDanmakuButton=0&noFullScreenButton=0&fjw=1" scrolling="no" border="0" frameborder="no" framespacing="0" allowfullscreen="true" width="100%" height="500px"></iframe>
```

## 注意事项

- 插件仅在渲染时替换播放器，不会修改原始文章内容
- 禁用插件后，视频将恢复到默认的Bilibili播放器
- 自动播放功能可能被浏览器阻止，这是浏览器的安全策略
- 某些参数可能在不同版本的Bilibili播放器中表现不同

## 版本历史

### v1.0.0
- 支持基本的播放器替换和配置选项


## 反馈与支持

如果您遇到任何问题或有改进建议，请通过以下方式联系我：

- [https://wangdaodao.com/](https://wangdaodao.com/)
- [hi@wangdaodao.com](hi@wangdaodao.com)