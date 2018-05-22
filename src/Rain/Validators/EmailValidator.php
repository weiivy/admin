<?php

namespace Rain\Validators;

/**
 * 邮箱验证器
 */
class EmailValidator extends Validator
{
    /**
     * @var string the regular expression used to validate the attribute value.
     * @see http://www.regular-expressions.info/email.html
     */
    public $pattern = '/^[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/';
    /**
     * @var string the regular expression used to validate email addresses with the name part.
     * This property is used only when [[allowName]] is true.
     * @see allowName
     */
    public $fullPattern = '/^[^@]*<[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?>$/';
    /**
     * @var boolean whether to allow name in the email address (e.g. "John Smith <john.smith@example.com>"). Defaults to false.
     * @see fullPattern
     */
    public $allowName = false;
    /**
     * @var boolean whether to check whether the email's domain exists and has either an A or MX record.
     * Be aware that this check can fail due to temporary DNS problems even if the email address is
     * valid and an email would be deliverable. Defaults to false.
     */
    public $checkDNS = false;
    /**
     * @var boolean whether validation process should take into account IDN (internationalized domain
     * names). Defaults to false meaning that validation of emails containing IDN will always fail.
     * Note that in order to use IDN validation you have to install and enable `intl` PHP extension,
     * otherwise an exception would be thrown.
     */
    public $enableIDN = false;


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if ($this->enableIDN && !function_exists('idn_to_ascii')) {
            throw new Exception('In order to use IDN validation intl extension must be installed and enabled.');
        }
        if ($this->message === null) {
            //$this->message = '{attribute} is not a valid email address.';
            $this->message = '{attribute}不是一个有效的邮箱地址';
        }
    }

    /**
     * @inheritdoc
     */
    protected function validateValue(&$value)
    {
        // make sure string length is limited to avoid DOS attacks
        if (!is_string($value) || strlen($value) >= 320) {
            $valid = false;
        } elseif (!preg_match('/^(.*<?)(.*)@(.*)(>?)$/', $value, $matches)) {
            $valid = false;
        } else {
            $domain = $matches[3];
            if ($this->enableIDN) {
                $value = $matches[1] . idn_to_ascii($matches[2]) . '@' . idn_to_ascii($domain) . $matches[4];
            }
            $valid = preg_match($this->pattern, $value) || $this->allowName && preg_match($this->fullPattern, $value);
            if ($valid && $this->checkDNS) {
                $valid = checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
            }
        }

        return $valid ? null : [$this->message, []];
    }

}
