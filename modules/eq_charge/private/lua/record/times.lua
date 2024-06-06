--使用次数收费脚本

options = %options

tags = user_tags()

if tags ~= nil then
    for key, value  in pairs(tags) do
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

--是否计入开机费
minimum_fee = option.minimum_fee
if record.cancel_minimum_fee ~= nil and record.cancel_minimum_fee > 0 then
    minimum_fee = 0
end

--是否计入单价
unit_price = option.unit_price
if record.cancel_unit_price ~= nil and record.cancel_unit_price > 0 then
    unit_price = 0
end

fee = round(unit_price + minimum_fee, 2)
price = currency .. unit_price .. '/次'

description = '<p>单价 %currency%unit_price/次, 开机费 %currency%minimum_fee, 共计 %currency%fee</p>'

params = {
    ['%unit_price'] = '<span>'..unit_price..'</span>',
    ['%minimum_fee'] = '<span>'..minimum_fee..'</span>',
    ['%currency'] = '<span>'..currency..'</span>',
    ['%fee'] = '<span>'..fee..'</span>'
}

description = T(description, params)
