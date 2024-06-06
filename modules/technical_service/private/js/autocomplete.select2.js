var select_params = {
    closeOnSelect: false,
    multiple: true, //多选
    ajax: {
        dataType: 'json',
        data: function(params) {
            return {
                s: params.term, // 搜索框内输入的内容，传递到Java后端的parameter为username
                st: params.page, // 第几页，分页哦
            };
        },
        // 分页
        processResults: function(data, params) {
            params.page = params.page || 1;
            return {
                results: data, // 后台返回的数据集
                pagination: {
                    more: (params.page * 5) < data[0].total_count //auto接口需要返回total_count
                }
            };
        },
        cache: false
    },
    escapeMarkup: function(markup) {
        return markup;
    },
    templateResult: function(repo) { // 显示的结果集格式，这里需要自己写css样式，可参照demo
        // 正在检索
        if (repo.loading) {
            return repo.text;
        }
        var markup = repo.text || repo.html;
        return markup;
    },
    templateSelection: function(repo) {
        return repo.text;
    } // 列表中选择某一项后显示到文本框的内容
};

$('select.autocomplete_select2').livequery(function(){
    console.log('autocomplete_select2-init');
    $this = $(this);
    var url = $this.data('src');
    if(url){
        select_params['ajax']['url'] = url;
        console.log(select_params);
        $this.select2(select_params);
    }
});