<?php

namespace Symfony\Component\Mailer\Bridge\Mailgun;

/**
 * @author Michael Garifullin <garifullin@gmail.com>
 *
 * @experimental in 4.3
 */
class Mailgun
{
    public const REGION_EU = 'EU';
    public const REGION_US = 'US';

    private const SMTP_DOMAIN_US = 'smtp.mailgun.org';
    private const SMTP_DOMAIN_EU = 'smtp.eu.mailgun.org';

    private const ENDPOINT_DOMAIN_EU = 'api.eu.mailgun.net';
    private const ENDPOINT_DOMAIN_US = 'api.mailgun.net';

    private const HTTP_API_ENDPOINT = 'https://%s/v3/%s/messages';
    private const HTTP_ENDPOINT = 'https://%s/v3/%s/messages.mime';

    /**
     * @param string $region
     * @return string
     */
    public static function resolveSmtpDomainByRegion(string $region = self::REGION_US): string
    {
        if (self::REGION_EU === $region) {
            return self::SMTP_DOMAIN_EU;
        }

        return self::SMTP_DOMAIN_US;
    }

    /**
     * @param string $domain
     * @param string $region
     * @return string
     */
    public static function resolveApiEndpoint(string $domain, string $region = self::REGION_US): string
    {
        return sprintf(self::HTTP_API_ENDPOINT, self::resolveHttpDomainByRegion($region), urlencode($domain));
    }

    /**
     * @param string $domain
     * @param string $region
     * @return string
     */
    public static function resolveHttpEndpoint(string $domain, string $region = self::REGION_US): string
    {
        return sprintf(self::HTTP_ENDPOINT, self::resolveHttpDomainByRegion($region), urlencode($domain));
    }

    /**
     * @param string $region
     * @return string
     */
    private static function resolveHttpDomainByRegion(string $region = self::ENDPOINT_DOMAIN_US): string
    {
        if (self::REGION_EU === $region) {
            return self::ENDPOINT_DOMAIN_EU;
        }

        return self::ENDPOINT_DOMAIN_US;
    }
}
