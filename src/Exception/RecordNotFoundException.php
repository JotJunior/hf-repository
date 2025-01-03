<?php

namespace Jot\HfRepository\Exception;

class RecordNotFoundException extends \Exception
{
    protected $message = 'Record not found';
}