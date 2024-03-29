<?php

namespace Juenfy\LaravelOrmCache\Cache;

trait Relation
{
    /**
     * @var string 关联缓存key 为空则不开启关联缓存
     */
    public static $relationCacheKey = '';

    /**
     * @var string 关联缓存的数据结构 默认有序集合
     */
    public static $relationCacheDataStructure = 'zset';

    /**
     * @var array 关联缓存排序字段 数据结构为zset时必填
     */
    public static $relationCacheSortFields = [];

    /**
     * @param $created
     * @return bool
     * @throws \Exception
     * ORM设置缓存数据
     */
    public static function createdRelation($created): bool
    {
        $data = $created->attributes;
        $primaryKey = $created->primaryKey;
        if (!isset($data[$primaryKey])) {
            throw new \Exception('关联缓存错误：主键数据不能为空');
        }
        self::checkRelationCacheConf();
        $cache = static::initCache();
        $cacheKey = static::getCacheKey($data, static::$relationCacheKey);
        $keys = static::$relationCacheDataStructure == 'zset' ? self::getSortedKeys($cacheKey) : [];
        $res = true;
        if ($keys) {
            //开启事务
            $cache->multi();
            foreach ($keys as $kk => $kv) {
                if (isset($data[$kk])) {
                    $cache->zAdd($kv, $data[$kk], $data[$primaryKey]);
                }
            }
            //执行事务
            $exec = $cache->exec();
            foreach ($exec as $v) {
                if ($v === false) {
                    $res = false;
                    break;
                }
            }
        } else {
            $res = $cache->sAdd($cacheKey, $data[$primaryKey]);
        }
        return $res;
    }

    /**
     * @param $updated
     * @return bool
     * @throws \Exception
     * ORM更新缓存数据
     */
    public static function updatedRelation($updated): bool
    {
        self::checkRelationCacheConf();
        //如果是有序集合
        $res = true;
        if (static::$relationCacheDataStructure == 'zset') {
            $cache = static::initCache();
            $data = $updated->attributes;
            $original = $updated->original;
            $diff = array_diff_assoc($data, $original);
            $intercept = array_intersect(array_keys($data), static::$relationCacheSortFields);
            if ($intercept && $diff) {
                $primaryKey = $updated->primaryKey;
                $cacheKey = static::getCacheKey($original, static::$relationCacheKey);
                $keys = self::getSortedKeys($cacheKey);
                $cache->multi();
                foreach ($keys as $kk => $kv) {
                    if (isset($diff[$kk])) {
                        $cache->zAdd($kv, $diff[$kk], $original[$primaryKey]);
                    }
                }
                //执行事务
                $exec = $cache->exec();
                foreach ($exec as $v) {
                    if ($v === false) {
                        $res = false;
                        break;
                    }
                }
            }
        }
        return $res;
    }

    /**
     * @param $deleted
     * @return bool
     * @throws \Exception
     * ORM删除缓存数据
     */
    public static function deletedRelation($deleted): bool
    {
        self::checkRelationCacheConf();
        $cache = static::initCache();
        $res = true;
        $original = $deleted->original;
        $primaryKey = $deleted->primaryKey;
        $cacheKey = static::getCacheKey($original, static::$relationCacheKey);
        if (static::$relationCacheDataStructure == 'zset') {
            $keys = self::getSortedKeys($cacheKey);
            $cache->multi();
            foreach ($keys as $kk => $kv) {
                $cache->zRem($kv, $original[$kk], $original[$primaryKey]);
            }
            //执行事务
            $exec = $cache->exec();
            foreach ($exec as $v) {
                if ($v === false) {
                    $res = false;
                    break;
                }
            }
        } else {
            $res = $cache->sRem($cacheKey, $original[$primaryKey]);
        }
        return $res;
    }

    /**
     * @param $key
     * @return array
     * @throws \Exception
     * 获取有序集合排序字段对应的key
     */
    private static function getSortedKeys($key): array
    {
        $keys = [];
        foreach (static::$relationCacheSortFields as $sortField) {
            $keys[$sortField] = "{$sortField}_{$key}";
        }
        return $keys;
    }

    /**
     * @return bool
     * @throws \Exception
     * 检验参数配置
     */
    private static function checkRelationCacheConf(): bool
    {
        if (!in_array(static::$relationCacheDataStructure, ['zset', 'set'])) {
            throw new \Exception('关联缓存错误：数据结构错误，暂时只支持zset和set');
        }
        if (static::$relationCacheDataStructure == 'zset' && empty(static::$relationCacheSortFields)) {
            throw new \Exception('关联缓存错误：数据结构为zset时排序字段必填');
        }
        return true;
    }
}