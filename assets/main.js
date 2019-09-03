jQuery(document).ready(function ($) {

    var image_url = '';// 要被插入文章的图片url

    // 标签页 默认第一个显示
    $(".smms-router a:first").addClass("active").show();
    // 标签页点击事件
    $(".smms-menu-item").click(function () {
        $(".smms-menu-item").removeClass("active");
        $(this).addClass("active");
    });
    // smms上传文件模态框
    $("#smms-modal-display").click(function () {
        $(".smms-container").css('display', 'block');
    });
    // 关闭模态框
    $("#smms-modal-close").click(function () {
        $(".smms-container").css('display', 'none');
    });
    // 已上传的图片列表
    $("#smms-uploaded").click(function () {
        $("#smms-file-list").removeClass("hidden");
        $("#smms-uploader").addClass("hidden");
        var data = { 'action': 'smms_route', 'do': 'query' };
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: data,
            dataType: "json",
            success: function (res) {
                $("#smms-file-list ul").html('');
                for (var i = 0; i < res.length; i++) {
                    var datas = "data-id=\"" + res[i].id +
                        "\" data-filename=\"" + res[i].filename +
                        "\" data-hash=\"" + res[i].hash +
                        "\" data-path=\"" + res[i].path +
                        "\" data-time=\"" + res[i].time + "\"";
                    $("#smms-file-list ul").append("<li id=\"smms-image-" + res[i].id + "\"><div class=\"smms-image\"" + datas + "><img src=\"" + smms_server_domain + "/" + res[i].path + "\"/></div></li>")
                }
                // 图片被选中
                $('div[class="smms-image"]').click(function () {
                    image_url = $(this).children("img").attr("src");
                    $("#smms-insert-to-post").removeAttr("disabled");
                    $("#smms-delete").attr("smms-image-id", $(this).data("id"));
                    $("#smms-delete").attr("smms-image-hash", $(this).data("hash"));
                    $(".smms-filename").html($(this).data("filename"));
                    $(".smms-upload-time").html($(this).data("time"));
                    $(".smms-detail .thumbnail").children("img").attr("src", smms_server_domain + $(this).data("path"));

                });
            }
        });
    });
    // 插入至文章
    $("#smms-insert-to-post").click(function () {
        $("html").find("iframe").contents().find("body").append('<img class="alignnone size-full wp-image-177" src="' + image_url + '" />');
        $(".smms-insert-message").html('<div class="updated"><p>已插入文章~~</p></div>').show(300).delay(3000).hide(300);
    });
    // 删除图片
    $("#smms-delete").click(function () {
        if (!confirm("是否删除图片(包括服务器和图床)"))
            return;
        var id = $(this).attr("smms-image-id");
        var hash = $(this).attr("smms-image-hash");
        var data = { 'action': 'smms_route', 'do': 'delete', 'id': id };
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: data,
            dataType: "text",
            success: function (res) {
                console.log(res);
                if (res == 'success') {
                    $("#smms-image-" + id).remove();
                    $.ajax({
                        url: 'https://sm.ms/api/delete/' + hash,
                        type: 'GET',
                        success: function () { }
                    });
                }
            }
        });
    });
    // 模态框上传 被点击
    $("#smms-upload").click(function () {
        $("#smms-file-list").addClass("hidden");
        $("#smms-uploader").removeClass("hidden");
    });
    // 上传到smms
    $('#smms-upload-btn').change(function () {
        var text = '';
        var formData = new FormData();
        for (var i = 0; i < this.files.length; i++) {
            var f = this.files[i];
            formData.append('smfile', f);
        }
        if ($('#smms_upload_method_v2').attr('checked') == 'checked') {
            formData.append('action', 'smms_route');
            formData.append('do', 'upload_v2');
            formData.append('upload', 'v2');
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                processData: false,
                contentType: false,
                async: false,
                dataType: 'json',
                data: formData,
                success: function (local_res) {
                    $("#smms-upload-btn").val('');
                    if (local_res == 'failed') {
                        text = '图片上传失败，内部错误';
                    } else if (local_res.code == 'success') {
                        text = '图片 ' + local_res.data.filename + ' 上传成功~~~';
                    } else {
                        text = '图片 ' + $("#smms-upload-btn").val() + ' 上传失败，错误信息：<strong>' + local_res.message + '</strong>';
                    }
                }
            });
        } else {
            $.ajax({
                url: 'https://sm.ms/api/v2/upload',
                type: 'POST',
                processData: false,
                contentType: false,
                async: false,
                data: formData,
                success: function (remote_res) {
                    $("#smms-upload-btn").val('');
                    if (remote_res.code != 'success') {
                        text = '图片 ' + $("#smms-upload-btn").val() + ' 上传失败，错误信息：<strong>' + remote_res.message + '</strong>';
                    } else {
                        // 结果发送到后端
                        var data = { 'action': 'smms_route', 'do': 'upload', 'smms-upload-result': remote_res };
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: data,
                            success: function (local_res) {
                                if (local_res == 'success') {
                                    text = '图片 ' + remote_res.data.filename + ' 上传成功~~~';
                                }
                            }
                        });
                    }
                }
            });
        }
        $(".smms-message").html('<p>' + text + '</p>').show(300).delay(3000).hide(300);
    });
});
