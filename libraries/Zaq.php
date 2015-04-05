<?php

class Zaq {

    protected $_l_delimiter = '{';
    protected $_r_delimiter = '}';
    protected $_l_delimiter_pattern = '';
    protected $_r_delimiter_pattern = '';
    protected $_zaq_file;
    protected $_zaq_path;
    public $CI;
    protected $_reserved = array(
        'foreach', 'as', 'if', 'elif', 'else', '/',
    );
    protected $_symbols = array(
        '/', '!', '(', ')', ',', '=', '->',
    );
    protected $_strings = array();
    protected $_str_prefix = 'ZAQ_STRING_';
    protected $_phps = array();
    protected $_php_prefix = 'ZAQ_PHP_';
    protected $_contents;
    protected $_code_raw;
    protected $_code_parsed;
    protected $_code_raw_parts = array();
    protected $_code_parsed_parts = array();
    protected $_to_unshift = array();
    protected $_to_push = array();
    protected $_config;
    protected $_exclude_patterns = array(
        'false', 'true', 'null', '\d.*',
    );

    function __construct() {
        $this->CI = & get_instance();
        $this->_set_delimiter_patterns();
        $this->CI->config->load('zaq', TRUE);
        $this->_config = $this->CI->config->item('zaq');
        $this->_check_cache_dir();
    }

    public function parse($view, $data = array(), $return = FALSE) {
        $time_start = microtime(TRUE);

        $view_file = VIEWPATH . $view . '.php';

        if (!is_file($view_file) OR ! is_readable($view_file)) {
            return FALSE;
        }
        
        $this->_zaq_file = md5($view_file);
        $this->_zaq_path = $this->_config['zaq_cache_dir'] . '/' . $this->_zaq_file;

        $this->_contents = file_get_contents($view_file);

        $this->_preserve_phps();
        $this->_parse();
        $this->_restore_phps();
        $this->_fix_reference();

        $vars = $this->CI->load->get_vars();
        $data = array_merge($vars, $this->_get_object_var($data));
        extract($data);
        file_put_contents($this->_zaq_path, $this->_contents);
        $time_end = microtime(TRUE);
        ob_start();
        include $this->_zaq_path;
        $output = ob_get_contents();
        @ob_end_clean();
        unlink($this->_zaq_path);

        log_message('info', 'Zaq: ' . $view . ' parsed in ' . ($time_end - $time_start) . ' seconds.');
        if ($return) {
            return $output;
        }
        $this->CI->output->append_output($output);
    }

    protected function _parse() {
        $this->_contents = preg_replace_callback(
                $this->_l_delimiter_pattern . '(.*?)' . $this->_r_delimiter_pattern, array($this, '_process'), $this->_contents
        );
    }

    protected function _preserve_phps() {
        $this->_phps = array();
        $this->_contents = preg_replace('/\s*\?>/', ' ?>', $this->_contents);
        $this->_contents = str_replace('<?=', '<?php echo ', $this->_contents);
        $this->_contents = preg_replace_callback('/<\?php.*?\?>/si', array($this, '_store_php'), $this->_contents);
    }

    protected function _store_php($matches) {
        $count = count($this->_phps);
        $this->_phps[] = $matches[0];
        return '@@@' . $this->_php_prefix . $count . '@@@';
    }

    protected function _restore_phps() {
        foreach ($this->_phps as $i => $php) {
            $this->_contents = str_replace('@@@' . $this->_php_prefix . $i . '@@@', $php, $this->_contents);
        }
    }

    protected function _fix_reference() {
        $this->_contents = str_replace('$this', '$this->CI', $this->_contents);
    }

    protected function _process($matches) {
        $this->_code_raw = trim($matches[1]);
        $this->_preserve_strings();
        $this->_expand();
        $this->_trim();
        $this->_split();
        $this->_decode();
        $this->_unshift();
        $this->_push();
        if (end($this->_code_parsed_parts) != ':') {
            $this->_code_parsed_parts[] = ';';
        }
        $this->_code_parsed = implode(' ', $this->_code_parsed_parts);
        $this->_restore_strings();
        $this->_contract();
        $this->_code_parsed = trim($this->_code_parsed);
        return '<?php ' . $this->_code_parsed . ' ?>';
    }

    protected function _preserve_strings() {
        $this->_strings = array();
        $this->_code_raw = preg_replace_callback('/([\'\"])(.*?)\1/', array($this, '_store_string'), $this->_code_raw);
    }

    protected function _store_string($matches) {
        $count = count($this->_strings);
        $this->_strings[] = $matches[2];
        return $matches[1] . '@@@' . $this->_str_prefix . $count . '@@@' . $matches[1];
    }

    protected function _restore_strings() {
        foreach ($this->_strings as $i => $str) {
            $this->_code_parsed = str_replace('@@@' . $this->_str_prefix . $i . '@@@', $str, $this->_code_parsed);
        }
    }

