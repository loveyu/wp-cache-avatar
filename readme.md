## WordPress 添加缓存QQ头像和Gravatar头像，并自定义随机头像
### 文件说明
* avatar.php 头像缓存文件，指定在wordpress根目录，通过伪静态访问该文件生成文件后进行跳转
* wordpress.php 主题支持函数，需创建一个表，然后获取邮箱数据得到QQ邮箱地址
* rand_avatar.php 随机头像生成，你也可以修改为其他地址

### 详细配置
可能需要涉及到伪静态和头像替换等操作，详细请参考：[http://www.loveyu.org/3154.html](http://www.loveyu.org/3154.html)
