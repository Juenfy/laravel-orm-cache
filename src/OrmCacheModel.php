<?php

namespace Juenfy\LaravelOrmCache;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Nette\Utils\Reflection;
use Juenfy\LaravelOrmCache\Cache\Base;
use Juenfy\LaravelOrmCache\Cache\Query;
use Juenfy\LaravelOrmCache\Cache\Relation;

class OrmCacheModel extends Model
{
    /**
     * @var null
     * 缓存实例
     */
    public static $cache = null;

    public static $cacheSwitch = false;

    use Base, Relation, Query;

    protected static function booted()
    {
        parent::booted(); // TODO: Change the autogenerated stub

        if (static::$cacheSwitch) {
            $calledClass = get_called_class();
            static::saved(function ($saved) use ($calledClass) {

                $original = $saved->original;
                if (empty($original)) {
                    //新增缓存
                    $createdBaseRes = static::createdBase($saved);
                    $createdRelationRes = true;
                    if (static::$relationCacheKey) {
                        $createdRelationRes = static::createdRelation($saved);
                    }
                    //更新缓存后的回调处理
                    if (method_exists($calledClass, 'createdCacheCallBack')) {
                        static::createdCacheCallBack($saved, $createdBaseRes && $createdRelationRes);
                    }
                } else {
                    //更新缓存
                    $updatedBaseRes = static::updatedBase($saved);
                    $updatedRelationRes = true;
                    if (static::$relationCacheKey) {
                        $updatedRelationRes = static::updatedRelation($saved);
                    }
                    if (method_exists($calledClass, 'updatedCacheCallBack')) {
                        static::updatedCacheCallBack($saved, $updatedBaseRes && $updatedRelationRes);
                    }
                }
            });

            static::deleted(function ($deleted) use ($calledClass) {
                //删除缓存
                $deletedBaseRes = static::deletedBase($deleted);
                $deletedRelationRes = true;
                if (static::$relationCacheKey) {
                    $deletedRelationRes = static::deletedRelation($deleted);
                }
                if (method_exists($calledClass, 'deletedCacheCallBack')) {
                    static::deletedCacheCallBack($deleted, $deletedBaseRes && $deletedRelationRes);
                }
            });
        }
    }

    /**
     * @param array $data
     * @param string $cacheKey
     * @return string
     * @throws \Exception
     * 获取缓存key
     */
    public static function getCacheKey(array $data, string $cacheKey, bool $rewriteData = false): string
    {
        $pattern = '/{\$(.*?)}/';
        preg_match_all($pattern, $cacheKey, $matches);
        $variables = $matches[1];
        $find = $replace = [];
        if ($rewriteData) {
            foreach ($variables as $vk => $vv) {
                if (isset($data[$vk])) {
                    $data[$vv] = $data[$vk];
                }
            }
        }
        foreach ($variables as $variable) {
            if (!isset($data[$variable])) {
                throw new \Exception("获取缓存key失败：缺失{\${$variable}}");
            }
            $find[] = "{\$$variable}";
            $replace[] = $data[$variable];
        }
        return $find && $replace ? str_replace($find, $replace, $cacheKey) : $cacheKey;
    }

    /**
     * @param array $data
     * @return void
     * 过滤缓存字段
     */
    public static function filterCacheDataField(array &$data)
    {
        if (static::$cacheFields) {
            foreach ($data as $k => $v) {
                if (!in_array($k, static::$cacheFields)) {
                    unset($data[$k]);
                }
            }
        }
    }

    /**
     * @param bool $paging
     * @return void
     * 开启分页
     */
    public function setPaging(bool $paging)
    {
        static::$paging = $paging;
        return $this;
    }

    /**
     * @int $pageSize
     * @return void
     * 设置每页记录数
     */
    public function setPageSize(int $pageSize)
    {
        static::$pageSize = $pageSize;
        return $this;
    }

    /**
     * @int $pageSize
     * @return void
     * 设置当前页码
     */
    public function setPage(int $page)
    {
        static::$page = $page;
        return $this;
    }

    /**
     * @param array $fields
     * @return void
     * 设置获取字段
     */
    public function setGetFields(array $fields)
    {
        static::$getFields = $fields;
        return $this;
    }

    /**
     * @param string $field
     * @return void
     * 设置排序字段
     */
    public function setSortField(string $field)
    {
        static::$sortField = $field;
        return $this;
    }

    /**
     * @param string $type
     * @return void
     * 设置排序顺序
     */
    public function setSortType(string $type)
    {
        static::$sortType = $type;
        return $this;
    }

    /**
     * @return null
     * 初始化缓存实例
     */
    public function initCache()
    {
        if (static::$cache == null) {
            static::$cache = Cache::store('redis')->getRedis();
        }
        return static::$cache;
    }

    public function doNotCache()
    {
        static::$cacheSwitch = false;
        return $this;
    }
}
