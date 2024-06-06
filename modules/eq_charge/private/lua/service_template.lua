template_title = '%template_title'
template_type = '%template_type'


if template_type == 'custom_service' then
    template_description = '<p>自定义</p>' .. template_description
elseif template_type == 'service_count' then
    template_description = '<p>按服务项目计费</p>'

elseif template_type == 'service_sample_count' then
    template_description = '<p>按样品数计费</p>'
else
    template_description = '<p>免费检测</p>'
    params = {}
    template_description = T(template_description, params)
end
