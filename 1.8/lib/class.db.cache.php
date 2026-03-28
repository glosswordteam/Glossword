<?php

/**
 * Glossword - glossary compiler (http://glossword.biz/)
 * © 2008-2026 Glossword.biz team <team at glossword dot biz>
 * © 2002-2008 Dmitry N. Shilnikov
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */
if (!defined('IN_GW')) {
    die('<!-- Not in App -->');
}

if (!class_exists('gwtkCache')) {
    /**
     * Simple file cache for SQL results and temporary data.
     *
     * Notes:
     * - No gzip support
     * - No legacy escaping/unescaping
     * - Atomic writes via temporary file + rename()
     * - Any PHP value is stored and restored via serialize()/unserialize()
     */
    class gwtkCache
    {
        /** @var string */
        public $path_root = '.';

        /** @var string */
        public $path_store = 'cache/sql';

        /** @var string */
        public $cache_ex = '.tmp';

        /** @var string */
        public $cache_prefix = '';

        /**
         * Cache lifetime in seconds.
         * 0 means never expire.
         *
         * @var int
         */
        public $cache_lifetime = 5;

        /** @var string */
        public $cache_subdir = '';

        /** @var string */
        public $filename = '_';

        /**
         * Cache key, usually md5 hash.
         *
         * @var string
         */
        private $_cache_key_hash = '_';

        /**
         * Debug log.
         *
         * @var array
         */
        public $query_array = array();

        /** @var int */
        public $cnt_queries_debug = 0;

        /**
         * Set cache directory path relative to root.
         *
         * @param string $dir
         * @return void
         */
        public function setPath($dir)
        {
            $this->path_store = (string) $dir;
        }

        /**
         * Set cache key and optional filename prefix.
         *
         * @param string $str
         * @param string $prefix
         * @return void
         */
        public function setKey($str, $prefix)
        {
            $this->_cache_key_hash = md5((string) $str);
            $this->cache_prefix = (string) $prefix;
        }

        /**
         * Prepare current filename.
         *
         * @return void
         */
        private function setFilename()
        {
            $this->filename = $this->buildFilename();
        }

        /**
         * Build full cache filename.
         *
         * @return string
         */
        private function buildFilename()
        {
            $dir = $this->buildDirectoryPath();
            if (!$this->ensureDirectoryExists($dir)) {
                return '_';
            }

            return $dir . '/' . $this->buildBasename();
        }

        /**
         * Build full directory path for cache storage.
         *
         * @return string
         */
        private function buildDirectoryPath()
        {
            $parts = array(
                rtrim($this->path_root, '/'),
                trim($this->path_store, '/')
            );

            if ($this->cache_subdir !== '') {
                $parts[] = trim($this->cache_subdir, '/');
            }

            return implode('/', $parts);
        }

        /**
         * Build cache file basename.
         *
         * @return string
         */
        private function buildBasename()
        {
            $prefix = ($this->cache_prefix !== '') ? $this->cache_prefix . '_' : '';

            return $prefix . $this->cache_key_hash . $this->cache_ex;
        }

        /**
         * Ensure cache directory exists.
         *
         * @param string $dir
         * @return bool
         */
        private function ensureDirectoryExists($dir)
        {
            if (is_dir($dir)) {
                return true;
            }

            if (mkdir($dir, 0777, true)) {
                return true;
            }

            if (is_dir($dir)) {
                return true;
            }

            $this->query_array[] = 'Cannot create directory: ' . $dir;

            return false;
        }

        /**
         * Delete current cache file from disk.
         *
         * @return bool
         */
        private function deleteFile()
        {
            if (!is_file($this->filename)) {
                return true;
            }

            $this->query_array[] = 'Delete ' . $this->filename;

            if (unlink($this->filename)) {
                return true;
            }

            $this->query_array[] = 'Cannot delete: ' . $this->filename;

            return false;
        }

        /**
         * Check whether current cache file exists and is not expired.
         *
         * @return bool
         */
        public function isValid()
        {
            $this->setFilename();

            if ($this->filename === '_' || !is_file($this->filename)) {
                return false;
            }

            if ($this->cache_lifetime > 0) {
                $file_mtime = filemtime($this->filename);
                if ($file_mtime === false) {
                    $this->query_array[] = 'Cannot read mtime: ' . $this->filename;
                    $this->deleteFile();

                    return false;
                }

                if ($file_mtime < (time() - (int) $this->cache_lifetime)) {
                    $this->deleteFile();

                    return false;
                }
            }

            return true;
        }

        /**
         * Save any PHP value into cache.
         *
         * @param mixed $content
         * @return bool
         */
        public function save($content)
        {
            if ($this->filename === '_' || $this->filename === '') {
                $this->setFilename();
            }

            if ($this->filename === '_') {
                return false;
            }

            $payload = serialize($content);

            $this->query_array[] = 'Save ' . $this->filename;

            return $this->writeFileAtomic($this->filename, $payload);
        }

        /**
         * Load any PHP value from cache.
         *
         * Returns NULL on failure.
         *
         * @return mixed
         */
        public function load()
        {
            if ($this->filename === '_' || $this->filename === '') {
                $this->setFilename();
            }

            $this->query_array[] = 'Load ' . $this->filename;
            ++$this->cnt_queries_debug;

            if (!is_file($this->filename)) {
                return null;
            }

            $payload = file_get_contents($this->filename);
            if ($payload === false) {
                $this->query_array[] = 'Cannot read: ' . $this->filename;

                return null;
            }

            return $this->safeUnserialize($payload);
        }

        /**
         * Write file atomically.
         *
         * @param string $filename
         * @param string $content
         * @return bool
         */
        private function writeFileAtomic($filename, $content)
        {
            $tmp_filename = $filename . '.part';

            if (file_put_contents($tmp_filename, $content, LOCK_EX) === false) {
                $this->query_array[] = 'Cannot write: ' . $tmp_filename;

                return false;
            }

            if (!rename($tmp_filename, $filename)) {
                @unlink($tmp_filename);
                $this->query_array[] = 'Cannot rename ' . $tmp_filename . ' to ' . $filename;

                return false;
            }

            return true;
        }

        /**
         * Unserialize cached payload safely.
         *
         * Returns NULL on failure.
         *
         * @param string $payload
         * @return mixed
         */
        private function safeUnserialize($payload)
        {
            if ($payload === '') {
                $this->query_array[] = 'Empty cache payload: ' . $this->filename;

                return null;
            }

            $value = @unserialize($payload);

            if ($value === false && $payload !== 'b:0;') {
                $this->query_array[] = 'Cannot unserialize cache: ' . $this->filename;

                return null;
            }

            return $value;
        }
    }
}