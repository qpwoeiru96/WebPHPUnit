## 说明

WebPHPUnit 是一套在线的可视化PHP单元测试系统，其基于 [VisualPHPUnit](https://github.com/NSinopoli/VisualPHPUnit "VisualPHPUnit") 二次改进而成，去除了数据库统计功能，同时修改整个库为phar格式，无需pear支持，自带phpUnit.phar。达到依赖性最小以及方便的可移动性。

## 需求

php &gt; 5.3 AND phpUnit &gt; 3.5

## 使用说明

基本上你只需拖动build里面的东西到一个Web根目录就可以执行了，但是为了了解清楚，我还是说一下各个文件以及文件夹的作用：

1.  `phpunit.phar` [phpUnit的压缩包 版本为 3.7.27 你可以从官网上下载最新版本并替换]
2.  `WebPHPUnit.phar` [WebPHPUnit的项目压缩包 包含了php、js、css等等多种文件，如果你只是使用那么无需关心]
3.  `index.php` [主入口文件 你可以将其放在任何地方]
4.  `web.config` [如果你是IIS 7+的PHP环境 你需要这个重写文件]
5.  `.htaccess` [如果你是Apache的环境 那么你需要这个重写文件]
6.  `cache` [缓存文件夹 在配置中可通过 <var>cache_directory</var> 进行配置]
7.  `test` [测试文件存放文件夹 在配置中可通过 <var>test_directories</var> 进行配置]
8.  `snap` [测试结果快照文件夹 在配置中可通过 <var>snapshot_directory</var> 进行配置]
9.  `config` [配置文件夹 存放 <var>phpunit.xml</var> 跟一些项目测试所需的 <var>bootstrap.php</var> ]

## 备注

1.  因为项目的特殊性，WebPHPUnit 必须放在webroot的根目录下，二级目录会工作不正常。
2.  如果是nginx的重写配置，那么你只需在 `location /` 里面轻轻松松的添加一条 `try_files $uri /index.php;`

## 截图

[![WebPHPUnit-Home.png](http://sou.la/blog/usr/uploads/2013/10/4254909678.png)](http://sou.la/blog/attachment/189/ "WebPHPUnit-Home.png")

[![WebPHPUnit-Archive.png](http://sou.la/blog/usr/uploads/2013/10/2773301611.png)](http://sou.la/blog/attachment/190/ "WebPHPUnit-Archive.png")