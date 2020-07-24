<?php
use Core\App_Model as App_Model;
use Core\App_Controller as App_Controller;
use Core\Helpers\Sql as Sql;

// filter post
global $__post;
$__post = filterInputData($__post);

// file: app/helpers/default_helper.php

function getMeta() {
    $controller = new App_Controller();
    $meta = [];
    if ($meta_items = $controller->load()->model('Meta')->retrieve('all')) {
        foreach ($meta_items as $key => $value) {
            $meta[$value['data_type']] = $value['data_value'];
        }
    }
    return $meta;
}

// filter content format for arrays, useful for post and get
function filterInputData($data) {
    if (count($data) <= 0) {
        return $data;
    }

    $return = [];

    foreach ($data as $key => $value) {
        $return[$key] = filterVar($value);
    }

    return $return;
}

function filterVar($var) {
    $return = $var;

    if (is_array($var)) {
        $return = filterInputData($var);
    }
    // test for money format: 1.280,23 or 35,50 etc
    else if (preg_match("/^(([0-9]\.?){1,})?(\,[0-9]{1,2})$/", $var)) {
        // number_format doesn't work as intended
        $return = str_replace('.', '', $var);
        $return = str_replace(',', '.', $return);
        $return = (double)$return;
    }
    // test for date dd/mm/yyyy
    else if (preg_match("/^([0-9]{2}\/){2}([0-9]{4})$/", $var)) {
        $return = format_date($var, 'Y-m-d');
    }

    return $return;
}

function is_printable($device_id) {
    return false;
    $db = new Sql(get_pdo());
    $db->array_only = false;
    $db->select("*");
    $db->from("Settings");
    $db->where("meta_param = 'print_list'");
    $db->limit(1);
    if (!$settings = $db->run()) {
        return false;
    }

    $print_list = json_decode($settings->meta_value, true);
    if (array_key_exists($device_id, $print_list) && ($print_list[$device_id] == 1)) {
        return true;
    }
    else {
        return false;
    }
}

function is_valid($post, $fields) {
    $opts = array(
        'int' => FILTER_VALIDATE_INT,
        'float' => FILTER_VALIDATE_FLOAT,
        'bool' => FILTER_VALIDATE_BOOLEAN,
        'email' => FILTER_VALIDATE_EMAIL
    );
    $errors = 0;
    foreach ($fields as $key => $value) {
        if (array_key_exists($key, $opts)) {
            foreach ($value as $var) {
                if (!filter_var($post[$var], $opts[$key])) {
                    $errors++;
                }
            }
        }
        else {
            foreach ($value as $var) {
                if ($key == 'object') {
                    if (!is_object($post[$var])) {
                        $errors++;
                    }
                }
                else if ($key == 'array') {
                    if (!is_array($post[$var])) {
                        $errors++;
                    }
                }
                if (!array_key_exists($var, $post)) {
                    $errors++;
                }
                else if ($post[$var] == '') {
                    $errors++;
                }
            }
        }
    }
    if ($errors > 0) {
        return false;
    }
    else {
        return true;
    }
}

function stringContainArrayValue($array, $string) {
    foreach ($array as $key => $value) {
        if (strstr($string, $value)) {
            return $value;
        }
    }
    return false;
}

function mask($mask,$str){

    $str = str_replace(" ","",$str);

    for($i=0;$i<strlen($str);$i++){
        $mask[strpos($mask,"#")] = $str[$i];
    }

    return $mask;

}

function formata_chave($str) {
    $str = str_replace('NFe', '', $str);
    $array = str_split($str);
    $return = '';
    foreach ($array as $key => $value) {
        $return .= $value;
        if (($key+1)%4==0) {
            $return .= ' ';
        }
        
    }
    return $return;
}

function validate_form() {
    global $CONFIG;

    if (count($CONFIG['msg']['error'])) {
        return false;
    }

    return true;
}

// defines the page title. returns bool.
function head($title = '') {
    $title = $title == '' ? '' : $title . ' - ';
    echo '<title>' . $title . SYSTEM_NAME . '</title>';
    include (DOCROOT . '/app/views/templates/head.php');
    return true;
}

// returns PDO object
function get_pdo() {
    $app = new App_Model();
    return $app->connect();
}