    protected function _expand() {
        foreach ($this->_symbols as $symbol) {
            $this->_code_raw = str_replace($symbol, ' ' . $symbol . ' ', $this->_code_raw);
        }
    }

    protected function _trim() {
        $this->_code_raw = preg_replace('/\s+/', ' ', $this->_code_raw);
        $this->_code_raw = trim($this->_code_raw);
    }

    protected function _split() {
        $this->_code_raw_parts = explode(' ', $this->_code_raw);
    }

    protected function _contract() {
        $this->_code_parsed = str_replace('= >', '=>', $this->_code_parsed);
        $this->_code_parsed = str_replace('= =', '==', $this->_code_parsed);
        $this->_code_parsed = str_replace('= =', '==', $this->_code_parsed);
        $this->_code_parsed = str_replace('= =', '==', $this->_code_parsed);
        $this->_code_parsed = str_replace('> =', '>=', $this->_code_parsed);
        $this->_code_parsed = str_replace('< =', '<=', $this->_code_parsed);
        $this->_code_parsed = str_replace('! =', '!=', $this->_code_parsed);
    }

    protected function _decode() {
        $this->_to_unshift = array();
        $this->_to_push = array();
        $pos = 0;
        $this->_code_parsed_parts = array();
        foreach ($this->_code_raw_parts as $p) {
            if (in_array($p, $this->_reserved)) { // reserved code
                $this->_code_parsed_parts[] = $this->_decode_reserved($p, $pos);
            } else if (in_array($p, $this->_symbols)) { // symbol
                $this->_code_parsed_parts[] = $this->_decode_symbol($p, $pos);
            } else {
                $this->_code_parsed_parts[] = $this->_decode_string($p, $pos);
            }
            $pos++;
        }
    }
    
    protected function _unshift() {
        foreach ($this->_to_unshift as $unshift) {
            array_unshift($this->_code_parsed_parts, $unshift);
        }
    }
    
    protected function _push() {
        foreach ($this->_to_push as $push) {
            array_push($this->_code_parsed_parts, $push);
        }
    }

    protected function _decode_reserved($code, $position) {
        if ($code == '/' AND $position == 0) {
            return '';
        }
        if ($code == 'if' || $code == 'foreach') {
            if ($this->_is_end()) {
                return 'end' . $code;
            }
            $this->_to_push[] = ')';
            $this->_to_push[] = ':';
            return $code . ' (';
        }
        if ($code == 'elif') {
            $this->_to_push[] = ')';
            $this->_to_push[] = ':';
            return 'elseif (';
        }
        if ($code == 'else') {
            $this->_to_push[] = ':';
            return $code;
        }
        return $code;
    }

    protected function _is_end() {
        return $this->_code_raw_parts[0] == '/';
    }

    protected function _decode_symbol($code, $position) {
        if ($code == '=') {
            if ($this->_code_raw_parts[0] == 'foreach') {
                return '=>';
            }
        }
        return $code;
    }

    protected function _decode_string($code, $position) {
        $code = preg_replace('/\[([^\'\"]*?)\]/', '[\'$1\']', $code);
        if ($position == 0) {
            $this->_to_unshift[] = 'echo';
        }
        foreach ($this->_exclude_patterns as $ex) {
            if (preg_match('/^' . $ex . '$/i', $code)) {
                return $code;
            }
        }
        if (preg_match('/^\w.*/', $code)) {
            $next = $position + 1;
            $prev = $position - 1;
            if ((isset($this->_code_raw_parts[$next]) AND $this->_code_raw_parts[$next] == '(') OR (isset($this->_code_raw_parts[$prev]) AND $this->_code_raw_parts[$prev] == '->') ) {
                return $code;
            }
            $code = '$' . $code;
        }
        $code = preg_replace('/^#(\w.*)/', '$1', $code);
        return $code;
    }

    protected function _set_delimiter_patterns() {
        $l_delimiter = preg_quote($this->_l_delimiter, '/');
        $this->_l_delimiter_pattern = '/' . $l_delimiter;
        $r_delimiter = preg_quote($this->_r_delimiter, '/');
        $this->_r_delimiter_pattern = $r_delimiter . '/';
    }

    public function set_delimiter($left_delimiter = '{', $right_delimiter = '}') {
        $this->_l_delimiter = $left_delimiter;
        $this->_r_delimiter = $right_delimiter;
        $this->_set_delimiter_patterns();
    }

    protected function _get_object_var($object) {
        return is_object($object) ? get_object_vars($object) : $object;
    }

    protected function _check_cache_dir() {
        if (!is_dir($this->_config['zaq_cache_dir'])) {
            mkdir($this->_config['zaq_cache_dir']);
        }
        if (!is_writable($this->_config['zaq_cache_dir'])) {
            die('Cache directory is not writable.');
        }
    }

}
