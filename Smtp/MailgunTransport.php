<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Mailer\Bridge\Mailgun\Smtp;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\Bridge\Mailgun\Mailgun;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

/**
 * @author Kevin Verschaeve
 *
 * @experimental in 4.3
 */
class MailgunTransport extends EsmtpTransport
{
    public function __construct(string $username, string $password, string $region = Mailgun::REGION_US, EventDispatcherInterface $dispatcher = null, LoggerInterface $logger = null)
    {
        parent::__construct(Mailgun::resolveSmtpDomainByRegion($region), 465, 'ssl', null, $dispatcher, $logger);

        $this->setUsername($username);
        $this->setPassword($password);
    }
}
