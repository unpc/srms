--按使用时长进行计费脚本

options = %options

--record()获取当前record相关信息
current_record = record()

--如果当前record有关联reserv，则fee为0，免费使用
if current_record['reserv_id']  ~= 0 then
    --免费使用无description
    fee = 0
else

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

    --预热时间
    lead_time = current_record.lead_time
    if current_record.cancel_lead_time ~= nil and current_record.cancel_lead_time > 0 then
        lead_time = 0
    end

    --冷却时间
    post_time = current_record.post_time
    if current_record.cancel_post_time ~= nil and current_record.cancel_post_time > 0 then
        post_time = 0
    end

    --是否计入开机费
    minimum_fee = option.minimum_fee
    if current_record.cancel_minimum_fee ~= nil and current_record.cancel_minimum_fee > 0 then
        minimum_fee = 0
    end

    --前置后置时间
    free_length = post_time + lead_time
    duration = second_to_hour(end_time - start_time - free_length)
    fee = round(minimum_fee + duration * option.unit_price, 2)
    price = currency .. option.unit_price .. '/时'

    charge_duration_blocks = '<span>' .. date('Y/m/d H:i:s', start_time + lead_time) .. ' - ' .. date('Y/m/d H:i:s', end_time - post_time) .. '</span>'

    description = '<p>计费时段 %charge_duration_blocks</p><p>计费时长 %duration小时, 单价 %currency%unit_price/时, 开机费 %currency%minimum_fee, 共计 %currency%fee</p><p>预热时长: %lead_time分钟, 冷却时长: %post_time分钟</p>'

    params = {
        ['%charge_duration_blocks'] = charge_duration_blocks,
        ['%duration'] = '<span>'..duration..'</span>',
        ['%unit_price'] = '<span>'..option.unit_price..'</span>',
        ['%minimum_fee'] = '<span>'..minimum_fee..'</span>',
        ['%currency'] = '<span>'..currency..'</span>',
        ['%fee'] = '<span>' .. fee .. '</span>',
        ['%lead_time'] = '<span>' .. second_to_min(lead_time) .. '</span>',
        ['%post_time'] = '<span>' .. second_to_min(post_time) .. '</span>'
    }

    description = T(description, params)
end
