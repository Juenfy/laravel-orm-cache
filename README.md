### <center>基于Laravel Eloquent ORM的缓存CURD</center>

```shell
composer require juenfy/laravel-orm-cache
```

用法
1.建个模型AdmUser.php继承OrmCacheModel模型
```php
<?php

namespace App\Models;

class AdmUser extends OrmCacheModel
{
    protected $table = 'adm_user';
    //开启CURD缓存
    protected static $cacheSwitch = true;
    //指定缓存key
    protected static $cacheKey = 'au:{$id}';
    //指定有序集合缓存key
    protected static $relationCacheKey = 'aur';
    //指定排序字段
    protected static $relationCacheSortFields = ['id','buy', 'view'];
    /**
     * @param $created
     * @return void
     * ORM设置缓存回调处理
     */
    protected static function createdCacheCallBack($created, $cacheRes){
        var_dump($cacheRes);
    }

    /**
     * @param $updated
     * @return void
     * ORM更新缓存回调处理
     */
    protected static function updatedCacheCallBack($updated, $cacheRes){
        var_dump($cacheRes);
    }

    /**
     * @param $deleted
     * @return void
     * ORM删除缓存回调处理
     */
    protected static function deletedCacheCallBack($deleted, $cacheRes){
        var_dump($cacheRes);
    }
}
```

2.建个控制器IndexController.php 分开执行cud、查看缓存更新情况！
```php
<?php

namespace App\Http\Controllers\Web;
use App\Models\AdmUser;
use Laravel\Lumen\Routing\Controller;
class IndexController extends Controller
{
    public function index()
    {
		//模拟插入数据
        for ($i = 0; $i < 30; $i++) {
            (AdmUser::query()->create([
                'name'=>uniqid(),
                'age'=>mt_rand(18,50),
                'buy'=>mt_rand(0,3000),
                'view'=>mt_rand(0,30000),
                'created_at'=>time()
            ]));
        }

        //查询缓存数据
        //设置返回字段
//        AdmUser::setGetFields(['age']);
        //查询详情
        $info = AdmUser::getInfo(1);
        var_dump($info);
        //查询列表 开启分页
        AdmUser::setPaging(true);
        //设置当前页码
        AdmUser::setPage(1);
        //设置每页记录数
        AdmUser::setPageSize(5);
        //设置排序字段
        AdmUser::setSortField('view');
        //设置排序方式
        AdmUser::setSortType('asc');
        $list = AdmUser::getList();
        var_dump($list);

        //更新数据
        $au = AdmUser::find(1);
        $au->name = 123456;
        $au->age = 11;
        $au->buy = 22;
        $au->created_at = time();
        $au->save();

        //删除数据
        AdmUser::destroy([1]);
    }
}

```

3.这是我本地跑的一些数据

![基于Eloquent ORM事件的缓存实时更新设计](https://cdn.learnku.com/uploads/images/202306/13/82399/lDaox5eM8i.png!large)
