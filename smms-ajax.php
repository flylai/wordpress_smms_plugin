<?php

function smms_upload_result()
{
    if (isset($_POST['smms-upload-result'])) {
        $insert_res = 'failed';
        if (smms_insert_db($_POST['smms-upload-result'])) {
            $insert_res = 'success';
        } else {
            $insert_res =  'failed';
        }
        echo $insert_res;
        wp_die();
    }
}

function smms_insert_db($res)
{
    global $wpdb;
    $prefix = $wpdb->prefix;

    if ($res['code']=='success') {
        return $wpdb->insert($prefix . SMMS_TABLENAME, array(
            'filename' => $res['data']['filename'],
            'storename' => $res['data']['storename'],
            'size' => $res['data']['size'],
            'width' => $res['data']['width'],
            'height' => $res['data']['height'],
            'hash' => $res['data']['hash'],
            'path' => $res['data']['path'],
        ));
    }
}

function smms_query_images()
{
    global $wpdb;
    $prefix = $wpdb->prefix;
    $SQL = 'SELECT `id`, `filename`, `hash`, `path`,`time` FROM ' . $prefix .  SMMS_TABLENAME;
    echo json_encode($wpdb->get_results($SQL));
    wp_die();
}

function smms_delete_image()
{
    global $wpdb;
    $prefix = $wpdb->prefix;
    if (!isset($_POST['id'])) {
        echo 'failed';
        return;
    }
    $args = array('id'=> $_POST['id']);
    echo $wpdb->delete($prefix . SMMS_TABLENAME, $args) ? 'success' : 'failed';
    wp_die();
}

add_action('wp_ajax_smms_route', 'smms_ajax_route');
function smms_ajax_route()
{
    $route = '';
    if (isset($_POST['do'])) {
        $route = $_POST['do'];
    }
    switch ($route) {
        case 'upload':
            smms_upload_result();
            break;
        case 'query':
            smms_query_images();
            break;
        case 'delete':
            smms_delete_image();
            break;
        default:
            break;
    }
}
