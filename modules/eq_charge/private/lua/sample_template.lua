options = %options
template_title = '%template_title'
template_type = '%template_type'


if template_type == 'custom_sample' then

	template_description = ''
	%script

	template_description = '<p>自定义</p>'..template_description

elseif template_type == 'project_sample' then
    template_description = '<p>按检测项目计费</p>'

else


	if free_user then
		unit_price = 0
	    minimum_fee = 0
	else

		tags = user_tags()
		if tags ~= nil then
		    for key, value in pairs(tags) do
		        if options[value] ~= nil then
		            option = options[value]
		            break
		        end
		    end
		end

		if option == nil then
		    option = options['*']
		end

        unit_price = option.unit_price
        minimum_fee = option.minimum_fee

	end

	unit_title = '每一样品需要金额'
	if template_type == 'sample_time' then
		unit_title = '每小时需要金额'
	end

	template_description = '<p>%template_title</p><p>%unit_title: %unit_price</p><p>每次使用的开机费用: %minimum_fee</p>'

	params = {
	    ['%unit_price'] = '<span>'..unit_price..'</span>',
		['%unit_title'] = '<span>'..unit_title..'</span>',
	    ['%minimum_fee'] = '<span>'..minimum_fee..'</span>',
	}

	template_description = T(template_description, params)
end
