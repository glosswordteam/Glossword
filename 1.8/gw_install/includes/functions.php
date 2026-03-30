<?php

if (!defined('IS_CLASS_GW2_FUNCTIONS')) {
    define('IS_CLASS_GW2_FUNCTIONS', 1);

    /**
     * Replacement for print_r()
     *
     * @param string $a Any string, object, or array.
     * @param string $c Additional marker for better visual display. Try "__FILE__"
     */
    function prn_r($a, $c = '')
    {
        if (is_array($a)) {
            ksort($a);
            $a = gw_htmlspecialchars_ltgt($a);
        } elseif (is_object($a) || is_string($a)) {
            $a = gw_htmlspecialchars_ltgt($a);
        }
        /* Set font size in pixels because function can be called from various places */
        print '<pre style="text-align:left;color:#000;background:#FFF;font: 14px/16px Consolas,\'Courier New\',monospace">';
        if ($c) {
            print '===&gt; <strong>' . $c . "</strong>\n";
        }
        /* Placing the output into buffer */
        ob_start();
        print_r($a);
        $b = ob_get_clean();
        /* compress indents */
        $b = preg_replace("/(^)?(    )([\(|\)|\[])?/", "  \\3", $b);
        /* highlight Array and Object */
        $b = str_replace('] => Array', '] => <span style="color:#080">Array</span>', $b);
        $b = str_replace('] => Object', '] => <span style="color:#080">Object</span>', $b);
        /* highlight numeric positive and negative keys */
        $b = preg_replace("/\[(-)?(\d+)\] =>/", '<span style="color:#888">&#91;<span style="color:#00C">\\1\\2</span>] =></span>', $b);
        $b = preg_replace("/\[(.*)\] =>/", '<span style="color:#888">&#91;<span style="color:#C50">\\1</span>] =></span>', $b);
        print $b;
        if ($c) {
            print '&lt;===';
        }
        print '</pre>';
    }


    /**
     * Merges arrays and clobber any existing key/value pairs
     * Keeps numeric keys, they will be not renumbered.
     *
     * @param array $a1 First array
     * @param array $a2 Second array
     * @return  array   Merged arrays
     */
    if (!function_exists('array_merge_clobber')) {
        function array_merge_clobber($a1, $a2)
        {
            if (!is_array($a1) || !is_array($a2)) {
                return false;
            }

            $arNew = $a1;

            foreach ($a2 as $key => $val) {
                if (is_array($val) && isset($arNew[$key]) && is_array($arNew[$key])) {
                    $arNew[$key] = gw_array_merge_clobber($arNew[$key], $val);
                } else {
                    $arNew[$key] = $val;
                }
            }

            return $arNew;
        }
    }


    /* */

    class tkit_functions
    {

        /* */
        public function make_int16($s)
        {
            $h = hash('md5', $s);
            $n = '';
            for ($i = 0; $i < 32; $i++) {
                $n .= hexdec(substr($h, $i, 2));
                if (strlen($n) > 16) {
                    $n = substr($n, 0, 16);
                    break;
                }
                $i++;
            }
            return $n;
        }

        public function hexbin($s)
        {
            return pack("H*", $s);
        }

        /* */
        public function make_str_random($a = 6)
        {
            $b = 'bdfghijkmnqrstuwzQWRUSDFGJLZN23456789';
            $c = strlen($b);
            $s = '';
            for ($i = 0; $i < $a; $i++) {
                $s .= $b[mt_rand(0, $c - 1)];
            }
            return $s;
        }

        /**
         * Get file contents. Binary and fail safe.
         *
         * @param string $filename Full path to filename
         * @return  string  File contents
         */
        public function file_get_contents($filename)
        {
            if (!file_exists($filename)) {
                return '[file_get_contents: file ' . $filename . ' does not exist]';
            }
            if (function_exists('file_get_contents')) /* PHP4 CVS only */ {
                $str = file_get_contents($filename);
            } else {
                /* file() is binary safe from PHP 4.3.0
                faster: $str = implode('', file($filename));
                */
                $fd = fopen($filename, "rb");
                $str = fread($fd, filesize($filename));
                fclose($fd);
            }
            if ($str == '') {
                return '[loadfile: ' . $filename . ' is empty]';
            }
            /* Remove slashes, 23 march 2002 */
            if (function_exists('get_magic_quotes_runtime') && @get_magic_quotes_runtime()) {
                $str = stripslashes($str);
            }
            return $str;
        }

        /**
         * Put contents into a file. Binary and fail safe.
         *
         * @param string $filename Full path to filename
         * @param string $content File contents
         * @param string $mode [ w = write new file (default) | a = append ]
         * @return  TRUE if success, FALSE otherwise
         */
        public function file_put_contents($filename, $content, $mode = "w")
        {
            $filename = str_replace('\\', '/', $filename);
            /* new file */
            if (!file_exists($filename)) {
                /* check & create directories first */
                $arParts = explode('/', $filename);
                $intParts = (sizeof($arParts) - 1);
                $d = '';
                for ($i = 0; $i < $intParts; $i++) {
                    $d .= $arParts[$i] . '/';
                    if (is_dir($d)) {
                        continue;
                    } else {
                        $oldumask = umask(0);
                        @mkdir($d, 0777);
                        @chmod($d, 0777);
                        umask($oldumask);
                    }
                }
                /* Nothing to write */
                if ($content == '') {
                    return true;
                }
                /* Write to file */
                $fp = @fopen($filename, "wb");
                @chmod($filename, 0777);
                if ($fp) {
                    fputs($fp, $content);
                } else {
                    return false;
                }
                fclose($fp);
            } else {
                /* Append to file */
                /* note: binary mode is transparent */
                if ($fp = @fopen($filename, $mode . 'b')) {
                    $is_allow = flock($fp, 2); /* lock for writing & reading */
                    if ($is_allow) {
                        fputs($fp, $content, strlen($content));
                    }
                    flock($fp, 3); /* unlock */
                    fclose($fp);
                } else {
                    return false;
                }
            }
            return true;
        }

        /**
         * Removes a file or en empty directory from disk
         */
        public function file_remove($filename)
        {
            if (file_exists($filename) && is_file($filename) && unlink($filename)) {
                return true;
            } elseif (file_exists($filename) && is_dir($filename) && rmdir($filename)) {
                return true;
            }
            return false;
        }
    }
}