function get_template($template, $params = array()) {
    $_language_file = DOCROOT.DS.'app'.DS.'languages'.DS.LANGUAGE.'.php';
    if (file_exists($_language_file)) {
     include ($_language_file);
    }
    else {
     die('Language file not found');
    }
    // extract params
    if ($params) {
        extract($params);
        unset($params);
    }
    include(DOCROOT . '/app/views/templates/' . $template . '.phtml');
}

// display messages from $CONFIG['msg']. retrns nothing.
function display_messages() {
    return false;
    global $CONFIG;
    if (count($CONFIG['msg']['success'])) {
        echo '<div class="alert alert-success alert-dismissable">';
        echo '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">Ã—</button>';
        echo '<h4><i class="icon fa fa-check"></i> Sucesso!</h4>';
        foreach ($CONFIG['msg']['success'] as $msg) {
            echo "<p>{$msg}</p>";
        }
        echo '</div>';
    }
    if (count($CONFIG['msg']['error'])) {
        echo '<div class="alert alert-danger alert-dismissable">';
        echo '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">Ã—</button>';
        echo '<h4><i class="icon fa fa-ban"></i> Erro!</h4>';
        foreach ($CONFIG['msg']['error'] as $msg) {
            echo "<p>{$msg}</p>";
        }
        echo '</div>';
    }
    if (count($CONFIG['msg']['info'])) {
        echo '<div class="alert alert-info alert-dismissable">';
        echo '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">Ã—</button>';
        echo '<h4><i class="icon fa fa-info"></i> Info!</h4>';
        foreach ($CONFIG['msg']['info'] as $msg) {
            echo "<p>{$msg}</p>";
        }
        echo '</div>';
    }
    if (count($CONFIG['msg']['alert'])) {
        echo '<div class="alert alert-warning alert-dismissable">';
        echo '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">Ã—</button>';
        echo '<h4><i class="icon fa fa-warning"></i> Atencao!</h4>';
        foreach ($CONFIG['msg']['alert'] as $msg) {
            echo "<p>{$msg}</p>";
        }
        echo '</div>';
    }
}

// display messages from $CONFIG['msg']. returns nothing.
function display_messages2() {
    global $CONFIG;
    if (count($CONFIG['msg']['success'])) {
        foreach ($CONFIG['msg']['success'] as $title => $msg) {
            $_title = 'Sucesso!!!';
            if (!is_integer($title)) {
                $_title = $title;
            }
            echo "
            $.gritter.add({
                title: '".$_title."',
                text: '".$msg."',
                image: '".WEBROOT."/plugins/gritter/images/success.png',
                sticky: false,
                time: 5000,
                class_name: 'sticky-success'
            });
            ";
        }
    }
    if (count($CONFIG['msg']['error'])) {
        foreach ($CONFIG['msg']['error'] as $title => $msg) {
            $_title = 'Erro';
            if (!is_integer($title)) {
                $_title = $title;
            }
            echo "
            $.gritter.add({
                title: '".$_title."',
                text: '".$msg."',
                image: '".WEBROOT."/plugins/gritter/images/error.png',
                sticky: false,
                time: 5000,
                class_name: 'sticky-error'
            });
            ";
        }
    }
    if (count($CONFIG['msg']['info'])) {
        foreach ($CONFIG['msg']['info'] as $title => $msg) {
            $_title = 'InformaÃ§Ã£o';
            if (!is_integer($title)) {
                $_title = $title;
            }
            echo "
            $.gritter.add({
                title: '".$_title."',
                text: '".$msg."',
                image: '".WEBROOT."/plugins/gritter/images/like.png',
                sticky: false,
                time: 5000,
                class_name: 'sticky-info'
            });
            ";
        }
    }
    if (count($CONFIG['msg']['alert'])) {
        foreach ($CONFIG['msg']['alert'] as $title => $msg) {
            $_title = 'AtenÃ§Ã£o!';
            if (!is_integer($title)) {
                $_title = $title;
            }
            echo "
            $.gritter.add({
                title: '".$_title."',
                text: '".$msg."',
                image: '".WEBROOT."/img/icons/warning.png',
                sticky: false,
                time: 5000,
                class_name: 'sticky-alert'
            });
            ";
        }
    }
}

// throws error page. returns void.
function error_page($error_code = '404') {
    $file = DOCROOT . '/app/views/errors/' . $error_code . '.php';
    if (file_exists($file)) {
        include ($file);
        exit();
    }
}

