<?php

function now() {
    return date('Y-m-d H:i:s');
}

function format_date($date, $format='Y-m-d', $time=false) {
    $dict = ['d','D','l','M','F','m','M','Y','H','i','s','j'];
    $replace = ['%d','%a','%A','%b','%B','%m','%h','%Y','%H','%M','%S','%e'];
    if (!$date || $date == '0000-00-00') return '';
    $t = '';
    if ($time) {
        $arr = explode(' ', $date);
        $t = ' '.$arr[1];
    }

    $date = str_replace("/", '-', $date);
    $format = str_replace($dict, $replace, $format);
    return strftime($format, strtotime($date)).$t;
    return date($format, strtotime($date)).$t;
}

function show_date($date, $format='d/m/Y') {
	$date = substr($date, 0, 10);
	return format_date($date, $format);
}

function show_time($time, $full=false) {
    $length = null;
    if (!$full) $length = 5;
	return substr($time, 11, $length);
}

// receives time string as 00:00 or 00:00:00
function convert_time($time, $convert_to='i') {
    // use php default time notation
    switch ($convert_to) {
        case 'i':// minutes
            $time_arr = explode(':', $time);
            $minutes = 0;
            $minutes += (int)$time_arr[1];
            $minutes += ((int)$time_arr[0]*60);
            return $minutes;
            break;
        case 'H:i':
            $hours = 0;
            $minutes = 0;
            if ($time >= 60) {
                while ($time >= 60) {
                    $hours += 1;
                    $time -= 60;
                }
            }
            $minutes = $time;
            return str_pad($hours, 2, '0', STR_PAD_LEFT).':'.str_pad($minutes, 2, '0', STR_PAD_LEFT);
            break;
        default:
            # code...
            break;
    }
}