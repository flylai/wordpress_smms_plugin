<?php
/*
Plugin Name: SM.MS图床工具
Plugin URI: https://flylai.com/587.html
Description: 通过使用SM.MS的API，支持在文章编辑时上传插入管理图片。
Author: flylai
Author URI: https://flylai.com/
Version: 1.0
*/


define('SMMS_URL', plugin_dir_url(__FILE__));
define('SMMS_VERSION', "1.0");
define('SMMS_TABLENAME', 'smms_image');

require(dirname(__FILE__) . '/smms-ajax.php');

// 身份认证
add_action('admin_init', 'smms_authentication');
function smms_authentication()
{
    if (!is_admin() || !current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
}

// 功能启用

function smms_install()
{
    global $wpdb;
    $prefix = $wpdb->prefix;
    $SQL = 'CREATE TABLE IF NOT EXISTS ' . $prefix . SMMS_TABLENAME . ' (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        filename TEXT NOT NULL,
        storename TEXT NOT NULL,
        size INT NOT NULL,
        width INT NOT NULL,
        height INT NOT NULL,
        hash TEXT NOT NULL,
        path TEXT NOT NULL,
        time TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY(id) )';
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($SQL);
}

// css js文件
function smms_load_assets()
{
    wp_enqueue_style('smms_css', SMMS_URL . 'assets/main.css');
    wp_enqueue_script('smms_js', SMMS_URL . 'assets/main.js');
}

// 模态框显示
add_action('admin_footer', 'smms_modal');//wp_after_admin_bar_render
function smms_modal()
{
    global $hook_suffix;
    if ($hook_suffix != 'post-new.php' && $hook_suffix != 'post.php') { //非文章编辑界面不加载
        return;
    }
    $smms_settings = get_option('SMMS_OPTION');
    echo "<script>var smms_server_domain = 'https://{$smms_settings['smms_server_domain']}'; var smms_secret_token = '{$smms_settings['smms_secret_token']}'; </script>"; ?>
<div class="smms-container">
    <div class="media-modal wp-core-ui">
        <button type="button" id="smms-modal-close" class="media-modal-close"><span class="media-modal-icon"><span
                    class="screen-reader-text">关闭媒体面板</span></span></button>
        <div class="media-modal-content">
            <div class="media-frame-title">
                <h1>上传文件到sm.ms<span class="dashicons dashicons-arrow-down"></span></h1>
            </div>
            <div class="media-frame-router">
                <div class="media-router smms-router">
                    <a href="#" id="smms-upload" class="media-menu-item smms-menu-item">上传文件</a>
                    <a href="#" id="smms-uploaded" class="media-menu-item smms-menu-item">媒体库</a>
                </div>
            </div>
            <div class="media-frame-content" data-columns="10">
                <div class="subscribe-main smms-message" style="display: none">
                </div>
                <div id="smms-file-list">
                    <ul>

                    </ul>
                </div>
                <div class="uploader-inline" id="smms-uploader">
                    <div class="smms-uploader-inline-content">
                    <fieldset>
                        <legend class="screen-reader-text"><span>时间格式</span></legend>
                        <label><input type="radio" id="smms_upload_method_v1" name="smms_upload_method" value="v1" checked="checked"><span>无token</span></label>
                        <label><input type="radio" id="smms_upload_method_v2" name="smms_upload_method" value="v2"><span>token</span></label>
                        <input type="file" name="filename" id="smms-upload-btn" multiple="multiple" />
	                </fieldset>
                    </div>
                </div>
                <div class="media-sidebar smms-detail">
                    <div class="attachment-details">
                        <h2>附件详情</h2>
                        <div class="attachment-info">
                            <div class="thumbnail thumbnail-image">
                                <img src="" draggable="false" alt="">
                            </div>
                            <div class="details">
                                <div class="smms-filename"></div>
                                <div class="smms-upload-time"></div>
                                <button type="button" class="button-link delete-attachment"
                                    id="smms-delete">永久删除</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="media-frame-toolbar">
                <div class="media-toolbar">
                    <div class="media-toolbar-secondary">
                        <div class="media-selection smms-insert-message">
                        </div>
                    </div>
                    <div class="media-toolbar-primary search-form">
                        <button type="button"
                            class="button media-button button-primary button-large media-button-insert"
                            id="smms-insert-to-post" disabled>插入至文章</button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php
}

