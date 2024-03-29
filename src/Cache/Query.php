<?php

namespace Juenfy\LaravelOrmCache\Cache;

trait Query
{
    /**
     * @var bool
     * 开启分页
     */
    public static $paging = false;

    /**
     * @var int
     * 设置当前页码
     */
    public static $page = 1;

    /**
     * @var int
     * 设置每页记录数
     */
    public static $pageSize = 10;

    /**
     * @var array
     * 获取的字段 为空默认返回所有
     */
    public static $getFields = [];

    /**
     * @var string
     * 排序字段
     */
    public static $sortField = '';

    /**
     * @var string
     * 排序方式
     */
    public static $sortType = 'desc';

    /**
     * @param ...$args
     * @return array|mixed
     * @throws \Exception
     * 获取缓存详情 按缓存key的{$xxx}顺序传参即可
     */
    public function getInfo(...$args)
    {
        $cache = static::initCache();
        $cacheKey = static::getCacheKey($args, static::$cacheKey, true);
        if (static::$cacheDataStructure == 'string') {
            $info = json_decode($cache->get($cacheKey), true) ?? [];
        } else {
            $info = static::$getFields ? $cache->hmget($cacheKey, static::$getFields) : $cache->hgetAll($cacheKey);
        }
        return $info;
    }

    /**
     * @param ...$args
     * @return array
     * @throws \Exception
     * 获取缓存列表 按关联缓存key的{$xxx}顺序传参即可
     */
    public function getList(...$args)
    {
        $cahce = static::initCache();
        $relationCacheKey = static::getCacheKey($args, static::$relationCacheKey, true);
        $list = [];
        if (static::$relationCacheDataStructure == 'zset') {
            $key = static::$sortField ? static::$sortField . '_' . $relationCacheKey : static::$relationCacheSortFields[0] . '_' . $relationCacheKey;
            $start = 0;
            $end = -1;
            if (static::$paging) {
                //开启分页
                $start = (static::$page - 1) * static::$pageSize;
                $end = $start + static::$pageSize - 1;
            }
            $elements = strtolower(static::$sortType) == 'asc' ? $cahce->zRange($key, $start, $end) : $cahce->zRevRange($key, $start, $end);
        } else {
            $elements = [];
            if (static::$paging) {
                //开启分页
                $start = (static::$page - 1) * static::$pageSize;
                $end = $start + static::$pageSize - 1;
                // 使用 SSCAN 迭代获取指定范围内的元素
                $cursor = '0'; // 初始游标值

                do {
                    $result = $cahce->sScan($relationCacheKey, $cursor, 'MATCH', '*', 'COUNT', static::$pageSize);
                    $cursor = $result[0]; // 获取下一次迭代的游标值
                    $elements = array_merge($elements, $result[1]); // 将获取到的元素合并到结果数组中
                } while ($cursor !== '0' && count($elements) < $end); // 继续迭代直到游标为 '0' 或达到结束索引
                // 截取指定范围内的元素
                $elements = array_slice($elements, $start, static::$pageSize);
            } else {
                $elements = $cahce->sMembers($relationCacheKey);
            }
        }
        foreach ($elements as $member) {
            $list[] = self::getInfo($member);
        }

        return $list;
    }
}