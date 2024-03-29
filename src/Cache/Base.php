<?php

namespace Juenfy\LaravelOrmCache\Cache;
trait Base
{
    /**
     * @var string 缓存key
     */
    public static $cacheKey = '';

    /**
     * @var array 缓存字段 空数组则缓存表所有字段
     */
    public static $cacheFields = [];

    /**
     * @var string 缓存的数据结构 默认哈希
     */
    public static $cacheDataStructure = 'hash';

    /**
     * @param $created
     * @return bool
     * @throws \Exception
     * ORM设置缓存数据
     */
    public static function createdBase ($created): bool
    {
        self::checkBaseCacheConf();
        $cache = static::initCache();
        $data = $created->attributes;
        $key = static::getCacheKey($data,static::$cacheKey);
        static::filterCacheDataField($data);
        $cacheMethod = 'hMset';
        if (static::$cacheDataStructure == 'string') {
            $cacheMethod = 'set';
            $data = json_encode($data,JSON_UNESCAPED_UNICODE);
        }
        return $cache->$cacheMethod($key,$data);
    }

    /**
     * @param $updated
     * @return bool
     * @throws \Exception
     * ORM更新缓存数据
     */
    public static function updatedBase ($updated): bool
    {
        self::checkBaseCacheConf();
        $cache = static::initCache();
        $data = $updated->attributes;
        $key = static::getCacheKey($data,static::$cacheKey);
        static::filterCacheDataField($data);
        $cacheMethod = 'hMset';
        if (static::$cacheDataStructure == 'string') {
            $cacheMethod = 'set';
            $oldData = json_decode($cache->get($key),true) ?: [];
            $data = array_merge($oldData,$data);
            $data = json_encode($data,JSON_UNESCAPED_UNICODE);
        }
        return $cache->$cacheMethod($key,$data);
    }

    /**
     * @param $deleted
     * @return bool
     * ORM删除缓存数据
     */
    public static function deletedBase ($deleted): bool
    {
        self::checkBaseCacheConf();
        $cache = static::initCache();
        $data = $deleted->attributes;
        $key = static::getCacheKey($data,static::$cacheKey);
        return $cache->del($key);
    }

    /**
     * @return bool
     * @throws \Exception
     * 校验参数配置
     */
    private static function checkBaseCacheConf(): bool
    {
        if (empty(static::$cacheKey)) {
            throw new \Exception('缓存错误：缓存key必填');
        }

        if (!in_array(static::$cacheDataStructure, ['hash','string'])) {
            throw new \Exception('缓存的数据结构错误：暂时只支持string和hash');
        }
        return true;
    }
}