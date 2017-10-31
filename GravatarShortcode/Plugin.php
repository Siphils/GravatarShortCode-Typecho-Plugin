<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * GravatarShortcode
 * 
 * @package GravatarShortcode
 * @author Siphils
 * @version 1.0.0
 * @link https://github.com/Siphils/GravatarShortcode-Typecho-Plugin
 */
class GravatarShortcode_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Abstract_Contents')->filter = array('GravatarShortcode_Plugin','gravatarFilter');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('GravatarShortcode_Plugin','gravatarParse');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('GravatarShortcode_Plugin','gravatarParse');
        return '启动成功！可以到后台';
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){
        return '禁用成功！';
    }
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        /** 分类名称 */
        $defaultSize = new Typecho_Widget_Helper_Form_Element_Text('defaultSize', NULL, '80', _t('默认尺寸'), _t('给[gravatar]设置默认的头像大小'));
        $form->addInput($defaultSize);
    }
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
    
    /**
     * 插件实现方法
     * 
     * @access public
     * @return void
     */
    public static function render() {}

    /**
     * MD兼容性过滤
     * 
     * @param array $value
     * @return array
     */
    public static function gravatarFilter($value)
    {
        return $value;
    }

    /**
     * 标签替换
     * 
     * @param string $content
     * @return string
     */
    public static function gravatarParse($content,$widget,$lastResult)
    {
        $content = empty($lastResult) ? $content : $lastResult;

        if ($widget instanceof Widget_Archive) {
            if ( false === strpos( $content, '[' ) ) {
                return $content;
            }
            $pattern = self::get_shortcode_regex( array('gravatar') );
            $content = preg_replace_callback("/$pattern/",array('GravatarShortcode_Plugin','parseCallback'), $content);

        }
        return $content;
    }

    /**
     * 回调处理
     * @param unknown $matches
     * @return string
     */
    public static function parseCallback($matches)
    {
        /*
            $mathes array
            * 1 - An extra [ to allow for escaping shortcodes with double [[]]
            * 2 - The shortcode name
            * 3 - The shortcode argument list
            * 4 - The self closing /
            * 5 - The content of a shortcode when it wraps some content.
            * 6 - An extra ] to allow for escaping shortcodes with double [[]]
         */
        if ( $matches[1] == '[' && $matches[6] == ']' ) {
            return substr($matches[0], 1, -1);
        }
        $attr = htmlspecialchars_decode($matches[3]);
        $atts = self::shortcode_parse_atts($attr);
        //从配置中获取默认尺寸参数
        $defaultSize = Typecho_Widget::widget('Widget_Options')->plugin('GravatarShortcode')->defaultSize;
        //图片是否显示为圆形
        $borderRadius = (isset($atts['round'])&&($atts['round']=='true')) ? '50%' : '0';        
        if (isset($atts['email'])){
            //设置了邮箱
            if(isset($atts['size']))   //同时设置了邮箱和图片尺寸
                $imgUrl = self::get_gravatar($atts['email'], $atts['size']);
            else //未设置图片尺寸
                $imgUrl = self::get_gravatar($atts['email'], $defaultSize);
            $gravatarCode = '<img class="plugin-gravatar" src='.$imgUrl.' style="border-radius:'.$borderRadius.';">';
        }
        else {
            //未设置邮箱
            $imgUrl = Helper::options()->pluginUrl.'/GravatarShortcode/default.jpg';
            $gravatarCode = (isset($atts['size'])) ? '<img class="plugin-gravatar" src="'.$imgUrl.'" width="'.$atts['size'].'" style="border-radius:'.$borderRadius.';">' : 
                '<img class="plugin-gravatar" src='.$imgUrl.' width="'.$defaultSize.'" style="border-radius:'.$borderRadius.';">';
        }
        return $gravatarCode;
    }

    /**
     *Get either a Gravatar URL or complete image tag for a specified email address.
     *
     * @param string $email The email address
     * @param string $s Size in pixels, defaults to 80px [ 1 - 2048 ]
     * @param string $d Default imageset to use [ 404 | mm | identicon | monsterid | wavatar ]
     * @param string $r Maximum rating (inclusive) [ g | pg | r | x ]
     * @param boole $img True to return a complete IMG tag False for just the URL
     * @param array $atts Optional, additional key/value attributes to include in the IMG tag
     * @return String containing either just a URL or a complete image tag
     * @source https://gravatar.com/site/implement/images/php/
     */
    private static function get_gravatar( $email, $s = 80, $d = 'mm', $r = 'g', $img = false, $atts = array() ) {
        $url = 'https://www.gravatar.com/avatar/';
        $url .= md5( strtolower( trim( $email ) ) );
        $url .= "?s=$s&d=$d&r=$r";
        if ( $img ) {
            $url = '<img src="' . $url . '"';
            foreach ( $atts as $key => $val )
            $url .= ' ' . $key . '="' . $val . '"';
            $url .= ' />';
        }
        return $url;
    }

    /**
     * Retrieve all attributes from the shortcodes tag.
     *
     * The attributes list has the attribute name as the key and the value of the
     * attribute as the value in the key/value pair. This allows for easier
     * retrieval of the attributes, since all attributes have to be known.
     *
     * @link https://github.com/WordPress/WordPress/blob/master/wp-includes/shortcodes.php
     * @since 2.5.0
     *
     * @param string $text
     * @return array|string List of attribute values.
     *                      Returns empty array if trim( $text ) == '""'.
     *                      Returns empty string if trim( $text ) == ''.
     *                      All other matches are checked for not empty().
     */
    private static function shortcode_parse_atts($text) {
        $atts = array();
        $pattern = '/([\w-]+)\s*=\s*"([^"]*)"(?:\s|$)|([\w-]+)\s*=\s*\'([^\']*)\'(?:\s|$)|([\w-]+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';
        $text = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $text);
        if ( preg_match_all($pattern, $text, $match, PREG_SET_ORDER) ) {
            foreach ($match as $m) {
                if (!empty($m[1]))
                    $atts[strtolower($m[1])] = stripcslashes($m[2]);
                elseif (!empty($m[3]))
                $atts[strtolower($m[3])] = stripcslashes($m[4]);
                elseif (!empty($m[5]))
                $atts[strtolower($m[5])] = stripcslashes($m[6]);
                elseif (isset($m[7]) && strlen($m[7]))
                $atts[] = stripcslashes($m[7]);
                elseif (isset($m[8]))
                $atts[] = stripcslashes($m[8]);
            }
    
            // Reject any unclosed HTML elements
            foreach( $atts as &$value ) {
                if ( false !== strpos( $value, '<' ) ) {
                    if ( 1 !== preg_match( '/^[^<]*+(?:<[^>]*+>[^<]*+)*+$/', $value ) ) {
                        $value = '';
                    }
                }
            }
        } else {
            $atts = ltrim($text);
        }
        return $atts;
    }

    
    /**
     * Retrieve the shortcode regular expression for searching.
     *
     * The regular expression combines the shortcode tags in the regular expression
     * in a regex class.
     *
     * The regular expression contains 6 different sub matches to help with parsing.
     *
     * 1 - An extra [ to allow for escaping shortcodes with double [[]]
     * 2 - The shortcode name
     * 3 - The shortcode argument list
     * 4 - The self closing /
     * 5 - The content of a shortcode when it wraps some content.
     * 6 - An extra ] to allow for escaping shortcodes with double [[]]
     *
     * @link https://github.com/WordPress/WordPress/blob/master/wp-includes/shortcodes.php
     * @since 2.5.0
     *
     *
     * @param array $tagnames List of shortcodes to find. Optional. Defaults to all registered shortcodes.
     * @return string The shortcode search regular expression
     */
    private static function get_shortcode_regex( $tagnames = null ) {
        $tagregexp = join( '|', array_map('preg_quote', $tagnames) );
    
        // WARNING! Do not change this regex without changing do_shortcode_tag() and strip_shortcode_tag()
        // Also, see shortcode_unautop() and shortcode.js.
        return
        '\\['                              // Opening bracket
        . '(\\[?)'                           // 1: Optional second opening bracket for escaping shortcodes: [[tag]]
        . "($tagregexp)"                     // 2: Shortcode name
        . '(?![\\w-])'                       // Not followed by word character or hyphen
        . '('                                // 3: Unroll the loop: Inside the opening shortcode tag
        .     '[^\\]\\/]*'                   // Not a closing bracket or forward slash
        .     '(?:'
        .         '\\/(?!\\])'               // A forward slash not followed by a closing bracket
        .         '[^\\]\\/]*'               // Not a closing bracket or forward slash
        .     ')*?'
        . ')'
        . '(?:'
        .     '(\\/)'                        // 4: Self closing tag ...
        .     '\\]'                          // ... and closing bracket
        . '|'
        .     '\\]'                          // Closing bracket
        .     '(?:'
        .         '('                        // 5: Unroll the loop: Optionally, anything between the opening and closing shortcode tags
        .             '[^\\[]*+'             // Not an opening bracket
        .             '(?:'
        . '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag
        .                 '[^\\[]*+'         // Not an opening bracket
        .             ')*+'
        .         ')'
        .         '\\[\\/\\2\\]'             // Closing shortcode tag
        .     ')?'
        . ')'
        . '(\\]?)';                          // 6: Optional second closing brocket for escaping shortcodes: [[tag]]
    }
}
