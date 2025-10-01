<?php

declare(strict_types=1);

namespace App\Service;

use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;
use Swoole\Runtime;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;

class MailerFactory
{
    private const DSN_FORMAT = '%s://%s%s%s:%d';

    public function __invoke(ContainerInterface $container): MailerInterface
    {
        $config = $container->get(ConfigInterface::class);
        $mailConfig = $config->get('mail.default', []);

        $transportConfig = $mailConfig['transport'] ?? [];
        $scheme = $transportConfig['scheme'] ?? 'smtp';
        $host = $transportConfig['host'] ?? 'localhost';
        $port = $transportConfig['port'] ?? 25;
        $username = $transportConfig['username'] ?? '';
        $password = $transportConfig['password'] ?? '';

        $dsn = sprintf(
            self::DSN_FORMAT,
            $scheme,
            $username ? urlencode($username) : '',
            $password ? ':' . urlencode($password) . '@' : ($username ? '@' : ''),
            $host,
            $port
        );

        $transport = Transport::fromDsn($dsn);
        $mailer = new Mailer($transport);

        return new class($mailer) implements MailerInterface {
            public function __construct(private MailerInterface $mailer)
            {
            }

            public function send($message, $envelope = null): void
            {
                $flags = Runtime::getHookFlags();
                Runtime::setHookFlags(0);

                try {
                    $this->mailer->send($message, $envelope);
                } finally {
                    Runtime::setHookFlags($flags);
                }
            }
        };
    }
}