// generates strong random id. returns int.
function crypto_rand_secure($min, $max) {
    $range = $max - $min;
    if ($range < 0)
        return $min; // not so random...
    $log = log($range, 2);
    $bytes = (int) ($log / 8) + 1; // length in bytes
    $bits = (int) $log + 1; // length in bits
    $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
    do {
        $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
        $rnd = $rnd & $filter; // discard irrelevant bits
    } while ($rnd >= $range);
    return $min + $rnd;
}

// generates unique token. returns string.
function get_token($length = 22, $upper = true) {
    $token = "";
    //$codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $codeAlphabet = "abcdefghijklmnopqrstuvwxyz";
    $codeAlphabet.= "123456789";
    for ($i = 0; $i < $length; $i++) {
        $token .= $codeAlphabet[crypto_rand_secure(0, strlen($codeAlphabet))];
    }
    if ($upper)
        $token = strtoupper($token);
    return $token;
}

// redirects to url. returns void.
function redirect_to($url) {
    header('location:' . WWWROOT . '/' . $url);
}

// manages pages for logged user needs. returns void.
function auth($op = '', $redirect_to = '') {
    switch ($op) {
        case 'yes':
            if (!@$_SESSION['app']['user']) {
                if (!isset($_COOKIE['user_id'])) {
                    redirect_to('logout');
                } else { // log the user back in
                    $db = new Sql(get_pdo());
                    $db->array_only = true;
                    $db->select("*");
                    $db->from("Users");
                    $db->where("id = '" . $_COOKIE['user_id'] . "'");
                    $result = $db->run();
                    if (isset($result)) {
                        $_SESSION['app'] = array(
                            'user' => array(
                                'id' => $result[0]['id'],
                                'name' => $result[0]['name'],
                                'email' => $result[0]['email'],
                            )
                        );
                        //redirect_to('projects');
                    } else {
                        redirect_to('logout');
                    }
                }
            }
            break;
        case 'no':
            if (@$_SESSION['app'])
                redirect_to($redirect_to);
            break;
        default:
            break;
    }
}

// gives access control by role to the page
// assumes user is logged in
function access_control($role) {
    switch ($role) {
        case 'admin':
            if (!@$_SESSION['app']['company']['admin'] != '1') {
                redirect_to();
            }
            break;
        default:
            # code...
            break;
    }
}

// search arrays recursevely. returns bool.
function array_rsearch($needle, $haystack) {
    foreach ($haystack as $key => $value) {
        $current_key = $key;
        if ($needle === $value OR ( is_array($value) && array_rsearch($needle, $value) !== false)) {
            return $current_key;
        }
    }
    return false;
}

// generates class name. returns string.
function get_class_name($string) {
    $inf = new Inflector();
    $plural = ucwords($inf->pluralize($string));
    return $plural . '_model';
}

function format_money($value, $symbol = 'R$ ') {
    $meta = getMeta();
    $currency = $meta['currency'];
    if (!$value) {
        $value = 0;
    }
    if (!is_numeric($value)) {
        return $value;
    }
    if ($symbol) {
        $symbol = $currency;
    }
    return $symbol . number_format(clear_pointer($value), 2, ',', '.');
}

function send_email($subject, $body, $to) {
    $mail = new PHPMailer;

    $mail->isSMTP();                                      // Set mailer to use SMTP
    $mail->Host = 'smtp.mandrillapp.com';  // Specify main and backup SMTP servers
    $mail->SMTPAuth = true;                               // Enable SMTP authentication
    $mail->Username = 'cicerogeorge@gmail.com';                 // SMTP username
    $mail->Password = '4GglbQ9spLxYzUtM9qF4Yw';                           // SMTP password
    $mail->SMTPSecure = 'tls';                            // Enable encryption, 'ssl' also accepted
    $mail->Port = 587;
    $mail->CharSet = 'UTF8';

    $mail->From = 'sistema@audiosort.com';
    $mail->FromName = 'Audiosort';
    $mail->addAddress($to['email'], $to['name']);     // Add a recipient
    $mail->addReplyTo('sistema@audiosort.com', 'NÃ£o Responda');

    $mail->WordWrap = 50;                                 // Set word wrap to 50 characters
    $mail->isHTML(true);                                  // Set email format to HTML

    $mail->Subject = $subject;
    $mail->Body = $body;

    if (!$mail->send()) {
        // echo 'Message could not be sent.<br>';
        // echo 'Mailer Error: ' . $mail->ErrorInfo;
        return false;
    } else {
        // echo 'Message has been sent';
        return true;
    }
}

