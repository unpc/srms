jQuery(function($) {
    $('body').on('change','input.date', function() {
        var name = $(this).next().attr("name");
        var $submit_date = $(
            'input[name="' + name + '"][type="hidden"]'
        );
       
       
        var t = $(this).attr('date_type');
        if (t == 'time') {
            var date = new Date('1970/01/01 ' + $(this).val());
        } else {
            var date = new Date($(this).val().replace(/-/g,'/'))
        }
        
        var time = date.getTime() / 1000
        if ($(this).val() == "") {
            $submit_date.val('')
            return;
        }
     
        $submit_date.val(time)
    });

    function formate(n){
        if(n<10)
        {
            n='0'+n;
        }
        return n;
    }
    $('input.date').livequery(function() {

        // if ($(this).prop('disabled')) {
        //     return
        // }

        //$(this).attr('readonly', 'readonly')
        var name = $(this).attr("name");
        var value = $(this).val();
        var t = $(this).attr('date_type');
        t = t ? t : 'datetime';
        var f = $(this).attr('date_format');
        f = f ? f : 'yyyy-MM-dd';
        //当value为数字或者value不为空时将日期格式转换
        if (!isNaN(value) && value != "") {
            
            //年月日时分秒
            var Y,M,D,h,m,s;
            var date = new Date(value * 1000);
            Y = date.getFullYear();
            M = date.getMonth() + 1 ;
            D = date.getDate();
            h = date.getHours();
            m = date.getMinutes();
            s = date.getSeconds();
            h=formate(h);
            m=formate(m);
            s=formate(s);
            D=formate(D);
            M=formate(M);

            switch (t) {
                case 'date':
                    f === 'MM-dd' ? $(this).val(M+'-'+D) : $(this).val(Y+'-'+M+'-'+D);
                    break;
                case 'year':
                    $(this).val(Y);
                    break;
                case 'time':
                    $(this).val(h+':'+m+':'+s);
                    break;
                case 'month':
                    $(this).val(Y+'-'+M);
                    break;
                case 'minute':
                    $(this).val(Y+'-'+M+'-'+D+' '+h+':'+m);
                    break;
                case 'datetime':
                    $(this).val(Y+'-'+M+'-'+D+' '+h+':'+m+':'+s);
                    break;
            }
        }

        laydate.render({
            elem: $(this).get(0),
            type: t,
            format: f
        });
        $(this).attr("name", "")
        $(this).after('<input name="' + name + '" type="hidden">');
        $(this).trigger("change");
    });
});
