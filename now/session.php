<?php
namespace now;

/**
 * 简单的session封装
 * @author      欧远宁
 * @version     1.0.0
 * @copyright   CopyRight By 欧远宁
 * @package     now
 */
final class session {
    
    /**
     * 密钥，使用前请修改
     * @var string
     */
    public static $secret = '';
    
    /**
     * 是否将session信息放到cookie中，如果是OnePageOneApplication的方式，
     * 建议不要放到cookie中，而是每次放到请求参数中
     * @var bool
     */
    public static $to_cookie = FALSE;
    
    /**
     * 存放到cookie的时候，这个cookie的名字
     * @var string
     */
    private static $cookie_name = 'nnss';
    
    /**
     * session中保存的数据内容
     * @var array
     */
    private static $sessions = array();
    
    /**
     * 随机数
     * @var string
     */
    public static $rand = '';
    
    /**
     * 初始化session
     * @static
     * @author 欧远宁
     * @param string $uid 用户id
     */
    public static function init_session($para=array()){
    	self::$rand = microtime(TRUE).'';
    	return self::make_session($para);
    }
    
    
    /**
     * 根据参数，生成一个加密的session字符串
     * 以后每次请求都需要传递此字符串，以便确认用户身份
     * @static
     * @author 欧远宁
     * @param string $uid 用户id
     */
    private static function make_session($para=array()) {
        $secret = self::$secret;
        $rand = self::$rand;
        
        $data = serialize($para);
        $ck = md5($rand.$data.$secret);
        
        $str = $rand.'|'.$ck.'|'.$data;
        $str = base64_encode(xxtea::encrypt($str, md5($secret)));
        if (self::$to_cookie){
            $cfg = $GLOBALS['cfg']['cfg']['session']['cookie'];
            if (VER != 'pro' && stripos($_SERVER['HTTP_USER_AGENT'], 'MSIE')){
            	if (ip2long($cfg['domain']) || stripos($cfg['domain'], '.') === FALSE){
            		$cfg['domain'] = '';
            	}
            }
            header('P3P: CP=CAO PSA OUR');
            setcookie(self::$cookie_name, $str, $cfg['expire'], $cfg['path'], $cfg['domain'], $cfg['secure'], $cfg['httponly']);
        }
        self::$sessions = $para;
        return $str;
    }
    
    /**
     * 得到当前session的字符串，只在使用cookie保存session时候有效
     * @static
     * @return string
     */
    public static function get_session_str(){
    	if (self::$to_cookie){
    		if(isset($_COOKIE[self::$cookie_name])){
    			return $_COOKIE[self::$cookie_name];
    		}
    	}
    	return '';
    }
    
    /**
     * 获取并验证当前登录用户的session信息
     * @static
     * @author 欧远宁
     * @param string $session session字符串
     */
    public static function get_session($session='') {
    	if (is_string($session) && $session != ''){
    		//do nothing
    	} else if (self::$to_cookie){
        	$new_sess = '';
            if(isset($_COOKIE[self::$cookie_name])){
                $new_sess = $_COOKIE[self::$cookie_name];
                unset($_COOKIE[self::$cookie_name]);
            }
	        if ($new_sess == '' && is_array($session) && isset($session[self::$cookie_name])){
	        	$new_sess = $session[self::$cookie_name];
	        }
	        $session = $new_sess;
        }
        $secret = self::$secret;
        
        $sess = xxtea::decrypt(base64_decode($session), md5($secret));
        $arr = explode('|', $sess, 3);
        
        if (count($arr) != 3) {
            return;
        }
        
        $str = md5($arr[0].$arr[2].$secret);
        if ($str == $arr[1]) {
            self::$sessions = unserialize($arr[2]);
            self::$rand = $arr[0];
        }
    }
    
    /**
     * 得到session中某个key的值
     * @static
     * @author 欧远宁
     * @param string $key
     */
    public static function get($key){
        if(key_exists($key, self::$sessions)){
            return self::$sessions[$key];
        } else {
            return null;
        }
    }
    
    /**
     * 得到当前session的所有值
     */
    public static function all(){
    	return self::$sessions;
    }
    
    /**
     * 给当前session增加一个key
     * @param string $key
     * @param any $val
     */
    public static function add($key, $val){
    	if (self::$rand == ''){
//     		throw new err('session 尚未初始化');
    		self::init_session(array($key=>$val));
    	} else {
    		self::$sessions[$key] = $val;
    		self::make_session(self::$sessions);
    	}
    }
    
}