--预约收费脚本
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

--[[
    start_time  reserv对象起始时间
    end_time    reserv对象结束时间
--]]

--records(start_time, end_time) 获取reserv关联的record数组

reserv_records = records(start_time, end_time)

charge_start_time = start_time
charge_end_time = end_time
fee = 0

--对存在使用记录进行处理
if #reserv_records > 0 then
    for key, record in pairs(reserv_records) do

        if record['start_time'] < charge_start_time then
            charge_start_time = record['start_time']
        end

        if record['end_time'] > charge_end_time then
            charge_end_time = record['end_time']
        end

    end
end

--对别人超时使用占用进行处理
if #reserv_records > 0 then

    --table.remove和php的array_shift功能一样
    first_record = table.remove(reserv_records, 1)

    if first_record['reserv_id'] ~= id and first_record['start_time'] <= start_time then
        charge_start_time = first_record['end_time'] + 1
    else
        table.insert(reserv_records, 1 ,first_record);
    end

end

charge_duration = second_to_hour(charge_end_time - charge_start_time)

fee = charge_duration * option.unit_price

other_duration = 0

--计费时段blocks
blocks = {
    {
        ['start_time'] = charge_start_time,
        ['end_time'] = charge_end_time,
        ['lead_time'] = 0,
        ['post_time'] = 0
    }
}

--自己使用计数
self_use = 0

while #reserv_records > 0 do
    --初始化使用blocks

    --获取最后一个reserv_records
    last_record = table.remove(reserv_records, #reserv_records)

    start_time = last_record['start_time']
    end_time = last_record['end_time']
    lead_time = last_record['lead_time']
    post_time = last_record['post_time']

    if last_record['user_id'] ~= user_id then
        --他人占用不收费
        other_duration = other_duration + second_to_hour(end_time - start_time)
    else
        --前置后置不收费
        other_duration = other_duration + second_to_hour(lead_time + post_time)
        self_use = self_use + 1
    end

    tmp = {}

    --计数器累加
    for k, block in pairs(blocks) do

        if last_record['user_id'] ~= user_id then
            --使用记录在block内, 进行block拆分
            if start_time > block['start_time'] and end_time < block['end_time'] then
                ab_end_time = start_time - 1
                bb_start_time = end_time + 1

                bstart_time = block['start_time']
                bend_time = block['end_time']

                ab = {
                    ['start_time'] = bstart_time,
                    ['end_time'] = ab_end_time,
                    ['lead_time'] = block['lead_time'],
                    ['post_time'] = 0
                }

                bb = {
                    ['start_time'] = bb_start_time,
                    ['end_time'] = bend_time,
                    ['lead_time'] = 0,
                    ['post_time'] = block['post_time']
                }

                table.insert(tmp, ab)
                table.insert(tmp, bb)
            elseif start_time > block['start_time'] and start_time < block['end_time'] and end_time >= block['end_time'] then
                block['end_time'] = start_time - 1
                table.insert(tmp, block)
            elseif end_time > block['start_time'] and end_time < block['end_time'] and start_time <= block['start_time'] then
                block['start_time'] = end_time + 1
                table.insert(tmp, block)
            else
                table.insert(tmp, block)
            end
        else
            --自己使用
            --  |-----------block------------------|
            --      |+++|-----record---------|+|
            --  as  ae                         cs  ce  (ae == bs, cs == be)
            --  |-a-|-----------b--------------|-c-|
            if start_time >= block['start_time'] and end_time <= block['end_time'] then
                if block['start_time'] ~= start_time then
                    a = {
                        ['start_time'] = block['start_time'],
                        ['end_time'] = start_time - 1,
                        ['lead_time'] = block['lead_time'],
                        ['post_time'] = 0
                    }
                    table.insert(tmp, a)
                end

                b = {
                    ['start_time'] = start_time,
                    ['end_time'] = end_time - 1,
                    ['lead_time'] = lead_time,
                    ['post_time'] = post_time
                }
                table.insert(tmp, b)

                if block['end_time'] ~= end_time then
                    c = {
                        ['start_time'] = end_time,
                        ['end_time'] = block['end_time'],
                        ['lead_time'] = 0,
                        ['post_time'] = block['post_time']
                    }
                    table.insert(tmp, c)
                end
            else
                table.insert(tmp, block)
            end
        end

    end

    blocks = tmp

end

--减去他人占用扣费增加开机费
fee = round(fee - other_duration * option.unit_price + option.minimum_fee * self_use, 2)
price = currency .. option.unit_price .. '/时'

sum_lead_time = 0
sum_post_time = 0
charge_duration_blocks = ''
for k, block in pairs(blocks) do
    charge_duration_blocks = charge_duration_blocks..'<span>'..date('Y/m/d H:i:s', block['start_time'] + block['lead_time'])..' - '..date('Y/m/d H:i:s', block['end_time'] - block['post_time'])..'</span>'
    sum_lead_time = sum_lead_time +  block['lead_time']
    sum_post_time = sum_post_time +  block['post_time']
    if (k ~= #blocks) then
        charge_duration_blocks = charge_duration_blocks..', '
    end
end

description = '<p>计费时段 %charge_duration_blocks</p><p>计费时长 %duration小时, 单价 %currency%unit_price/时, 开机费 %currency%minimum_fee, 共计 %currency%fee</p><p>预热时长: %lead_time分钟, 冷却时长: %post_time分钟</p>'

--减去他人计费时长, 获取实际计费时长
duration = charge_duration - other_duration

params = {
    ['%charge_duration_blocks'] = charge_duration_blocks,
    ['%duration'] = '<span>'..duration..'</span>',
    ['%unit_price'] = '<span>'..option.unit_price..'</span>',
    ['%minimum_fee'] = '<span>'..option.minimum_fee..'</span>',
    ['%currency'] = '<span>'..currency..'</span>',
    ['%fee'] = '<span>'..fee..'</span>',
    ['%lead_time'] = '<span>'..second_to_min(sum_lead_time)..'</span>',
    ['%post_time'] = '<span>'..second_to_min(sum_post_time)..'</span>'
}

description = T(description, params)
