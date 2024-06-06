options = %options
template_title = '%template_title'
template_type = '%template_type'


if template_type == 'custom_record' then

	template_description = ''
	%script

	template_description = '<p>自定义</p>'..template_description

else

	unit_title = '每小时需要金额'
	if template_type == 'record_time' then
		unit_title = '每小时需要金额'
	end
	if template_type == 'record_times' then
		unit_title = '每一次需要金额'
	end
	if template_type == 'record_samples' then
		unit_title = '每一样品需要金额'
	end

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

    -- bug: 24729（3）17Kong/Sprint-285：lims3.3全面测试：仪器设置按照使用折扣计费，普通用户查看仪器详情页-基本信息，使用计费设置显示错误
	if template_type == 'record_time_discount' then
        option = options['*']
        unit_price = option.unit_price
        minimum_fee = option.minimum_fee
		template_description = '<p>%template_title</p>'

		params = {
			['%unit_title'] = '<span>'..unit_title..'</span>',
			['%unit_price'] = '<span>'..unit_price..'</span>',
			['%minimum_fee'] = '<span>'..minimum_fee..'</span>',
		}
    else 
        template_description = '<p>%template_title</p><p>%unit_title: %unit_price</p><p>每次使用的开机费用: %minimum_fee</p>'

        params = {
            ['%unit_title'] = '<span>'..unit_title..'</span>',
            ['%unit_price'] = '<span>'..unit_price..'</span>',
            ['%minimum_fee'] = '<span>'..minimum_fee..'</span>',
        }
	end

	template_description = T(template_description, params)

end
