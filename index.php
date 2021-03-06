<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category   Mage
 * @package    Mage
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

if (version_compare(phpversion(), '5.2.0', '<') === true) {
    echo '<div style="font:12px/1.35em arial, helvetica, sans-serif;"><div style="margin:0 0 25px 0; border-bottom:1px solid #ccc;"><h3 style="margin:0; font-size:1.7em; font-weight:normal; text-transform:none; text-align:left; color:#2f2f2f;">Whoops, it looks like you have an invalid PHP version.</h3></div><p>Magento supports PHP 5.2.0 or newer. <a href="http://www.magentocommerce.com/install" target="">Find out</a> how to install</a> Magento using PHP-CGI as a work-around.</p></div>';
    exit;
}

/**
 * Error reporting
 */
error_reporting(E_ALL | E_STRICT);

/**
 * Compilation includes configuration file
 */
$compilerConfig = 'includes/config.php';
if (file_exists($compilerConfig)) {
    include $compilerConfig;
}

$mageFilename = 'app/Mage.php';
$maintenanceFile = 'maintenance.flag';

if (!file_exists($mageFilename)) {
    if (is_dir('downloader')) {
        header("Location: downloader");
    } else {
        echo $mageFilename . " was not found";
    }
    exit;
}

if (file_exists($maintenanceFile)) {
    include_once dirname(__FILE__) . '/errors/503.php';
    exit;
}

################################################ Hln35 FPC Settings begin ##############################
#是否开启 FPC
const HLN35_FPC_ENABLE = true;

#是否开启调试
const HLN35_FPC_DEBUG = true;

#cache 过期时间
const HLN35_FPC_CACHE_EXPIRE = 43200;

#cache adapter 当前可选 apc 或者 filesystem 默认是 filesystem
const HLN35_FPC_CACHE_ADAPTER = 'filesystem';

#cache 存放目录的绝对路径，推荐使用绝对路径
const HLN35_FPC_CACHE_DIR_ABS = '';

#cache 存放目录的相对路径，注意: 为了提高性能，需要人工确定目录是否存在以及是否可读写，不会自动创建目录
const HLN35_FPC_CACHE_DIR_REL = 'var/cache/fpc';

#当前是否已经在 .htaccess 中使用了 url rewrite
const HLN35_FPC_IS_URL_WRITE_OPEN = false;

#no cache filters
$Hln35_FPC_NO_CACHE_FILTERS = array(
    // add filters here
);
################################################ Hln35 FPC Settings end ##############################


################################################ Hln35 FPC Body start ################################
if (HLN35_FPC_DEBUG) {
    date_default_timezone_set('Asia/Shanghai');

// uncomment below to debug index.php when you get a white screen
//    register_shutdown_function(function () {
//        if (($err = error_get_last()) !== null)
//            var_dump($err);
//    });

    function rLog()
    {
        file_put_contents('fpc.log', implode(',', func_get_args()) . "\n", FILE_APPEND);
    }
}

const _DS_ = DIRECTORY_SEPARATOR;

//below is magento default startup logic
function _defaultMageStartup()
{
    global $mageFilename;
    require_once $mageFilename;

    #Varien_Profiler::enable();

    if (isset($_SERVER['MAGE_IS_DEVELOPER_MODE'])) {
        Mage::setIsDeveloperMode(true);
    }

    #ini_set('display_errors', 1);

    umask(0);

    /* Store or website code */
    $mageRunCode = isset($_SERVER['MAGE_RUN_CODE']) ? $_SERVER['MAGE_RUN_CODE'] : '';

    /* Run store or run website */
    $mageRunType = isset($_SERVER['MAGE_RUN_TYPE']) ? $_SERVER['MAGE_RUN_TYPE'] : 'store';

    Mage::run($mageRunCode, $mageRunType);
}

function _getNoCacheOutput()
{
    global $mageFilename;
    ob_start();
    _defaultMageStartup();
    $ret = ob_get_contents();
    ob_end_clean();
    return $ret;
}

function _getRequestPathInfo()
{
    if (isset($_SERVER['PATH_INFO'])) {
        return trim($_SERVER['PATH_INFO'], '/');
    }

    $path = trim($_SERVER['REQUEST_URI'], '/');
    $self = trim($_SERVER['PHP_SELF'], '/');

    if (($idx = strpos($path, '?')) !== false) {
        $path = substr($path, 0, $idx);
    }

    $rootDir = dirname($self);
    if (($idx = strrpos($path, $rootDir)) === 0) {
        $path = substr($path, strlen($rootDir));
    }

    if (($idx = strpos($path, 'index.php')) !== false) {
        $path = substr($path, strlen('index.php'));
    }

    return trim($path, '/');
}

