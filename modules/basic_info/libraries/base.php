<?php

abstract class Base extends API_Common
{
    protected static $errors = [
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Invalid Args',
        404 => 'Not Found'
    ];
}
