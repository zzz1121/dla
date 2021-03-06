
layui.use(['jquery'], function() {
    var $ = layui.jquery, input = '';
    /* 修改模式下表单自动赋值 */
    if (formData) {
        console.log(formData);
        for (var i in formData) {
            switch($('.field-'+i).attr('type')) {
                case 'select':
                    input = $('.field-'+i).find('option[value="'+formData[i]+'"]');
                    input.prop("selected", true);
                    break;
                case 'radio':
                    input = $('.field-'+i+'[value="'+formData[i]+'"]');
                    input.prop('checked', true);
                    break;
                case 'checkbox':
                    input = $('.field-'+i+'[value="'+formData[i]+'"]');
                    input.val(formData[i]);
                    break;
                case 'img':
                    input = $('.field-'+i);
                    input.attr('src', formData[i]);
                default:
                    input = $('.field-'+i);
                    input.val(formData[i]);
                    break;
            }
            if (input.attr('data-disabled')) {
                input.prop('disabled', true);
            }
            if (input.attr('data-readonly')) {
                input.prop('readonly', true);
            }
        }
        // form.render('checkbox');
    }
});