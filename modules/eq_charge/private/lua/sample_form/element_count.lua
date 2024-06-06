--按使用记录检测记录数计费脚本

-- options = %options

-- tags = user_tags()
-- if tags ~= nil then
--     for key, value in pairs(tags) do

--         if options[value] ~= nil then
--             option = options[value]
--             break
--         end

--     end
-- end

-- if option == nil then
--     option = options['*']
-- end


--获取检测信息
element = sample_form()
count = element.count
unit_price = element.price
start_time = element.start_time
end_time = element.end_time

--获取使用时长
duration = second_to_hour(end_time - start_time)
--计费时段
charge_duration_blocks = date('Y/m/d H:i:s', start_time)..' - '..date('Y/m/d H:i:s', end_time)

fee = round(count * unit_price, 2)

description = '<p>检测数量%count个, 单价 %currency%unit_price/个, 共计 %currency%fee</p>'
params = {
    ['%charge_duration_blocks'] = '<span>'..charge_duration_blocks..'</span>',
    ['%duration'] = '<span>'..duration..'</span>',
    ['%count'] = '<span>'..count..'</span>',
    ['%unit_price'] = '<span>'..unit_price..'</span>',
    ['%currency'] = '<span>'..currency..'</span>',
    ['%fee'] = '<span>'..fee..'</span>'
}

description = T(description, params)
