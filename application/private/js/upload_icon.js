/**
 + 用户、仪器、课题组头像上传
 +
 + @author: Clh  lianhui.cao@geneegroup.com
 + @time: 2018-08-24 10:00:00
 +
 + @param url -> upload_url
 + @param mod -> module
 **/
(function($){

	$.upload_icon = function(url, mod) {

        var icon_cover = $('.img_icon').find('.icon_cover');
        var upload_icon = $('.upload_icon');
        $('.img_icon').hover(function () {icon_cover.show(); }, function () {icon_cover.hide(); })

        icon_cover.click(function() {upload_icon.click();});

        upload_icon.change(function() {
            var file = event.target.files[0];
            if((file.type).indexOf("image/")==-1){  
                alert("仅支持上传png,jpg等格式的图片!");
                return;
            }else{
                var formData = new FormData();
                formData.append('file', file);
                formData.append('submit', '上传图标');
    
                $.ajax({
                    url: url,
                    type: 'post',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success : function(data) {
                        $(".short_container .icon").attr("src", getObjectURL(upload_icon[0].files[0]));
                    },
                    error : function() {
                        console.warn('Upload File False!');
                    }
                });
            }
        })


        var getObjectURL = function (file) {
            var url = null ;
            if (window.createObjectURL != undefined) { // basic
                url = window.createObjectURL(file) ;
            } else if (window.URL != undefined) { // mozilla(firefox)
                url = window.URL.createObjectURL(file) ;
            } else if (window.webkitURL != undefined) { // webkit or chrome
                url = window.webkitURL.createObjectURL(file) ;
            }
            return url ;
        };

    }

})(jQuery);
