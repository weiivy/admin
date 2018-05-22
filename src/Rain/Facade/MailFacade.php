<?php

namespace Rain\Facade;

class MailFacade
{
    public static $config = array(
        //'host' => 'smtp.qq.com',
        //'username' => '190196539@qq.com',
        //'password' => 'xxxxxx',
        //'name' => 'Ethan',
        //'port' => '465',
        //'encryption' => 'ssl', //ssl、tls
    );

    public static $error;

    /**
     * @param string $to
     * @param string $title
     * @param string $content
     * @return bool
     */
    public static function send($to, $title, $content)
    {
        try {
            // message
            $message = \Swift_Message::newInstance();
            $message->setFrom(array(static::$config['username'] => static::$config['name']));
            $message->setTo($to);
            $message->setSubject($title);
            $message->setBody($content, 'text/html', 'utf-8');
            //$message->attach(\Swift_Attachment::fromPath('pic.jpg', 'image/jpeg')->setFilename('rename_pic.jpg'));

            //transport
            $transport = \Swift_SmtpTransport::newInstance(static::$config['host'], static::$config['port']);
            $transport->setUsername(static::$config['username']);
            $transport->setPassword(static::$config['password']);
            if (isset(static::$config['encryption'])) {
                $transport->setEncryption(static::$config['encryption']);
            }

            //mailer
            $mailer = \Swift_Mailer::newInstance($transport);

            $mailer->send($message);
            return true;
        } catch (\Swift_ConnectionException $e) {
            static::$error = $e->getMessage();
            return false;
        }
    }

}