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

function smms_upload_image_v2()
{
    if(isset($_POST['upload']))
    {
        $wp_upload_dir = wp_upload_dir()['basedir'];
        $upload_file = $wp_upload_dir . '/' . $_FILES['smfile']['name'];
        $smms_settings = get_option('SMMS_OPTION');
        if(move_uploaded_file($_FILES['smfile']['tmp_name'], $wp_upload_dir . '/' . $_FILES['smfile']['name'])) {
            $postdata = array('smfile' => new CURLFile($upload_file));
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https:///sm.ms/api/v2/upload');
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Linux; Android 5.0; SM-G900P Build/LRX21T) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.59 Mobile Safari/537.36');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization:' . $smms_settings['smms_secret_token']));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
            $results = curl_exec($ch);
            smms_insert_db(json_decode($results, true));
            echo $results;
            curl_close($ch);
            unlink($upload_file);
        } else {
            echo 'failed';
        }
    }
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
        case 'upload_v2':
            smms_upload_image_v2();
            break;
        default:
            break;
    }
}
