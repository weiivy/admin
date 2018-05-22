<?php

namespace Rain;

use Rain\Validators\Validator as BaseValidator;

/**
 * 数据验证帮助类
 * @author  Zou Yiliang
 */
class Validator
{
    public static $errors = [];

    /**
     * @param array $data
     * @param $rules
     * @return bool
     */
    public static function validate(&$data, array $rules, array $labels = array())
    {
        $temp = [];
        foreach ($rules as $k => $v) {
            if ($v instanceof ValidateRuleMaker) {
                $temp = array_merge($temp, $v->getRules());
            } else {
                $temp[] = $v;
            }
        }

        self::$errors = BaseValidator::runValidate($temp, $data, $labels);
        return count(self::$errors) === 0;
    }

    /**
     * 返回一条验证错误消息
     * @return string
     */
    public static function getFirstError()
    {
        $errors = array_values(static::getFirstErrors());
        return current($errors);
    }

    /**
     * 返回验证错误消息，每个字段只返回第一条消息
     * [
     *     'username' =>  'Username is required.',
     *     'email' => 'Email address is invalid.',
     * ]
     * @return array
     */
    public static function getFirstErrors()
    {
        if (empty(self::$errors)) {
            return [];
        } else {
            $errors = [];
            foreach (self::$errors as $name => $es) {
                if (!empty($es)) {
                    $errors[$name] = reset($es);
                }
            }

            return $errors;
        }
    }

    /**
     * 返回所有验证错误消息
     * [
     *     'username' => [
     *         'Username is required.',
     *         'Username must contain only word characters.',
     *     ],
     *     'email' => [
     *         'Email address is invalid.',
     *     ]
     * ]
     * @return array
     */
    public static function getErrors()
    {
        return self::$errors;
    }

    /**
     * @param string|array $field
     * @return ValidateRuleMaker
     */
    public static function makeRule($field)
    {
        return new ValidateRuleMaker($field);
    }

}

class ValidateRuleMaker
{
    private $field;
    private $rules = [];

    public function __construct($field)
    {
        $this->field = $field;
    }

    public function trim()
    {
        $this->rules[] = [$this->field, 'trim'];
        return $this;
    }

    public function required()
    {
        $this->rules[] = [$this->field, 'required'];
        return $this;
    }

    public function length($value)
    {
        $this->rules[] = [$this->field, 'string', 'length' => $value];
        return $this;
    }

    public function email(array $rules = [])
    {
        $this->rules[] = array_merge([$this->field, 'email'], $rules);
        return $this;
    }

    public function defaultValue($value)
    {
        $this->rules[] = [$this->field, 'default', 'value' => $value];
        return $this;
    }

    public function boolean()
    {
        $this->rules[] = [$this->field, 'boolean'];
        return $this;
    }

    public function safe()
    {
        $this->rules[] = [$this->field, 'safe'];
        return $this;
    }

    /**
     * @return array
     */
    public function getRules()
    {
        return $this->rules;
    }
}