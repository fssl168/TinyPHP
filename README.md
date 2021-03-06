# TinyPHP

仿造thinkPHP开发的开源极简的PHP框架

## 安装环境
在本地开发环境是 ubuntu16.04+PHP7.0.18+MySQL5.7.19+nginx1.10.3

理论来说PHP>=5.3即可，因为框架有面向对象(还没有测试过）

数据库由PDO驱动，理论支持主流数据库

## 安装步骤
### 第一步
将源码下载
```
git clone git@github.com:woodyxiong/ss-panel.git
```

### 第二步

如果使用nginx,则将配置文件指向Web目录
```
server {
    listen	80;
    index index.php;
    server_name servername;
	root yourpath/TinyPHP/Web;
```

### 第三步

与thinkPHP类似，你可以自由在用户目录写业务代码

## 使用特性

### 渲染html页面

在业务层代码直接执行 `display()`函数
> 显示默认的视图模板 `Application/Home/View/Index/Index.html`

```
<?php
namespace Home\Controller;
use Tiny\Controller\viewController;
class IndexController extends viewController{
    public function index(){
        $this->assign("tiny","php");
        $this->display();
    }
}
```
> 指定视图模板 `Application/Home/View/Index/Index2.html`

```
public function index(){
    $this->display('index2');
}
```
> 模板赋值
由于没有使用任何模板引擎，所以在模板还是使用PHP代码进行输出

模板赋值变量 在`Application/Home/Controller/IndexController.class.php`
```
public function index(){
    $name='tinyPHP';
    $this->assign('name',$name);
    $this->display();
}
```
模板变量输出 在`Application/Home/View/Index/index.html`
```
<?php
echo $name;
```

### 使用数据库
在 `Application/Home/Conf/db.php` 可对数据库信息进行配置
```
'DB_TYPE'   => 'mysql', // 数据库类型
'DB_HOST'   => 'localhost', // 服务器地址
'DB_NAME'   => 'databasename', // 数据库名
'DB_USER'   => 'databaseuser', // 用户名
'DB_PWD'    => 'password', // 密码
'DB_PORT'   => 3306, // 端口
'DB_PREFIX' => 'prex', // 数据库表前缀
'DB_CHARSET'=> 'utf8', // 字符集
```

>数据库操作方法

在业务层代码先用 `M()` 对数据库连接实例化，然后用 `excute()` 执行sql语句

用 `getLastSql()` 可获得上次执行的sql语句
```
public function sql(){
    $data=M()->execute('select * from camera');//执行sql语句
    $sql=M()->getLastSql();//获取上次的sql语句
}
```

### cookie操作
在 `Application/Home/Conf/db.php` 可对cookie进行配置
```
'COOKIE_EXPIRE' => 3600,   // Cookie有效期
'COOKIE_PATH'   => '',     // Cookie有效域名
'COOKIE_DOMAIN' => '/',    // Cookie作用域
```

>cookie操作方法

```
$cookie=cookie();//获取全部cookie
cookie('tinyPHP','very good');//设置名为tinyPHP的cookie为very good
$cookie['tinyPHP']=cookie('tinyPHP');//返回名为tinyPHP的cookie
cookie('tinyPHP',null);//清除名为tinyPHP的cookie信息
```

### session操作
在 `Application/Home/Conf/session.php` 可对session进行配置
```
'SESSION_NAME' => 'tinySSID',//session在cookie的名称
'SESSION_SAVEPATH' => '/var/lib/php/sessions',//session的存储路径
```
> session操作方法

```
$session=session();//获取全部cookie
session('tinyPHP','very good');//设置名为tinyPHP的session为very good
$session['tinyPHP']=session('tinyPHP');//返回名为tinyPHP的session
session('tinyPHP',null);//清除名为tinyPHP的cookie信息
```

### api令牌操作
> 参数介绍

首先为了防止抓包等方式获取token等参数，请务必使用https进行传输。
本套令牌设计共需传入3个参数
- token 令牌
- timestamp 请求时间戳
- sign  令牌+时间戳的签名

> 原理说明

新建数据库索引表，将用户和令牌绑定，由于可能一个用户使用不同的终端，所以可能会一个用户对应多个token。

每次用户发起请求的时候同时将时间戳发出，如果服务器端判定客户端发送的数据和服务器端接收数据的时间较短，则返回超时错误，这样是为了防止一个请求被调用多次，也可以防止中间人拿到token之后重复发送。

为了防止中间人拿到token之后用自己现在的时间戳发送请求，所以才会有签名的功能，保证令牌和时间戳都是从客户端发出的。

> 操作说明

在 `Application/Home/Conf/token.php`进行配置
```
<?php
return array(
    'token_createsalt'  =>  'tinyPHPtoken', //创建api的盐
    'token_signsalt'  =>  'tinyPHPsign',    //签名函数的盐
    'token_expiretime'  =>  3600,           //借口过期时间
);
```
在 `Application/Home/Conf/api.php`进行配置，可以自由的添加项目开发时需要的错误代码
```
<?php
return array(
    '100'   =>  'missing parameter',    //缺少参数
    '101'   =>  'missing token',        //缺少token
    '102'   =>  'missing timestamp',    //缺少时间戳
    '103'   =>  'missing sign',         //缺少签名
    '104'   =>  'overtime',             //超时
    '105'   =>  'invalid sign',         //签名无效
    '106'   =>  'invalid token',        //无效token
    '200'   =>  'seccess',              //成功
);
```
业务层函数介绍

```
$token=$this->createToken($username);   //给用户创建token并且返回给$token
$user=$this->getuser();                 //直接获取token是哪个用户发来的，此函数在父类中是虚函数，必须在子类定义
$this->fail($code);                     //有错误，将错误码和错误信息返回给客户
$this->success($data);                  //一切准备就绪，给客户端发送数据
```

> 操作示例

用户登录成功并且给用户返回一个token

```
用户登录成功并且给用户返回一个token
public function auth(){
    // ...验证登录
    // 创建token
    $username='tinyPHP';
    $token=$this->createToken($username);
    // ...保存token到数据库或者缓存中
    // 将token发送给客户端
    $data['token']=$token;
    $this->success($data);
}
```

获取用户名，再次强调，此函数在父类中是虚函数，必须在业务层重新定义
```
protected function getuser(){
    $token=$_GET['token'];
    $this->checkToken();
    //一般为数据库或者缓存操作，在此处直接省略
    if($token=="7cfb01bdac5fdd88f57556e0b3302702")
        return "tinyPHP";
    else
        $this->fail("106");
}
```

验证成功，并且从服务器返回数据
```
public function example(){
    $user=$this->getuser();
    //  获取token与user的关系
    $username=$this->getuser($token);
    // 业务逻辑
    //  ...
    $data["username"]='tinyPHP';
    $this->success($data);
}
```

### 变量调试
> 操作方法

类似`var_dump()`方法，对浏览器进行友好输出
```
D($var);
```

> 原理介绍

用ob_start()将var_dump()要输出的保存下来，然后对输出增加html标签，使输出更美观

## 需要完成的事项
- [ ] json的实例化
- [ ] cookie里需要有session的加密序列
- [ ] 静态页面缓存
- [ ] php邮箱操作
- [ ] 日志记录操作
- [ ] 上传文件操作
