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

--sample()函数获取sample
sample = sample()

records = sample_records(id)

if #records > 0 then
    description = ""
    fee = 0;
    for key, record in pairs(records) do
        --[[
            sample['end']结束时间
                对应数据库内dtend
            sample['start']开始时间
                对应数据库内dtstart
        --]]

        start_time = record['start_time']
        end_time = record['end_time']

        --前置后置时间
        free_length = record['post_time'] + record['lead_time']

        --获取使用时长
        duration = second_to_hour(end_time - start_time - free_length )

        --计算fee
        unit = round(duration * option.unit_price + option.minimum_fee, 2)
        fee = fee + round(duration * option.unit_price + option.minimum_fee, 2)
        price = currency .. option.unit_price .. '/时'

        if start_time > 0 then
            --计费时段
            charge_duration_blocks = date('Y/m/d H:i:s', start_time + record['lead_time']) .. ' - ' .. date('Y/m/d H:i:s', end_time - record['post_time'])

            description = description .. '<p>计费时段 %charge_duration_blocks</p><p>计费时长 %duration小时, 单价 %currency%unit_price/时, 开机费 %currency%minimum_fee, 共计 %currency%fee</p><p>预热时长: %lead_time分钟, 冷却时长: %post_time分钟</p>'
    
            params = {
                ['%charge_duration_blocks'] = '<span>'..charge_duration_blocks..'</span>',
                ['%duration'] = '<span>'..duration..'</span>',
                ['%unit_price'] = '<span>'..option.unit_price..'</span>',
                ['%minimum_fee'] = '<span>'..option.minimum_fee..'</span>',
                ['%currency'] = '<span>'..currency..'</span>',
                ['%fee'] = '<span>' .. unit .. '</span>',
                ['%lead_time'] = '<span>' .. second_to_min(record['lead_time']) .. '</span>',
                ['%post_time'] = '<span>' .. second_to_min(record['post_time']) .. '</span>'
            }
        else
            description = description .. '<p>计费时长 %duration小时, 单价 %currency%unit_price/时, 开机费 %currency%minimum_fee, 共计 %currency%fee</p><p>预热时长: %lead_time分钟, 冷却时长: %post_time分钟</p>'
    
            params = {
                ['%duration'] = '<span>'..duration..'</span>',
                ['%unit_price'] = '<span>'..option.unit_price..'</span>',
                ['%minimum_fee'] = '<span>'..option.minimum_fee..'</span>',
                ['%currency'] = '<span>'..currency..'</span>',
                ['%fee'] = '<span>' .. unit .. '</span>',
                ['%lead_time'] = '<span>' .. second_to_min(record['lead_time']) .. '</span>',
                ['%post_time'] = '<span>' .. second_to_min(record['post_time']) .. '</span>'
            }
        end

        description = T(description, params)
    end
else
    --[[
        sample['end']结束时间
            对应数据库内dtend
        sample['start']开始时间
            对应数据库内dtstart
    --]]

    start_time = sample.start_time
    end_time = sample.end_time

    --前置后置时间
    free_length = sample.post_time + sample.lead_time

    --获取使用时长
    duration = second_to_hour(end_time - start_time - free_length )

    --计算fee
    fee = round(duration * option.unit_price + option.minimum_fee, 2)
    price = currency .. option.unit_price .. '/时'

    if start_time > 0 then
        --计费时段
        charge_duration_blocks = date('Y/m/d H:i:s', start_time + sample.lead_time) .. ' - ' .. date('Y/m/d H:i:s', end_time - sample.post_time)

        description = '<p>计费时段 %charge_duration_blocks</p><p>计费时长 %duration小时, 单价 %currency%unit_price/时, 开机费 %currency%minimum_fee, 共计 %currency%fee</p><p>预热时长: %lead_time分钟, 冷却时长: %post_time分钟</p>'

        params = {
            ['%charge_duration_blocks'] = '<span>'..charge_duration_blocks..'</span>',
            ['%duration'] = '<span>'..duration..'</span>',
            ['%unit_price'] = '<span>'..option.unit_price..'</span>',
            ['%minimum_fee'] = '<span>'..option.minimum_fee..'</span>',
            ['%currency'] = '<span>'..currency..'</span>',
            ['%fee'] = '<span>' .. fee .. '</span>',
            ['%lead_time'] = '<span>' .. second_to_min(sample.lead_time) .. '</span>',
            ['%post_time'] = '<span>' .. second_to_min(sample.post_time) .. '</span>'
        }
    else
        description = '<p>计费时长 %duration小时, 单价 %currency%unit_price/时, 开机费 %currency%minimum_fee, 共计 %currency%fee</p><p>预热时长: %lead_time分钟, 冷却时长: %post_time分钟</p>'

        params = {
            ['%duration'] = '<span>'..duration..'</span>',
            ['%unit_price'] = '<span>'..option.unit_price..'</span>',
            ['%minimum_fee'] = '<span>'..option.minimum_fee..'</span>',
            ['%currency'] = '<span>'..currency..'</span>',
            ['%fee'] = '<span>' .. fee .. '</span>',
            ['%lead_time'] = '<span>' .. second_to_min(sample.lead_time) .. '</span>',
            ['%post_time'] = '<span>' .. second_to_min(sample.post_time) .. '</span>'
        }
    end

    description = T(description, params)
end
