# 介绍
boa是一款免费开源、灵活易用的配置式PHP框架，MVC设计模式，完全面向对象，易学易用、便于快速开发。

* 可以对class细粒度配置，免初始化调用
* 基于配置的单例到分布式，免改动程序
* 多层次可覆盖的配置，满足灵活性需求

# 安装
* 命令行下切换至www目录执行：php boa -b
* 或者浏览器中直接访问：http://localhost

# 帮助
* 官网：http://boasoft.top
* 文档：http://boasoft.top/doc/

# 示例
## 示例控制器
```php
namespace mod\doc\controller;

use boa\boa;
use boa\msg;
use boa\controller;

class index extends controller{
	public function __construct(){
		parent::__construct();
	}

	public function index(){
		$this->view->assign('title', 'boa框架开发文档'); //模板赋值
		$this->view->html(); //从模板www/tpl/doc/index/index.html输出html
	}

	public function menu(){
		$model = boa::model('doc.content'); //访问doc模块中content模型
		$data = $model->list_content($this->cid); //从模型获取数据
		// GET/POST/COOKIE数据中的cid可以直接用$this->cid访问，配置验证规则后会自动验证
		$this->view->json($data); //输出json数据
	}
}
```
## 示例验证规则
如欲对示例控制器中cid进行验证，可以配置mod/doc/variable/index/menu.php
```php
return [
	'cid' => [
		'label' => '栏目ID', //标签名
		'check' => 'required', //检查规则：必需
		'filter' => 'intval' //过滤规则：转为整型
	]
]
```
## 类的访问
boa命名空间下的类可以直接通过boa类访问
```php
//使用默认或静态配置（config.php中配置）
boa::cache()->get('language');

//动态配置一
boa::cache(['expire' => 86400])->get('language');

//动态配置二
$cache = boa::cache();
$cache->cfg('expire', 86400);
$cache->get('language');
```
## 控制器之间的访问
```php
//后端调用其他控制器方法并返回结果，可以带参数
$res = boa::call('news.content.show', ['id' => 1]);
```
## 访问模块中的库
```php
//访问admi模块中library/test.php
boa::lib('admin.test')->get();

//访问当前模块中library/test.php
boa::lib('test')->get();
```
## 数据库操作
```php
//访问database类，等同boa::database()
$db = boa::db();
$data = ['title'=>'Title', 'content'=>'Content'];

$res = $db->table('news')->insert($data); //插入
$res = $db->table('news')->where('id = ?', 1)->delete(); //删除
$res = $db->table('news')->where('id = ?', 1)->update($data); //更新

//联合查询
$arr = $db->table('news A')
->field('A.*, B.category AS cat, COUNT(C.*) total')
->join('category B', 'A.cid = B.id')
->join('tag C', 'A.id = C.pid')
->where('A.cid = ? AND A.status = ?', 9, 1)
->limit(50, 10)
->order('A.sort ASC, A.id DESC')
->select();

//单行查询
$arr = $db->table('news')->where('id = ?', 1)->find();
```
## 示例模板
示例控制器中的模板www/tpl/doc/index/index.html
```javascript
{inc inc.head} //包含inc/head.html

{$arr news.content.show 1} //获取模型数据，参数id=1

{list $arr $k $v} //循环输出$arr
	{$k} : {$v} <br>
{/list}

{date Y-m-d {time}} //调用date函数，支持嵌套time函数

{if {date Y-m-d} == 2020-01-01 or ($a > 1 && $b == 2)} //多条件判断
	...
{elseif {date Y-m-d} == 2020-02-02 || $c == 3}
	...
{else}
	...
{/if}

{@news.error.1001} //调用news模块语言包中error.php中1001标签
```
## 多语言
```php
//调用boa内核语言包中error.php中2标签，带替换参数
boa::lang('boa.error.2', 'home/controller/test.php');

//调用home模块语言包中news.php中title标签
boa::lang('home.news.title');
```
