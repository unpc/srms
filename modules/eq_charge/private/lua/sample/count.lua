--按送样记录样品数计费脚本

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

--sample()函数获取sample
sample = sample()

--sample['count']为送样送样数

count = sample.count

fee = round(count * option.unit_price + option.minimum_fee, 2)
price = currency .. option.unit_price .. '/个'

--description模板
description = '<p>送样%count个, 单价 %currency%unit_price/个, 开机费 %currency%minimum_fee, 共计 %currency%fee</p>'

--设定replace array
params = {
    ['%count'] = '<span>'..count..'</span>',
    ['%unit_price'] = '<span>'..option.unit_price..'</span>',
    ['%minimum_fee'] = '<span>'..option.minimum_fee..'</span>',
    ['%currency'] = '<span>'..currency..'</span>',
    ['%fee'] = '<span>'..fee..'</span>'
}

description = T(description, params)