function get_http_codes() {
    return array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        449 => 'Retry With',
        450 => 'Blocked by Windows Parental Controls',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        509 => 'Bandwidth Limit Exceeded',
        510 => 'Not Extended',
        601 => 'Print server not found',
        602 => 'Custom Status',
        603 => 'Custom Status',
        604 => 'Custom Status',
        605 => 'Custom Status',
    );
}

function clear_pointer($value) {
    if (preg_match("/^([0-9]\.?){1,}\,([0-9]{1,})$/", $value)) {
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
    }
    return $value;
}

function shortPrint($str, $len=10) {
    if (strlen($str) > $len) {
        return substr($str, 0, $len) . '...';
    }
    else {
        return $str;
    }
}

function _dump($var, $die= false) {
    echo '<pre style="z-index: 9999; display: block;">';
    var_dump($var);
    echo '</pre>';
    if ($die) die();
}

function core_errors($e) {
    // _dump($e);
    global $core_errors;
    if (is_object($e)) {
        $trace = $e->getTrace();
        $html = '<div class="core-error">DEVELOPMENT MODE - CORE ERROR:<br>';
        $html .= $e->getMessage() . '<br />';
        $html .= $trace[1]['file'] . '<br />';
        $html .= 'Line ' . $trace[1]['line'] . '<br />';
        $html .= '</div>';

        echo $html;
    }
}

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function generateHexToken($length = 10) {
    $characters = '0123456789abcdef';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function parms($string,$data) {
    $indexed=$data==array_values($data);
    foreach($data as $k=>$v) {
        if(is_string($v)) $v="'$v'";
        if($indexed) $string=preg_replace('/\?/',$v,$string,1);
        else $string=str_replace(":$k",$v,$string);
    }
    return $string;
}

function set_active($url_list) {
    $active = '';

    foreach ($url_list as $key => $value) {
        if (strstr($_SERVER['REQUEST_URI'], $value)) {
            $active = ' active';
        }
    }

    return $active;
}

function get_pagination($total_items, $current_page, $pace, $deviation=4) {
    $total_pages = ceil($total_items / $pace);

    $start = $current_page - $deviation;
    $end = $current_page + $deviation;

    if ($start <= 0) {
        $end += $start;
        $start = 1;
    }
    else if ($end >= $total_pages) {
        $start -= $end - $total_pages;
        $end = $total_pages;
    }

    if ($end > $total_pages) {
        $end = $total_pages;
    }

    $html = '<nav><ul class="pagination" style="margin-bottom: 0;">';

    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
        if ($total_pages > 10) {
            $html .= '<li class="page-item" aria-current="page"><span class="page-link">...</span></li>';
        }
    }
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $current_page) {
            $html .= '<li class="page-item active" aria-current="page"><span class="page-link">';
            $html .= $i;
            $html .= '<span class="sr-only">(current)</span></span></li>';
        }
        else { 
            $html .= '<li class="page-item"><a class="page-link" href="?page='.$i.'">'.$i.'</a></li>';
        }
    }
    if ($end < $total_pages) {
        if ($total_pages > 10) {
            $html .= '<li class="page-item" aria-current="page"><span class="page-link">...</span></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="?page='.$total_pages.'">'.$total_pages.'</a></li>';
    }

    $html .= '</ul></nav>';

    echo $html;
}

function httpPOST($url, $fields, $json=false) {
    // set proper cookies
    $cookies_string = $sep = '';
    // foreach ($_COOKIE as $key => $value) {
    //     $cookies_string .= $key == 'ASP_NET_SessionId' ? $sep.'ASP.NET_SessionId='.$value : $sep.$key.'='.$value;
    //     $sep = ';';
    // }

    // set header options
    $opts = array(
        "Accept-language: en",
        "Cookie: ".$cookies_string."",
        "User-agent: Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.101 Safari/537.36"
    );

    if ($json == true) {
        $fields_string = json_encode($fields);
        $opts[] = "Content-Type: application/json";
        $opts[] = "Content-Length: " . strlen($fields_string);
    }
    else {
        $fields_string = http_build_query($fields);
    }

    //open connection
    $ch = curl_init();

    //set the url, number of POST vars, POST data
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, count($fields));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $opts);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    //execute post
    $response = curl_exec($ch);

    //close connection
    curl_close($ch);
    return $response;
}


