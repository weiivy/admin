<?php

namespace Rain\Validators;

/**
 * 虚拟验证，其主要目的是标记安全的值
 */
class SafeValidator extends Validator
{
    public $skipOnEmpty = false;

    protected function validateValue(&$value)
    {
    }
}
