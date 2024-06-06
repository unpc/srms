--按按耗材个数计费脚本 (已通过charge_lua_result.after.calculate_amount手动计算耗材费，不再走这个lua了)

options = %options

tags = user_tags()
if tags ~= nil then
    for key, value in pairs(tags) do

        if options[value] ~= nil then
                option = options[value]['unit_price']
            break
        end

    end
end

if option == nil then
    option = options['*']['unit_price']
end

--获取耗材信息
material_record = material_record()
materials = material_record.materials
source_id = material_record.source_id
source_name = material_record.source_name

--计算收费
fee = 0

for id, number in pairs(materials) do

    fee = fee + (number * option[id])

end

fee = round(fee, 2)

--计费描述
description = '<p>'..source_name..'['..source_id..']生成耗材收费, 共计 <span>'..currency..'</span><span>'..fee..'</span></p>'
