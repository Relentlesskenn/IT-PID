<?php

function check_rate_limit($key, $limit, $time_frame) {
    $current_time = time();
    $ip = $_SERVER['REMOTE_ADDR'];
    
    if (!isset($_SESSION['rate_limits'])) {
        $_SESSION['rate_limits'] = [];
    }
    
    $rate_key = "{$key}:{$ip}";
    
    if (!isset($_SESSION['rate_limits'][$rate_key])) {
        $_SESSION['rate_limits'][$rate_key] = ['count' => 1, 'expire' => $current_time + $time_frame];
        return true;
    }
    
    $rate_data = &$_SESSION['rate_limits'][$rate_key];
    
    if ($current_time > $rate_data['expire']) {
        $rate_data = ['count' => 1, 'expire' => $current_time + $time_frame];
        return true;
    }
    
    if ($rate_data['count'] < $limit) {
        $rate_data['count']++;
        return true;
    }
    
    return false;
}

?>