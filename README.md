# GravatarShortcode-Typecho-Plugin  
一个用于在文章或独立页面内显示Gravatar头像的短代码插件. 
***
# 安装  
下载`GravatarShortcode`文件夹到`/usr/plugins/`目录下  
登陆到后台启用插件，插件设置页可以设置头像的默认尺寸大小  
***  
# 使用方法  
* #### 参数列表
```sh
email //从该邮箱获取对应的gravatar头像，格式为example@example.com，留空时将调用插件目录下的默认头像
size //设置显示的尺寸，值为整数，留空时使用插件后台设置的默认尺寸
round  //设置是否将图片显示为圆形，true为是，不设置或其他值为正方形，注意true的大小写
```  
* #### 示例  
1.`[gravatar]` 
2.`[gravatar size="200" round="true"]`
3.`[gravatar email="example@example.com"]`  
4.`[gravatar email="example@example.com" size="80" round="true"]`  
***
