<?php
class Message_API
{
    public static function messages_get($e, $params, $data, $query)
    {

        $user = L('gapperUser');
        $selector = "{$user}<receiver message";

        if (isset($query['startTime']) && intval($query['startTime'])) {
            $dtstart = intval($query['startTime']);
            $selector .= "[ctime>={$dtstart}]";
        }
        if (isset($query['endTime']) && intval($query['endTime'])) {
            $dtend = intval($query['endTime']);
            $selector .= "[ctime>0][ctime<={$dtend}]";
        }

        $total = $pp = Q("$selector")->total_count();
        $start = (int) $query['st'] ?: 0;
        $per_page = (int) $query['pp'] ?: 30;
        $start = $start - ($start % $per_page);
        $selector .= ":limit({$start},{$per_page}):sort(is_read A, ctime D)";
        $messages = [];
        foreach (Q("$selector") as $message) {
            $messages[] = self::message_format($message);
        }
        $e->return_value = ["total" => $total, "items" => $messages];
    }

    public static function message_patch($e, $params, $data, $query)
    {
        $user = L('gapperUser');
        $message = O('message', $params[0]);
        if (!$message->id) {
            throw new Exception('message not found', 404);
        }

        if ($message->receiver->id != $user->id) {
            throw new Exception('forbbiden', 403);
        }

        if (!$message->is_read && $data['read']) {
            $message->is_read = true;
            $message->save();
        }
        $e->return_value = self::message_format($message);
    }

    public static function message_format($message)
    {
        return [
            'id' => (int) $message->id,
            'sender' => [
                'id' => (int)$message->sender->id,
                'name' => (int)$message->sender->id ? $message->sender->name : H('系统'),
            ],
            'receiver' => [
                'id' => (int)$message->receiver->id,
                'name' => $message->receiver->name,
            ],
            'title' => $message->title,
            'body' => (string)strip_tags(new Markup(stripslashes($message->body), '<br><a>'), TRUE),
            'is_read' => (bool) $message->is_read,
            'ctime' => (int) $message->ctime,
        ];
    }
}
