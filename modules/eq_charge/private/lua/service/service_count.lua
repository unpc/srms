--按服务项目数计费

options = %options

tags = user_tags()

if tags ~= nil then
    for key, value in pairs(tags) do

        --类似于array_key_exists
        if options[value] ~= nil then
            option = options[value]
            break
        end
    end
end

if option == nil then
    option = options['*']
end

fee = 0

service_apply_record = service_apply_record()

project_k = 'project_price_' .. service_apply_record.project_id

per_project_amount = 0

for k, v in pairs(option) do
    if project_k == k then per_project_amount = v end
end

price = currency .. per_project_amount
fee = round(per_project_amount, 2)

--description模板
description = '<p>按服务项目计费,项目【%project_name】%price/次,共计%currency%fee</p>'

--设定replace array
params = {
    ['%project_name'] = '<span>' .. service_apply_record.project_name .. '</span>',
    ['%price'] = '<span>' .. price .. '</span>',
    ['%currency'] = '<span>' .. currency .. '</span>',
    ['%fee'] = '<span>' .. fee .. '</span>'
}

description = T(description, params)