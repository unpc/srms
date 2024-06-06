--按使用时间计费脚本

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

--record()获取record信息
record = record()

--[[
    record['end']结束时间
        对应数据库内dtend
    record['start']开始时间
        对应数据库内dtstart
--]]

start_time = record.start_time
end_time = record.end_time

--预热时间
lead_time = record.lead_time
if record.cancel_lead_time ~= nil and record.cancel_lead_time > 0 then
    lead_time = 0
end

--冷却时间
post_time = record.post_time
if record.cancel_post_time ~= nil and record.cancel_post_time > 0 then
    post_time = 0
end

--前置后置时间
free_length = lead_time + post_time

--获取使用时长
duration = second_to_hour(end_time - start_time - free_length )

--是否计入开机费
minimum_fee = option.minimum_fee
if record.cancel_minimum_fee ~= nil and record.cancel_minimum_fee > 0 then
    minimum_fee = 0
end

--计算fee
fee = round(duration * option.unit_price + minimum_fee, 2)
price = currency .. option.unit_price .. '/时'

--计费时段
charge_duration_blocks = date('Y/m/d H:i:s', start_time + lead_time) .. ' - ' .. date('Y/m/d H:i:s', end_time - post_time)

description = '<p>计费时段 %charge_duration_blocks</p><p>计费时长 %duration小时, 单价 %currency%unit_price/时, 开机费 %currency%minimum_fee, 共计 %currency%fee</p><p>预热时长: %lead_time分钟, 冷却时长: %post_time分钟</p>'

params = {
    ['%charge_duration_blocks'] = '<span>'..charge_duration_blocks..'</span>',
    ['%duration'] = '<span>'..duration..'</span>',
    ['%unit_price'] = '<span>'..option.unit_price..'</span>',
    ['%minimum_fee'] = '<span>'..minimum_fee..'</span>',
    ['%currency'] = '<span>'..currency..'</span>',
    ['%fee'] = '<span>' .. fee .. '</span>',
    ['%lead_time'] = '<span>' .. second_to_min(lead_time) .. '</span>',
    ['%post_time'] = '<span>' .. second_to_min(post_time) .. '</span>'
}

description = T(description, params)
