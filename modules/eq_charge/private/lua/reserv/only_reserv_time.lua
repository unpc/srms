--预约收费脚本,近按预约的时间收费，不考虑使用情况
options = %options

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

fee = 0

duration = second_to_hour(end_time - start_time)
fee = round(duration * option.unit_price + option.minimum_fee, 2)
price = currency .. option.unit_price .. '/时'
charge_duration_blocks = '<span>' .. date('Y/m/d H:i:s', start_time) .. ' - ' .. date('Y/m/d H:i:s', end_time) .. '</span>'

description = '<p>计费时段 %charge_duration_blocks</p><p>计费时长%duration小时, 单价 %currency%unit_price/时, 开机费 %currency%minimum_fee, 共计 %currency%fee</p>'

params = {
    ['%charge_duration_blocks'] = charge_duration_blocks,
    ['%duration'] = '<span>'..duration..'</span>',
    ['%unit_price'] = '<span>'..option.unit_price..'</span>',
    ['%minimum_fee'] = '<span>'..option.minimum_fee..'</span>',
    ['%currency'] = '<span>'..currency..'</span>',
    ['%fee'] = '<span>'..fee..'</span>'
}

description = T(description, params)
