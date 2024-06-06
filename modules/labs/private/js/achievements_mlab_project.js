jQuery(function($){
  var selector = ['#', proj_uid].join('')
    , $form = $(selector).parents('form')

  $form.find('input[name=lab]').bind('token.input.change', function() {
    Q.trigger({
      event:'select_lab_change',
      data:{
        container:proj_uid,
        object_name:object_name,
        object_id:object_id,
        labs:$form.find('input[name=lab]').val(),
      },
      url:url
    });
  })
})
