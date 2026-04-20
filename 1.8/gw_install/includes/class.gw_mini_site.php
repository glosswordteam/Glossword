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
 * (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
if (!defined('IN_GW')) {
    die('<!-- Not in App -->');
}

/**
 * Minimal installer site controller.
 *
 * Compatible with PHP 5.6.
 */
class gw_mini_site
{
    /**
     * @var object
     */
    public $oTkit;

    /**
     * @var object
     */
    public $oHtml;

    /**
     * @var object
     */
    public $oTimer;

    /**
     * @var object
     */
    public $oFunc;

    /**
     * @var object
     */
    public $oChecker;

    /**
     * @var object
     */
    public $oHtmlTags;

    /**
     * @var object
     */
    public $oTpl;

    /**
     * @var object
     */
    public $oXml;

    /**
     * @var object
     */
    public $V;

    /**
     * @var array
     */
    public $gv = [];

    /**
     * @var array
     */
    public $ar_broken = [];

    /**
     * @var array
     */
    public $ar_steps = [];

    /**
     * @var string
     */
    public $current_function = '';

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->V = new gw_var_store([
                                        'is_debug_time' => 0,
                                        'is_debug_tkit' => 0,
                                        'is_debug_db' => 0,
                                        'is_show_debug_db' => 0,
                                        'visual-theme' => 'visual-theme',
                                        'path_locale' => 'locale',
                                        'path_views' => 'views',
                                        'path_css' => 'visual-theme',
                                        'path_js' => '.',
                                        'path_tpl' => 'visual-theme',
                                        'path_temp' => 'temp',
                                        'path_includes' => 'includes',
                                        'path_db' => 'includes',
                                        'file_index' => 'index.php',
                                        'version' => '1.8.13',
                                        'site_name' => 'Glossword',
                                        'site_desc' => 'Glossary compiler',
                                        'path_temp_app' => '../gw_temp',
                                        'file_lock' => '../gw_temp/install_lock.txt',
                                    ]);
    }

    /**
     * Get one variable or all variables from the store.
     *
     * @param string $variable
     *
     * @return mixed
     */
    public function g($variable = '')
    {
        if ($variable !== '') {
            return $this->V->{$variable};
        }

        return get_object_vars($this->V);
    }

    /**
     * Set variable in the store.
     *
     * @param string $variable
     * @param mixed $value
     *
     * @return void
     */
    public function a($variable, $value)
    {
        $this->V->{$variable} = $value;
    }

    /**
     * Initialize application services.
     *
     * @return void
     */
    public function init()
    {
        $this->oTimer = $this->_init_timer('init');
        $this->a('debug_memory_s', memory_get_usage());

        /* Auto time for server */
        $this->a('time_req', isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time());
        $this->a('time_gmt', $this->V->time_req - @date('Z'));

        /* Get accepted encoding */
        $this->a(
            'HTTP_ACCEPT_ENCODING',
            isset($_SERVER['HTTP_ACCEPT_ENCODING'])
                ? $_SERVER['HTTP_ACCEPT_ENCODING']
                : (isset($_SERVER['HTTP_TE']) ? $_SERVER['HTTP_TE'] : '')
        );

        /* Get remote IP string */
        $this->a('REMOTE_ADDR', gw_get_remote_ip());

        /* Functions class */
        $this->oFunc = $this->_init_functions();

        switch ($this->gv['arv']) {
            case 'css':
            case 'js':
                break;

            default:
                /* HTML templates */
                $this->oTpl = $this->_init_html_tpl();
                /* XML reader */
                $this->oXml = $this->_init_xmlreader();
                /* Requirements checker */
                $this->oChecker = $this->_init_reqchecker();
                /* HTML tags */
                $this->oHtmlTags = $this->_init_html_tags();

                $this->set_steps();
                $this->oTpl->addVal('v:favicon', 'favicon.ico');
                $this->ar_broken = [];
                break;
        }

        /* Translation kit */
        $this->oTkit = $this->_init_tkit(['gwreqcheck-global', 'gwinstall-global'], $this->gv['il']);

        /* Correct interface language */
        $this->gv['il'] = $this->oTkit->arL['lang_uri'];

        $this->oHtml = $this->_init_html();

        $this->oTimer->end('init');
        $this->oTimer->start('proc');
    }

    /**
     * Initialize helper functions.
     *
     * @return tkit_functions
     */
    public function _init_functions()
    {
        require_once($this->V->path_includes . '/functions.php');

        return new tkit_functions;
    }

    /**
     * Initialize requirements checker.
     *
     * @return gw_reqcheck
     */
    public function _init_reqchecker()
    {
        require_once($this->V->path_includes . '/reqchecker.php');

        return new gw_reqcheck;
    }

    /**
     * Initialize XML reader wrapper.
     *
     * @return gw2_xmlreader5
     */
    public function _init_xmlreader()
    {
        require_once($this->V->path_includes . '/xml_reader5.php');

        return new gw2_xmlreader5;
    }

    /**
     * Initialize translation kit.
     *
     * @param array $ar_tkit_profiles
     * @param string $il
     *
     * @return tkit
     */
    public function _init_tkit($ar_tkit_profiles, $il)
    {
        require_once($this->g('path_includes') . '/class.tkit.php');

        $o = new tkit;
        $o->path_locale = $this->g('path_locale');
        $o->is_debug = $this->g('is_debug_tkit');

        /* Load phrases */
        $o->import_tag($ar_tkit_profiles, $il);

        return $o;
    }

    /**
     * Initialize HTML helper.
     *
     * @return gw2_html
     */
    public function _init_html()
    {
        require_once($this->g('path_includes') . '/class.html_gw2.php');

        $o = new gw2_html;
        $o->path_css = $this->g('path_css');
        $o->path_js = $this->g('path_js');
        $o->HTTP_ACCEPT_ENCODING = $this->g('HTTP_ACCEPT_ENCODING');

        return $o;
    }

    /**
     * Initialize HTML tags helper.
     *
     * @return gw2_html_tags
     */
    public function _init_html_tags()
    {
        require_once($this->g('path_includes') . '/class.html_tags.php');

        return new gw2_html_tags;
    }

    /**
     * Initialize HTML template engine.
     *
     * @return tkit_template
     */
    public function _init_html_tpl()
    {
        $this->a('is_tpl_show_names', 0);

        require_once($this->g('path_includes') . '/class.tpl.php');
        require_once($this->g('path_includes') . '/class.template.ext.php');

        $o = new tkit_template();
        /* $o->oDb =& $this->oDb; */
        $o->init($this->g('visual-theme'));
        $o->path_source = $this->g('path_tpl');
        $o->path_cache = $this->g('path_temp');
        $o->tmp['d'] = [];

        return $o;
    }

    /**
     * Initialize database connection.
     *
     * @param array $ar_params
     * @param bool $is_return
     *
     * @return mixed
     */
    public function _init_db($ar_params = [], $is_return = false)
    {
        if (!defined('BASEPATH')) {
            define('BASEPATH', '');
        }

        if (!defined('EXT')) {
            define('EXT', '.php');
        }

        if (!class_exists('CI_Exceptions')) {
            include($this->g('path_includes') . '/Exceptions.php');
        }

        require_once($this->g('path_includes') . '/DB.php');

        $ar_params['hostname'] = $ar_params['db_host'];
        $ar_params['username'] = $ar_params['db_user'];
        $ar_params['database'] = $ar_params['db_name'];
        $ar_params['password'] = $ar_params['db_pass'];
        $ar_params['dbprefix'] = $ar_params['db_prefix'];
        $ar_params['dbdriver'] = $ar_params['db_type'];
        $ar_params['pconnect'] = false;
        $ar_params['active_r'] = true;
        $ar_params['db_debug'] = false;
        $ar_params['db_debug_q'] = false;
        $ar_params['cache_on'] = false;
        $ar_params['cachedir'] = '';

        if ($is_return === true) {
            return ci_db($ar_params, $this->g('path_includes'));
        }

        return ci_db($ar_params, $this->g('path_includes'));
    }

    /**
     * Initialize database forge.
     *
     * @return void
     */
    public function _init_db_forge()
    {
        /* require_once($this->g('path_includes') . '/DB_forge.php'); */
        /* return new CI_DB_forge; */
    }

    /**
     * Initialize timer.
     *
     * @param string $prefix
     *
     * @return gw_mini_timer
     */
    public function _init_timer($prefix = '')
    {
        return new gw_mini_timer($prefix);
    }

    /**
     * Include current view file.
     *
     * @return void
     */
    public function page_body()
    {
        $file_to_function = $this->V->path_views . '/' . $this->current_function . '.php';

        switch ($this->gv['arv']) {
            case 'css':
            case 'js':
                break;

            default:
                if (is_file($file_to_function)) {
                    include($file_to_function);
                }
                break;
        }
    }

    /**
     * Put translation kit phrases into the template engine.
     *
     * @return void
     */
    public function import_tkit_phrases()
    {
        $phrases = $this->oTkit->get_phrases_all();

        if (!is_array($phrases)) {
            return;
        }

        foreach ($phrases as $phrase_key => $phrase_value) {
            $this->oTpl->addVal('l:' . $phrase_key, $phrase_value);
        }
    }

    /**
     * Configure installation steps.
     *
     * @param int $int_steps
     *
     * @return void
     */
    public function set_steps($int_steps = 0)
    {
        $this->ar_steps = [];

        if ($int_steps < 0) {
            $int_steps = 0;
        }

        for ($step_index = 1; $step_index <= $int_steps; $step_index++) {
            $this->ar_steps[$step_index] = $step_index;
        }
    }

    /**
     * Get next installation step.
     *
     * @param int $this_step
     *
     * @return int
     */
    public function get_next_step($this_step)
    {
        return isset($this->ar_steps[$this_step + 1]) ? $this->ar_steps[$this_step + 1] : 0;
    }

    /**
     * Render installation steps HTML.
     *
     * @return string
     */
    public function get_html_steps()
    {
        $steps_html = [];

        foreach ($this->ar_steps as $step) {
            $steps_html[$step] = '{l:10001} ' . $step;

            if ($step == $this->gv['step']) {
                $steps_html[$step] = '<em>' . $steps_html[$step] . '</em>';
            }
        }

        return implode(' &#8226; ', $steps_html);
    }

    /**
     * Import topics from XML file.
     *
     * @param string $filename
     *
     * @return bool
     */
    public function import_topics_file($filename)
    {
        $ar_data = $this->_get_xml_import_data($filename, true);

        if ($ar_data === false || !isset($ar_data['topic']) || !is_array($ar_data['topic'])) {
            return false;
        }

        $this->oDb->truncate('topics');
        $this->oDb->truncate('topics_phrase');

        foreach ($ar_data['topic'] as $topic_data) {
            $id_topic = $topic_data['attributes']['id'];
            $topic_row = [
                'id_topic' => $id_topic,
            ];
            $topic_phrase_base_row = [
                'id_topic' => $id_topic,
            ];

            foreach ($topic_data['value'] as $topic_item_group) {
                $topic_item = $topic_item_group[0];

                switch ($topic_item['tag']) {
                    case 'parameters':
                        $topic_parameters = @unserialize($topic_item['value']);

                        if (!is_array($topic_parameters)) {
                            $topic_parameters = [];
                        }

                        $topic_row = $topic_parameters + $topic_row;
                        break;

                    case 'entry':
                        foreach ($topic_item['value'] as $lang_group) {
                            foreach ($lang_group as $lang_entry) {
                                $id_lang = $lang_entry['attributes']['xml:lang'];
                                $topic_phrase_row = $topic_phrase_base_row;

                                foreach ($lang_entry['value'] as $field_group) {
                                    $field_item = $field_group[0];
                                    $topic_phrase_row[$field_item['tag']] = $field_item['value'];
                                }

                                $topic_phrase_row['id_lang'] = $id_lang . '-utf8';
                                $this->oDb->insert('topics_phrase', $topic_phrase_row);
                            }
                        }
                        break;
                }
            }

            if (!isset($topic_row['date_created'])) {
                $topic_row['date_created'] = $this->g('time_gmt');
                $topic_row['date_modified'] = $topic_row['date_created'];
            }

            $this->oDb->insert('topics', $topic_row);
        }

        return true;
    }

    /**
     * Import custom pages from XML file.
     *
     * @param string $filename
     *
     * @return bool
     */
    public function import_custom_pages_file($filename)
    {
        $ar_data = $this->_get_xml_import_data($filename, true);

        if ($ar_data === false || !isset($ar_data['custom_page']) || !is_array($ar_data['custom_page'])) {
            return false;
        }

        $this->oDb->truncate('pages');
        $this->oDb->truncate('pages_phrase');

        foreach ($ar_data['custom_page'] as $custom_page_data) {
            $id_page = $custom_page_data['attributes']['id'];
            $page_row = [
                'id_page' => $id_page,
            ];
            $page_phrase_base_row = [
                'id_page' => $id_page,
            ];

            foreach ($custom_page_data['value'] as $page_item_group) {
                $page_item = $page_item_group[0];

                switch ($page_item['tag']) {
                    case 'parameters':
                        $page_parameters = @unserialize($page_item['value']);

                        if (!is_array($page_parameters)) {
                            $page_parameters = [];
                        }

                        $page_row = $page_parameters + $page_row;
                        break;

                    case 'entry':
                        foreach ($page_item['value'] as $lang_group) {
                            foreach ($lang_group as $lang_entry) {
                                $id_lang = $lang_entry['attributes']['xml:lang'];
                                $page_phrase_row = $page_phrase_base_row;

                                foreach ($lang_entry['value'] as $field_group) {
                                    $field_item = $field_group[0];
                                    $page_phrase_row[$field_item['tag']] = $field_item['value'];
                                }

                                $page_phrase_row['id_lang'] = $id_lang . '-utf8';

                                if (!isset($page_phrase_row['page_descr'])) {
                                    $page_phrase_row['page_descr'] = '';
                                }

                                if (!isset($page_phrase_row['page_keywords'])) {
                                    $page_phrase_row['page_keywords'] = '';
                                }

                                if (!isset($page_phrase_row['page_content'])) {
                                    $page_phrase_row['page_content'] = '';
                                }

                                $this->oDb->insert('pages_phrase', $page_phrase_row);
                            }
                        }
                        break;

                    default:
                        /* page_php_1, page_php_2 */
                        $page_row[$page_item['tag']] = $page_item['value'];
                        break;
                }
            }

            if (!isset($page_row['page_php_1'])) {
                $page_row['page_php_1'] = '';
            }

            if (!isset($page_row['page_php_2'])) {
                $page_row['page_php_2'] = '';
            }

            /* Imported by admin */
            $page_row['id_user'] = '2';
            $page_row['date_created'] = $this->g('time_gmt');
            $page_row['date_modified'] = $page_row['date_created'];

            $this->oDb->insert('pages', $page_row);
        }

        return true;
    }

    /**
     * Import visual theme from XML file.
     *
     * @param string $filename
     *
     * @return bool
     */
    public function import_visual_themes_file($filename)
    {
        $ar_data = $this->_get_xml_import_data($filename, false);

        if (
            $ar_data === false
            || !isset($ar_data['style'][0]['attributes'])
            || !isset($ar_data['style'][0]['value']['group'])
            || !is_array($ar_data['style'][0]['value']['group'])
        ) {
            return false;
        }

        $q1 = $ar_data['style'][0]['attributes'];
        $q2 = [];
        $version_parts = array_pad(explode('.', $q1['version']), 3, 0);

        list($q1['v1'], $q1['v2'], $q1['v3']) = $version_parts;
        unset($q1['version']);

        $theme_dir = $q1['id_theme'];

        /* Compatibility with old database */
        $q1['id_theme'] = str_replace('_', '\\_', $q1['id_theme']);

        /* Clear settings for existing theme */
        $this->oDb->delete('theme', ['id_theme' => $q1['id_theme']]);
        $this->oDb->delete('theme_settings', ['id_theme' => $q1['id_theme']]);

        /* Insert new */
        $this->oDb->insert('theme', $q1);

        foreach ($ar_data['style'][0]['value']['group'] as $ar_v1) {
            $id_group = $ar_v1['attributes']['id'];

            foreach ($ar_v1['value'] as $ar_v2) {
                foreach ($ar_v2 as $setting_index => $ar_v3) {
                    switch ($id_group) {
                        case 'settings':
                            /* Compatibility with old database */
                            $ar_v3['attributes']['key'] = str_replace('_', '\\_', $ar_v3['attributes']['key']);

                            $q2[$setting_index]['id_theme'] = $q1['id_theme'];
                            $q2[$setting_index]['date_modified'] = $this->g('time_gmt');
                            $q2[$setting_index]['settings_key'] = $ar_v3['attributes']['key'];
                            $q2[$setting_index]['settings_value'] = $ar_v3['value'];
                            $q2[$setting_index]['settings_value'] = str_replace('&lt;![CDATA[', '<![CDATA[', $q2[$setting_index]['settings_value']);
                            $q2[$setting_index]['settings_value'] = str_replace(']]&gt;', ']]>', $q2[$setting_index]['settings_value']);
                            $q2[$setting_index]['code'] = '';
                            $q2[$setting_index]['code_i'] = '';
                            break;

                        case 'binary':
                            $binary_filename = $this->g('path_temp_app') . '/t/' . $theme_dir . '/' . $ar_v3['attributes']['key'];
                            $this->oFunc->file_put_contents(
                                $binary_filename,
                                pack('H' . strlen($ar_v3['value']), $ar_v3['value']),
                                'w'
                            );
                            break;
                    }
                }
            }
        }

        if (!empty($q2)) {
            $this->oDb->insert('theme_settings', $q2);
        }

        return true;
    }

    /**
     * Import custom alphabetic order profile from XML file.
     *
     * @param string $filename
     *
     * @return bool
     */
    public function import_custom_az_file($filename)
    {
        $ar_data = $this->_get_xml_import_data($filename, true);

        if (
            $ar_data === false
            || !isset($ar_data['custom_az'][0]['attributes'])
            || !isset($ar_data['custom_az'][0]['value']['entry'])
            || !is_array($ar_data['custom_az'][0]['value']['entry'])
        ) {
            return false;
        }

        $q1 = $ar_data['custom_az'][0]['attributes'];
        $q2 = [];

        /* Create a new profile */
        $id_profile = 1;
        $this->oDb->select_max('id_profile');
        $query = $this->oDb->get('custom_az_profiles');

        foreach ($query->result() as $row) {
            $id_profile = ((int) $row->id_profile) + 1;
        }

        $q1['id_profile'] = $id_profile;
        $this->oDb->insert('custom_az_profiles', $q1);

        foreach ($ar_data['custom_az'][0]['value']['entry'] as $entry_index => $entry_data) {
            $q2[$entry_index]['id_profile'] = $id_profile;

            foreach ($entry_data['value'] as $entry_group) {
                foreach ($entry_group as $entry_item) {
                    $q2[$entry_index][$entry_item['tag']] = $entry_item['value'];
                }
            }

            /* Convert string to numeric sort key */
            $int_len = strlen($q2[$entry_index]['az_value']);
            $az_int = '';

            for ($char_index = 0; $char_index < $int_len; $char_index++) {
                $az_int .= ord($q2[$entry_index]['az_value'][$char_index]);
            }

            $q2[$entry_index]['az_int'] = $az_int;
        }

        if (!empty($q2)) {
            $this->oDb->insert('custom_az', $q2);
        }

        return true;
    }

    /**
     * Prepare page header state.
     *
     * @return void
     */
    public function page_header()
    {
        $this->current_function = $this->gv['target'] . '_' . $this->gv['action'];

        switch ($this->gv['arv']) {
            case 'css':
            case 'js':
                break;

            default:
                /* Check for locked installation */
                if (is_file($this->g('file_lock'))) {
                    $lock_dir = realpath(dirname($this->g('file_lock')));
                    $real_filename = $lock_dir
                        ? $lock_dir . '/' . basename($this->g('file_lock'))
                        : $this->g('file_lock');

                    $real_filename = str_replace('\\', '/', $real_filename);

                    print '<div style="padding:1em;font: 100% sans-serif;">'
                        . $this->oTkit->_(20017, '<samp>' . $real_filename . '</samp>')
                        . '</div>';
                    exit;
                }

                /* Add header by default */
                $this->oHtml->append_html_title(
                    $this->oTkit->_(20000) . ': ' . $this->g('site_name') . ' ' . $this->g('version')
                );
                break;
        }
    }

    /**
     * Finalize response output.
     *
     * @return void
     */
    public function page_footer()
    {
        $this->oTimer->end('proc');

        switch ($this->gv['arv']) {
            case 'css':
                header('Content-type: text/css');
                $this->oHtml->css_a_file($this->gv['file']);
                $this->oHtml->css_a('v:path_css', $this->g('path_css'));
                print $this->oHtml->css_g();
                return;

            case 'js':
                header('Content-type: application/javascript');
                break;

            default:
                $this->import_tkit_phrases();
                $this->oTpl->addVal('v:steps', $this->get_html_steps());

                $this->oTpl->addVal('v:html_title', $this->oHtml->get_html_title());
                $this->oTpl->addVal('v:file_index', GW2_THIS_SCRIPT);
                $this->oTpl->addVal('v:xml-lang', $this->oTkit->arL['isocode3']);
                $this->oTpl->addVal('v:text_direction', $this->oTkit->arL['direction']);
                $this->oTpl->addVal('v:charset', 'utf-8');
                $this->oTpl->addVal('v:path_css', $this->g('path_css'));
                $this->oTpl->addVal('v:version', $this->g('version'));
                $this->oTpl->addVal('v:il', $this->gv['il']);
                $this->oTpl->addVal('v:form_action', $this->g('file_index'));

                header('Content-type: text/html');

                /* Set HTML template */
                $this->oTpl->set_tpl(GW2_TPL_WEB_INDEX);

                /* Parse dynamic blocks */
                foreach ($this->oTpl->tmp['d'] as $id_dynamic => $dynamic_block) {
                    if (is_array($dynamic_block)) {
                        foreach ($dynamic_block as $dynamic_row) {
                            foreach ($dynamic_row as $assign_key => $assign_value) {
                                $this->oTpl->assign([$assign_key => $assign_value]);
                            }

                            $this->oTpl->parseDynamic($id_dynamic);
                        }
                    } else {
                        $this->oTpl->parseDynamic($id_dynamic);
                    }

                    unset($this->oTpl->tmp['d'][$id_dynamic]);
                }

                $str_debug_sql = '';
                $query_count = 0;
                $db_time = 0;

                if (isset($this->oDb->queries)) {
                    $query_count = $this->oDb->query_count;

                    if ($this->g('is_show_debug_db')) {
                        foreach ($this->oDb->queries as $query_index => $query_sql) {
                            $this->oDb->queries[$query_index] = str_replace('{', '&#123;', $query_sql);
                        }

                        $str_debug_sql .= '<ol title="database"><li>'
                            . implode('</li><li>', $this->oDb->queries)
                            . '</li></ol>';
                    }

                    $db_time = $this->oDb->elapsed_time(3);
                }

                if ($this->g('is_debug_time')) {
                    $time_php = $this->oTimer->_('init') + $this->oTimer->_('proc');
                    $this->oTpl->addVal(
                        'v:debug',
                        '<div class="debugwindow">'
                        . 'Total: <strong>' . sprintf('%1.3f', $time_php + $db_time) . '</strong> - '
                        . 'PHP: <strong>' . sprintf('%1.3f', $time_php) . '</strong> '
                        . '(init: ' . $this->oTimer->_('init') . ' + proc: ' . $this->oTimer->_('proc') . ') - '
                        . 'SQL: <strong>' . $db_time . '</strong> '
                        . '(Queries: <strong>' . $query_count . '</strong>) - '
                        . 'Memory, bytes: '
                        . $this->oTkit->number_format(memory_get_usage() - $this->g('debug_memory_s'))
                        . ' - ' . $this->V->path_views . '/' . $this->current_function . '.php'
                        . $str_debug_sql
                        . '</div>'
                    );
                }

                /* Compile HTML template */
                $this->oTpl->parse();
                $this->oHtml->append($this->oTpl->output());
                break;
        }

        print $this->oHtml->g();
    }

    /**
     * Register and normalize global variables.
     *
     * @param array $ar
     *
     * @return void
     */
    public function global_variables($ar = [])
    {
        require_once($this->g('path_includes') . '/class.register_globals.php');

        $o_globals = new tkit_register_globals($ar);
        $this->gv = $o_globals->register($ar);

        /* Shorthand $this->gv['arg']['var'] => $this->gv['var'] */
        if (isset($this->gv['arg']) && is_array($this->gv['arg'])) {
            foreach ($this->gv['arg'] as $arg_key => $arg_value) {
                $this->gv[$arg_key] = $arg_value;
                unset($this->gv['arg'][$arg_key]);
            }
        }

        $o_globals->do_default($this->gv['target'], 'chooselanguage');
        $o_globals->do_default($this->gv['il'], 'english');
        $o_globals->do_default($this->gv['step'], '1');

        /* Filter incoming data */
        $o_globals->do_alphanum($this->gv['target']);
        $o_globals->do_alphanum($this->gv['action']);
        $o_globals->do_alphanum($this->gv['file']);
        $o_globals->do_alphanum($this->gv['il']);
        $o_globals->do_alphanum($this->gv['step']);
    }

    /**
     * Read XML import file and return parsed data.
     *
     * @param string $filename
     * @param bool $is_skip_root
     *
     * @return array|false
     */
    private function _get_xml_import_data($filename, $is_skip_root = true)
    {
        $this->oXml->is_skip_root = $is_skip_root ? true : false;

        $xml_data = $this->oXml->get($filename);

        if (!is_array($xml_data)) {
            return false;
        }

        return $xml_data;
    }
}
