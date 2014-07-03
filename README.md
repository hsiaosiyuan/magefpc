### 如何在 FPC 中去除指定的 module/controller/action

在 index.php 中找到变量 $Hln35_FPC_NO_CACHE_FILTERS，会注意到它是一个数组。在数组中添加 m/c/a 和它们对应的 value 组成的 assoc array 来
去除指定的 module/controller/action

```php
#no cache filters
$Hln35_FPC_NO_CACHE_FILTERS = array(
	array(
		'm' => '',
		'c' => '',
		'a' => ''
	),
);
```

比如现在需要将如下 URL 从 FPC 中去除

	http://example.com/index.php/customer/account/login

如果熟悉 magento 等 mvc 结构的，应该会知道，上面的 URL 实际上是访问的 customer module 下的 account controller 中的 login action 方法
即：

 MVC            |       Corresponding 
:---------------|---------------------:
 module         |       customer    
 controller     |       account 
 action         |       login   


而开头的 m 就是 module，c 就是 controller，a 就是 action
所以

```php
#no cache filters
$Hln35_FPC_NO_CACHE_FILTERS = array(
	array(
		'm' => 'customer',
		'c' => 'account',
		'a' => 'login'
	),
);
```

就可以完成将
	
	http://example.com/index.php/customer/account/login

从 FPC 中去除的功能


### 灵活的操作

```php
#no cache filters
// 表示 customer/account/* 都是不走 FPC 的，比如
// http://example.com/index.php/customer/account/login
// http://example.com/index.php/customer/account/logout
// http://example.com/index.php/customer/account/register
// an so on
$Hln35_FPC_NO_CACHE_FILTERS = array(
	array(
		'm' => 'customer',
		'c' => 'account',
		'a' => '*'
	),
);
```

```php
#no cache filters
// 表示 customer/account|address/* 都是不走 FPC 的，比如
// http://example.com/index.php/customer/account/login
// http://example.com/index.php/customer/account/logout
// http://example.com/index.php/customer/account/register
// and so on
// http://example.com/index.php/customer/address/new
// http://example.com/index.php/customer/address/edit
// http://example.com/index.php/customer/address/view
// and so on
$Hln35_FPC_NO_CACHE_FILTERS = array(
	array(
		'm' => 'customer',
		'c' => 'account|address',
		'a' => '*'
	),
);
```

```php
#no cache filters
// 表示 customer/*/* 都是不走 FPC 的，比如
// http://example.com/index.php/customer/a/b
// http://example.com/index.php/customer/c/d
// http://example.com/index.php/customer/e/f
// and so on
$Hln35_FPC_NO_CACHE_FILTERS = array(
	array(
		'm' => 'customer',
		'c' => '*',
		'a' => '*'
	),
);
```