function _parseModelControllerAction()
{
    $requestPath = _getRequestPathInfo();
    $parts = explode('/', $requestPath);

    return array(
        'm' => !empty($parts[0]) ? $parts[0] : null,
        'c' => !empty($parts[1]) ? $parts[1] : null,
        'a' => !empty($parts[2]) ? $parts[2] : null,
        'count' => count($parts)
    );
}

function _isOutOfCache()
{
    global $Hln35_FPC_NO_CACHE_FILTERS;
    $mca = _parseModelControllerAction();

    if ($mca['count'] === 0 || $mca['m'] === null)
        return false;

    foreach ($Hln35_FPC_NO_CACHE_FILTERS as $filter) {
        if ($mca['m'] === $filter['m']) {
            if ($filter['c'] === '*')
                return true;

            $cColl = explode('|', $filter['c']);
            if (!in_array($mca['c'], $cColl))
                return false;

            if ($filter['a'] === '*')
                return true;

            $aColl = explode('|', $filter['a']);
            return in_array($mca['a'], $aColl);
        }
    }

    return false;
}

if (!function_exists('getallheaders')) {
    function getallheaders()
    {
        $headers = '';
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

$requestUri = $_SERVER['REQUEST_URI'];
$headers = getallheaders();
$isAjax = isset($headers['X-Requested-With']) && strtolower($headers['X-Requested-With']) === 'xmlhttprequest';

$userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : false;
$isIPhone = $userAgent && strpos($userAgent, 'iPhone') !== false;
$isAndroid = $userAgent && strpos($userAgent, 'Android') !== false;

$isMobile = $isIPhone || $isAndroid;

if (HLN35_FPC_DEBUG) {
    rLog(
        HLN35_FPC_ENABLE,                                                    // fpc_enable
        date('Y-m-d H:i:s', filectime(__FILE__)),                            // idx_ctime
        $requestUri,                                                         // request_uri
        _getRequestPathInfo(),                                               // path_info
        date('Y-m-d H:i:s', time()),                                         // request_time
        $_SERVER['SERVER_ADDR'],                                             // server_addr
        $isAjax,                                                             // is_ajax
        $isMobile,                                                           // is_mobile
        _isOutOfCache(),                                                     // is_out_of_cache
        HLN35_FPC_ENABLE && !$isAjax && !$isMobile && !_isOutOfCache()       // all_cond_union
    );
}

if (HLN35_FPC_ENABLE && !$isAjax && !$isMobile && !_isOutOfCache()) {
    $output = '';

    switch (HLN35_FPC_CACHE_ADAPTER) {
        case 'apc':
            if (extension_loaded('apc') && ini_get('apc.enabled')) {
                $key = urlencode($requestUri);
                $needReCache = true;
                $cached = null;

                if (apc_exists($key)) {
                    $cached = apc_fetch($key);
                    if (is_array($cached) && isset($cached['added_time'])) {
                        $addedTime = $cached['added_time'];
                        $needReCache = $addedTime + HLN35_FPC_CACHE_EXPIRE < time();
                    }
                }

                if ($needReCache) {
                    $cached = array(
                        'added_time' => time(),
                        'content' => _getNoCacheOutput()
                    );

                    apc_add($key, $cached);
                }

                $output = $cached['content'];
            } else {
                trigger_error('apc has not been installed or disabled', E_ERROR);
            }
            break;
        case 'filesystem':
        default:
            $cacheDir = HLN35_FPC_CACHE_DIR_ABS ? HLN35_FPC_CACHE_DIR_ABS : realpath(HLN35_FPC_CACHE_DIR_REL);
            $cacheFile = $cacheDir . _DS_ . urlencode($requestUri);
            $lastChangeTime = file_exists($cacheFile) ? filectime($cacheFile) : false;

            if ($lastChangeTime !== false && $lastChangeTime + HLN35_FPC_CACHE_EXPIRE >= time()) {
                $output = file_get_contents($cacheFile);
            } else {
                $output = _getNoCacheOutput();
                file_put_contents($cacheFile, $output);
            }

            break;
    }

    echo $output;
    exit;
} else {
    _defaultMageStartup();
}
################################################ Hln35 FPC Body end ################################