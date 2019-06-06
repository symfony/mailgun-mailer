<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Mailer\Bridge\Mailgun\Http\Api;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\Bridge\Mailgun\Mailgun;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SmtpEnvelope;
use Symfony\Component\Mailer\Transport\Http\Api\AbstractApiTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Kevin Verschaeve
 *
 * @experimental in 4.3
 */
class MailgunTransport extends AbstractApiTransport
{

    private $key;
    private $domain;
    private $region;

    public function __construct(string $key, string $domain, string $region = Mailgun::REGION_US, HttpClientInterface $client = null, EventDispatcherInterface $dispatcher = null, LoggerInterface $logger = null)
    {
        $this->key = $key;
        $this->domain = $domain;
        $this->region = $region;

        parent::__construct($client, $dispatcher, $logger);
    }

    protected function doSendEmail(Email $email, SmtpEnvelope $envelope): void
    {
        $body = new FormDataPart($this->getPayload($email, $envelope));
        $headers = [];
        foreach ($body->getPreparedHeaders()->getAll() as $header) {
            $headers[] = $header->toString();
        }

        $response = $this->client->request('POST', Mailgun::resolveApiEndpoint($this->domain, $this->region), [
            'auth_basic' => 'api:'.$this->key,
            'headers' => $headers,
            'body' => $body->bodyToIterable(),
        ]);

        if (200 !== $response->getStatusCode()) {
            $error = $response->toArray(false);

            throw new TransportException(sprintf('Unable to send an email: %s (code %s).', $error['message'], $response->getStatusCode()));
        }
    }

    private function getPayload(Email $email, SmtpEnvelope $envelope): array
    {
        $headers = $email->getHeaders();
        $html = $email->getHtmlBody();
        if (null !== $html) {
            if (stream_get_meta_data($html)['seekable'] ?? false) {
                rewind($html);
            }
            $html = stream_get_contents($html);
        }
        [$attachments, $inlines, $html] = $this->prepareAttachments($email, $html);

        $payload = [
            'from' => $envelope->getSender()->toString(),
            'to' => implode(',', $this->stringifyAddresses($this->getRecipients($email, $envelope))),
            'subject' => $email->getSubject(),
            'attachment' => $attachments,
            'inline' => $inlines,
        ];
        if ($emails = $email->getCc()) {
            $payload['cc'] = implode(',', $this->stringifyAddresses($emails));
        }
        if ($emails = $email->getBcc()) {
            $payload['bcc'] = implode(',', $this->stringifyAddresses($emails));
        }
        if ($email->getTextBody()) {
            $payload['text'] = $email->getTextBody();
        }
        if ($html) {
            $payload['html'] = $html;
        }

        $headersToBypass = ['from', 'to', 'cc', 'bcc', 'subject', 'content-type'];
        foreach ($headers->getAll() as $name => $header) {
            if (\in_array($name, $headersToBypass, true)) {
                continue;
            }

            $payload['h:'.$name] = $header->toString();
        }

        return $payload;
    }

    private function prepareAttachments(Email $email, ?string $html): array
    {
        $attachments = $inlines = [];
        foreach ($email->getAttachments() as $attachment) {
            $headers = $attachment->getPreparedHeaders();
            if ('inline' === $headers->getHeaderBody('Content-Disposition')) {
                // replace the cid with just a file name (the only supported way by Mailgun)
                if ($html) {
                    $filename = $headers->getHeaderParameter('Content-Disposition', 'filename');
                    $new = basename($filename);
                    $html = str_replace('cid:'.$filename, 'cid:'.$new, $html);
                    $p = new \ReflectionProperty($attachment, 'filename');
                    $p->setAccessible(true);
                    $p->setValue($attachment, $new);
                }
                $inlines[] = $attachment;
            } else {
                $attachments[] = $attachment;
            }
        }

        return [$attachments, $inlines, $html];
    }
}
