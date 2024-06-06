options = %options
template_title = '%template_title'
template_type = '%template_type'

if template_type == 'custom_reserv' then

	template_description = ''
	%script

	template_description = '<p>自定义</p>'..template_description

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

	template_description = '<p>%template_title</p><p>每小时需要金额: %unit_price</p><p>每次使用的开机费用: %minimum_fee</p>'

	params = {
	    ['%unit_price'] = '<span>'..unit_price..'</span>',
	    ['%minimum_fee'] = '<span>'..minimum_fee..'</span>',
	}

	template_description = T(template_description, params)
end
