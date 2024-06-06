--按使用时间折扣计费脚本
defaultDiscount = 100
defaultTagDiscount = 100
--计算规则 timeCountDiscount * userTypeDiscount *  perHourPrice * ( zoneDiscount1* zoneCount1 + zoneDiscount2* zoneCount2)
fee = 0

--获取初始折扣
function noDiscount(option, record)
    return defaultDiscount
end

--获取时间累计折扣
function timeCountDiscount(countData, record)
    time_counts_discount = defaultDiscount
    use_sec_length = record.end_time - record.start_time + 1
    startLength = countData['start']['key'] * 3600
    endLength = countData['end']['key'] * 3600
    time_counts_discount = 100 --时间累计折扣最终值
    if (use_sec_length < startLength) then
        time_counts_discount = countData['start']['discount']
    elseif (use_sec_length >= endLength) then
        time_counts_discount = countData['end']['discount']
    elseif (countData['between'] ~= nil) then
        for k, v in pairs(countData['between']) do
            if (use_sec_length >= v['min'] * 3600 and use_sec_length < v['max'] * 3600) then
                time_counts_discount = v['discount']
                break
            end
        end
    end

    return time_counts_discount
end

--获取时间区间折扣
function zoneDiscount(zone, record)
    local zoneStart = zone['time_zone']['start']
    local zoneend = zone['time_zone']['end']
    local discountZone = zoneDiscount_php(zone)
    return discountZone
end


options = %options

default = options['*']
tag_dicount = options['tag_discount'] and options['tag_discount'] or nil

tags = user_tags()
if tags ~= nil then
    for key, value in pairs(tags) do
        if options[value] ~= nil then
            option = options[value]
            --当前标签的基础折扣
            defaultTagDiscount = (tag_dicount ~= nil and tag_dicount[value]) and tag_dicount[value] or 100
            break
        end
    end
end

--record()获取record信息
record = record()

--是否计入开机费
minimum_fee = default['minimum_fee']
if record.cancel_minimum_fee ~= nil and record.cancel_minimum_fee > 0 then
    default['minimum_fee'] = 0
end

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
free_length = post_time + lead_time

record['start_time'] = record.start_time + lead_time
record['end_time'] = record.end_time - post_time
use_sec_length = record.end_time - record.start_time + 1
use_hour_lenth = second_to_hour(use_sec_length)
description = ''

if option == nil then
    option = options['*']
    noDiscount = noDiscount(option, record) / 100
    fee = use_hour_lenth * noDiscount * default['unit_price']
else
    --获取使用时长折扣
    timeCountDiscount = timeCountDiscount(option['count'], record)
    nowPrice = default['unit_price'] * (timeCountDiscount / 100) * (defaultTagDiscount / 100) --折扣后的单价
    description = '(其中用户折扣' .. defaultTagDiscount .. '%. 累计时长' .. use_hour_lenth .. 'h, 折扣' .. timeCountDiscount .. '%. '
    --获取时间区间的折扣
    zoneDiscount = zoneDiscount(option['zone'], record)
    noDiscountLength = 0
    dicountToZone = {}
    for key, value in pairs(zoneDiscount) do
        if value['length'] > 1 then --1秒计算
            local zoneUseHourLength = second_to_hour(value['length'])
            local currentTotal = (value['discount'] / 100) * zoneUseHourLength
            fee = fee + nowPrice * currentTotal
            if dicountToZone[value['discount']] == nil then
                dicountToZone[value['discount']] = zoneUseHourLength
            else
                dicountToZone[value['discount']] = dicountToZone[value['discount']] + zoneUseHourLength
            end
        end
    end

    for key, value in pairs(dicountToZone) do
        description = description .. ' 使用时段' .. value .. 'h, 折扣' .. key .. '%;'
    end

    description = description .. ')'
end

--开计费
fee = round(fee + default['minimum_fee'], 2)

pd = '<p>预热时长:' .. lead_time / 60 .. '分钟,冷却时长:' .. post_time / 60 .. '分钟</p>'
description = '<p>计费时长%hourLength h, 单价 %currency%unit_price/h, 开机费 %currency%minimum_fee, 共计 %currency%fee</p>' .. description .. pd
params = {
    ['%hourLength'] = '<span>' .. use_hour_lenth .. '</span>',
    ['%unit_price'] = '<span>' .. default['unit_price'] .. '</span>',
    ['%minimum_fee'] = '<span>' .. default['minimum_fee'] .. '</span>',
    ['%currency'] = '<span>' .. currency .. '</span>',
    ['%fee'] = '<span>' .. fee .. '</span>'
}
description = T(description, params)