//post.php || post-new.php加载
add_action('add_meta_boxes', 'smms_init');
function smms_init()
{
    smms_install();
    smms_load_assets();
}

// 文章编辑界面添加按钮
add_action('media_buttons', 'smms_upload_btn');
function smms_upload_btn()
{
    echo '<span class="button" name="smms" id="smms-modal-display" >上传图片(SM.MS)</span>';
}

//添加链接
add_filter('plugin_action_links', 'smms_plugin_detail', 10, 2);
function smms_plugin_detail($actions, $plugin_file)
{
    static $plugin;
    if (!isset($plugin)) {
        $plugin = plugin_basename(__FILE__);
    }
    if ($plugin == $plugin_file) {
        $settings	= array('settings' => '<a href="options-general.php?page=smms-image-settings">插件设置</a>');
        $site_link	= array('support' => '<a href="https://flylai.com/587.html" target="_blank">使用说明</a>');
        $actions 	= array_merge($settings, $actions);
        $actions	= array_merge($site_link, $actions);
    }
    return $actions;
}

//默认数据
add_action('admin_init', 'smms_options');
function smms_options()
{
    $smms_settings = get_option('SMMS_OPTION'); //获取选项
    if ($smms_settings == '') {
        $smms_settings = array( //设置默认数据
            'smms_server_domain' => 'i.loli.net',
            'smms_secret_token' => ''
        );
        update_option('SMMS_OPTION', $smms_settings); //更新选项
    }
}

//设置菜单
add_action('admin_menu', 'smms_image_menu');
function smms_image_menu()
{
    add_options_page('SMMS图床插件设置页面', 'SMMS图床插件设置', 'manage_options', 'smms-image-settings', 'smms_image_options');
}

function smms_image_options()
{
    if (isset($_POST['smms_save'])) {
        $smms_settings = array(
            'smms_server_domain' => trim(@$_POST['smms_server_domain']),
            'smms_secret_token' => trim(@$_POST['smms_secret_token'])
        );
        @update_option('SMMS_OPTION', $smms_settings);
        echo '<div class="updated" id="message"><p>已保存~~~!</p></div>';
    }
    $smms_settings = get_option('SMMS_OPTION'); ?>
<div class="wrap">
    <h1>SMMS图床工具-设置</h1>
    <p>SMMS图床工具可以在文章编辑界面上传图片至sm.ms图床，并插入文章</p>
    <p>Secret Token是SMMS账户的识别码，使用token上传的图片会归入你在SMMS的账户下</p>
    <form method="post">
        <table class="form-table">
            <tbody>
                <tr valign="top">
                    <th scope="row">选择smms图床域名</th>
                    <td>
                        <label>
                            <select name="smms_server_domain">
                                <option value="i.loli.net">i.loli.net</option>
                                <!-- <option value="ooo.0o0.ooo">ooo.0o0.ooo</option> smms目前不支持自选域名-->
                            </select>
                        </label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Secret Token</th>
                    <td>
                        <label>
                            <input name="smms_secret_token" type="text" value="<?php echo $smms_settings['smms_secret_token']; ?>" class="regular-text code"/>
                        </label>
                    </td>
                </tr>
            </tbody>
        </table>
        <p class="submit">
            <input class="button button-primary" type="submit" name="smms_save" id="submit"
                value="保存更改" />&nbsp;&nbsp;&nbsp;&nbsp;
        </p>
        </table>
        <p>
            <strong>使用提示:</strong>
            <br />
            1.插件会在数据库中新建一个表，包含上传文件的信息。
            <br />
            2.你可以在<strong>文章编辑界面</strong>进行上传和管理已经上传的图片，并可以将他们插入文章。
            <br />
            3.如果有问题和建议请到 <a href="https://flylai.com/587.html">博客</a> 下反馈。
        </p>
</div>
<?php
}
