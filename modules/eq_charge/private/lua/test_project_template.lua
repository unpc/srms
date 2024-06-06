options = %options
test_project_items = options['*']['test_project_items']
template_title = '%template_title'
template_type = '%template_type'

if free_user then
    unit_price = 0
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

    template_description = '<p>%template_title</p>'

    if test_project_items ~= nil then
        for i, v in pairs(test_project_items) do
            if unit_price[i] ~= nil then
                template_description = template_description .. '<p>项目: ' .. v .. ' 金额: ' .. unit_price[i] .. '</p>'
            end
        end
    end

end

