<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheService
{
    protected int $defaultTtl = 3600;
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $ttl = $ttl ?? $this->defaultTtl;
        try {
            return Cache::remember($key, $ttl, $callback);
        } catch (\Exception $e) {
            Log::error("Cache error for key {$key}: " . $e->getMessage());
            return $callback();
        }
    }
    public function put(string $key, mixed $value, ?int $ttl = null): bool
    { // сохранить в кеш
        $ttl = $ttl ?? $this->defaultTtl;
        
        try {
            return Cache::put($key, $value, $ttl);
        } catch (\Exception $e) {
            Log::error("Cache put error for key {$key}: " . $e->getMessage());
            return false;
        }
    }
    public function get(string $key, mixed $default = null): mixed
    { // получить из кеша
        try {
            return Cache::get($key, $default);
        } catch (\Exception $e) {
            Log::error("Cache get error for key {$key}: " . $e->getMessage());
            return $default;
        }
    }
    public function forget(string $key): bool
    { // удалить из кеша
        try {
            return Cache::forget($key);
        } catch (\Exception $e) {
            Log::error("Cache forget error for key {$key}: " . $e->getMessage());
            return false;
        }
    }
    public function flush(): bool
    { // очистить кеш
        try {
            return Cache::flush();
        } catch (\Exception $e) {
            Log::error("Cache flush error: " . $e->getMessage());
            return false;
        }
    }
    public function has(string $key): bool
    { // проверить ключ
        try {
            return Cache::has($key);
        } catch (\Exception $e) {
            Log::error("Cache has error for key {$key}: " . $e->getMessage());
            return false;
        }
    }
    public function ttl(string $key): int
    { // получить time to live
        try {
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                return Cache::getRedis()->ttl($key);
            } else {
                return -1;
            }
        } catch (\Exception $e) {
            Log::error("Cache TTL error for key {$key}: " . $e->getMessage());
            return -1;
        }
    }
    public function flushPattern(string $pattern): void
    { // очистить кеш по паттерну
        try {
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $keys = Cache::getRedis()->keys($pattern);
                if (!empty($keys)) {
                    Cache::getRedis()->del($keys);
                }
            } else {
                Cache::flush();
            }
        } catch (\Exception $e) {
            Log::error("Cache flush pattern error for {$pattern}: " . $e->getMessage());
        }
    }
    public function rememberWithTags(string $key, array $tags, callable $callback, ?int $ttl = null): mixed
    {
        $ttl = $ttl ?? $this->defaultTtl;
        try {
            return Cache::tags($tags)->remember($key, $ttl, $callback);
        } catch (\Exception $e) {
            Log::error("Cache rememberWithTags error for key {$key}: " . $e->getMessage());
            return $callback();
        }
    }
    public function putWithTags(string $key, mixed $value, array $tags, ?int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->defaultTtl;
        try {
            return Cache::tags($tags)->put($key, $value, $ttl);
        } catch (\Exception $e) {
            Log::error("Cache putWithTags error for key {$key}: " . $e->getMessage());
            return false;
        }
    }
    public function getWithTags(string $key, array $tags, mixed $default = null): mixed
    {
        try {
            return Cache::tags($tags)->get($key, $default);
        } catch (\Exception $e) {
            Log::error("Cache getWithTags error for key {$key}: " . $e->getMessage());
            return $default;
        }
    }
    public function forgetByTags(array $tags): bool
    {
        try {
            Cache::tags($tags)->flush();
            return true;
        } catch (\Exception $e) {
            Log::error("Cache forgetByTags error for tags " . implode(',', $tags) . ": " . $e->getMessage());
            return false;
        }
    }
    public function flushByTags(array $tags): bool
    {
        try {
            Cache::tags($tags)->flush();
            return true;
        } catch (\Exception $e) {
            Log::error("Cache flushByTags error for tags " . implode(',', $tags) . ": " . $e->getMessage());
            return false;
        }
    }
    public function hasWithTags(string $key, array $tags): bool
    {
        try {
            return Cache::tags($tags)->has($key);
        } catch (\Exception $e) {
            Log::error("Cache hasWithTags error for key {$key}: " . $e->getMessage());
            return false;
        }
    }
    public function ttlWithTags(string $key, array $tags): int
    {
        try {
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $redis = Cache::getRedis();
                $taggedKey = $this->buildTaggedKey($key, $tags);
                return $redis->ttl($taggedKey);
            } else {
                return -1;
            }
        } catch (\Exception $e) {
            Log::error("Cache ttlWithTags error for key {$key}: " . $e->getMessage());
            return -1;
        }
    }
    private function buildTaggedKey(string $key, array $tags): string
    {
        // Строим ключ с тегами для Redis
        $tagString = implode('|', $tags);
        return "tagged:{$tagString}:{$key}";
    }
}
