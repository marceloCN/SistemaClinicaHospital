<?php

declare(strict_types=1);

namespace Lib\Support;

use InvalidArgumentException;
use RuntimeException;

class Mail
{
    private array $to = [];
    private array $cc = [];
    private array $bcc = [];
    private ?array $replyTo = null;
    private ?string $fromAddress = null;
    private ?string $fromName = null;
    private string $subject = '';

    public static function to(string|array $addresses): self
    {
        return (new self())->addAddresses($addresses, 'to');
    }

    public function cc(string|array $addresses): self
    {
        return $this->addAddresses($addresses, 'cc');
    }

    public function bcc(string|array $addresses): self
    {
        return $this->addAddresses($addresses, 'bcc');
    }

    public function replyTo(string $address, ?string $name = null): self
    {
        $this->replyTo = self::parseAddress($address, $name);

        return $this;
    }

    public function from(string $address, ?string $name = null): self
    {
        self::parseAddress($address);

        $this->fromAddress = $address;
        $this->fromName = $name;

        return $this;
    }

    public function subject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function send(string $html, ?string $text = null): void
    {
        if ($this->to === [] && $this->cc === [] && $this->bcc === []) {
            throw new InvalidArgumentException('Mail: indica al menos un destinatario con Mail::to(...)');
        }

        $fromAddress = $this->fromAddress ?? (string) config('mail.from.address', '');

        if ($fromAddress === '') {
            throw new InvalidArgumentException('Mail: configura MAIL_FROM_ADDRESS en .env o usa ->from()');
        }

        $fromName = $this->fromAddress !== null
            ? $this->fromName
            : ((string) config('mail.from.name', '') ?: null);

        $recipients = [];

        foreach (array_merge($this->to, $this->cc, $this->bcc) as $address) {
            $recipients[] = $address[0];
        }

        $message = $this->buildMessage($fromAddress, $fromName, $html, $text);

        $this->transmit($fromAddress, $recipients, $message);
    }

    private function addAddresses(string|array $addresses, string $field): self
    {
        foreach ((array) $addresses as $address) {
            $this->{$field}[] = self::parseAddress((string) $address);
        }

        return $this;
    }

    private function buildMessage(string $fromAddress, ?string $fromName, string $html, ?string $text): string
    {
        $headers = [];
        $headers[] = 'Date: ' . date('r');
        $headers[] = 'From: ' . self::formatAddress([$fromAddress, $fromName]);

        if ($this->to !== []) {
            $headers[] = 'To: ' . self::formatList($this->to);
        }

        if ($this->cc !== []) {
            $headers[] = 'Cc: ' . self::formatList($this->cc);
        }

        if ($this->replyTo !== null) {
            $headers[] = 'Reply-To: ' . self::formatAddress($this->replyTo);
        }

        $headers[] = 'Subject: ' . self::encodeHeader($this->subject);
        $headers[] = 'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . self::domainOf($fromAddress) . '>';
        $headers[] = 'MIME-Version: 1.0';

        if ($text === null) {
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
            $headers[] = 'Content-Transfer-Encoding: base64';

            $body = self::encodeBody($html);
        } else {
            $boundary = '=_alt_' . bin2hex(random_bytes(16));
            $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';

            $body = '--' . $boundary . "\r\n"
                . "Content-Type: text/plain; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: base64\r\n\r\n"
                . self::encodeBody($text) . "\r\n"
                . '--' . $boundary . "\r\n"
                . "Content-Type: text/html; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: base64\r\n\r\n"
                . self::encodeBody($html) . "\r\n"
                . '--' . $boundary . '--';
        }

        return implode("\r\n", $headers) . "\r\n\r\n" . $body;
    }

    private function transmit(string $fromAddress, array $recipients, string $message): void
    {
        $config = (array) config('mail.mailers.' . config('mail.default', 'smtp'), []);

        $host = (string) ($config['host'] ?? '');
        $port = (int) ($config['port'] ?? 587);
        $timeout = (int) ($config['timeout'] ?? 15);
        $encryption = strtolower((string) ($config['encryption'] ?? ''));
        $verifyPeer = (bool) ($config['verify_peer'] ?? true);

        if ($host === '') {
            throw new InvalidArgumentException('Mail: configura MAIL_HOST en .env');
        }

        if ($encryption === '') {
            $encryption = $port === 465 ? 'ssl' : 'auto';
        }

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => $verifyPeer,
                'verify_peer_name' => $verifyPeer,
                'peer_name' => $host,
            ],
        ]);

        $socket = @stream_socket_client(
            ($encryption === 'ssl' ? 'ssl' : 'tcp') . '://' . $host . ':' . $port,
            $errorCode,
            $errorMessage,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if ($socket === false) {
            throw new RuntimeException("Mail: no se pudo conectar a {$host}:{$port} ({$errorMessage})");
        }

        stream_set_timeout($socket, $timeout);

        try {
            $this->expect($socket, [220], 'saludo inicial');

            $capabilities = $this->command($socket, 'EHLO ' . self::clientDomain(), [250], 'EHLO');

            $useTls = $encryption === 'tls' || ($encryption === 'auto' && stripos($capabilities, 'STARTTLS') !== false);

            if ($useTls) {
                if (stripos($capabilities, 'STARTTLS') === false) {
                    throw new RuntimeException('Mail: el servidor SMTP no ofrece STARTTLS');
                }

                $this->command($socket, 'STARTTLS', [220], 'STARTTLS');

                if (@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) !== true) {
                    $tlsError = error_get_last()['message'] ?? '';

                    throw new RuntimeException('Mail: fallo el handshake TLS con ' . $host . ($tlsError !== '' ? ' (' . $tlsError . ')' : ''));
                }

                $capabilities = $this->command($socket, 'EHLO ' . self::clientDomain(), [250], 'EHLO (TLS)');
            }

            $username = (string) ($config['username'] ?? '');

            if ($username !== '') {
                $this->authenticate($socket, $username, (string) ($config['password'] ?? ''), $capabilities);
            }

            $this->command($socket, 'MAIL FROM:<' . $fromAddress . '>', [250], 'MAIL FROM');

            foreach ($recipients as $recipient) {
                $this->command($socket, 'RCPT TO:<' . $recipient . '>', [250, 251], 'RCPT TO');
            }

            $this->command($socket, 'DATA', [354], 'DATA');

            $data = (string) preg_replace('/\r\n|\r|\n/', "\r\n", $message);
            $data = (string) preg_replace('/^\./m', '..', $data);

            $this->write($socket, $data . "\r\n.\r\n");
            $this->expect($socket, [250], 'entrega del mensaje');

            @fwrite($socket, "QUIT\r\n");
        } finally {
            @fclose($socket);
        }
    }

    private function authenticate($socket, string $username, string $password, string $capabilities): void
    {
        $mechanisms = '';

        if (preg_match('/AUTH[ =]([A-Z0-9\- ]+)/i', $capabilities, $matches) === 1) {
            $mechanisms = strtoupper($matches[1]);
        }

        if ($mechanisms === '' || str_contains($mechanisms, 'LOGIN')) {
            $this->command($socket, 'AUTH LOGIN', [334], 'AUTH LOGIN');
            $this->command($socket, base64_encode($username), [334], 'usuario SMTP');
            $response = $this->command($socket, base64_encode($password), [235], 'clave SMTP');

            return;
        }

        if (str_contains($mechanisms, 'PLAIN')) {
            $this->write($socket, 'AUTH PLAIN ' . base64_encode("\0{$username}\0{$password}") . "\r\n");
            $this->expect($socket, [235], 'AUTH PLAIN');

            return;
        }

        throw new RuntimeException('Mail: el servidor SMTP no ofrece AUTH LOGIN ni AUTH PLAIN');
    }

    private function command($socket, string $command, array $expected, string $context): string
    {
        $this->write($socket, $command . "\r\n");

        [$code, $text] = $this->readResponse($socket);

        if (!in_array($code, $expected, true)) {
            throw new RuntimeException("Mail SMTP ({$context}): respuesta inesperada del servidor: {$text}");
        }

        return $text;
    }

    private function expect($socket, array $expected, string $context): void
    {
        [$code, $text] = $this->readResponse($socket);

        if (!in_array($code, $expected, true)) {
            throw new RuntimeException("Mail SMTP ({$context}): respuesta inesperada del servidor: {$text}");
        }
    }

    private function write($socket, string $data): void
    {
        if (@fwrite($socket, $data) === false) {
            throw new RuntimeException('Mail: la conexion SMTP se interrumpio durante el envio');
        }
    }

    private function readResponse($socket): array
    {
        $text = '';
        $line = null;

        while (($line = fgets($socket, 2048)) !== false) {
            $text .= $line;

            if (strlen(rtrim($line, "\r\n")) < 4 || substr($line, 3, 1) !== '-') {
                break;
            }
        }

        if ($text === '' || $line === false || (stream_get_meta_data($socket)['timed_out'] ?? false)) {
            throw new RuntimeException('Mail: el servidor SMTP no respondio (timeout o conexion cerrada)');
        }

        return [(int) substr(trim($text), 0, 3), trim($text)];
    }

    private static function parseAddress(string $value, ?string $name = null): array
    {
        $value = trim($value);

        if (preg_match('/^(.*)<([^<>]+)>$/', $value, $matches) === 1) {
            $parsedName = trim(trim($matches[1]), '"\'');
            $name = $parsedName !== '' ? $parsedName : $name;
            $value = trim($matches[2]);
        }

        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException("Mail: direccion de correo invalida: {$value}");
        }

        return [$value, $name];
    }

    private static function formatList(array $addresses): string
    {
        return implode(', ', array_map([self::class, 'formatAddress'], $addresses));
    }

    private static function formatAddress(array $address): string
    {
        [$email, $name] = $address;

        if ($name === null || $name === '') {
            return $email;
        }

        if (preg_match('/[^\x20-\x7E]/', $name) === 1) {
            return self::encodeHeader($name) . ' <' . $email . '>';
        }

        if (preg_match('/[",;:<>@()\[\]\\\\]/', $name) === 1) {
            $name = '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $name) . '"';
        }

        return $name . ' <' . $email . '>';
    }

    private static function encodeHeader(string $value): string
    {
        if ($value === '' || preg_match('/[^\x20-\x7E]/', $value) !== 1) {
            return $value;
        }

        $characters = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [$value];
        $chunks = [];
        $chunk = '';

        foreach ($characters as $character) {
            if ($chunk !== '' && strlen($chunk . $character) > 42) {
                $chunks[] = $chunk;
                $chunk = '';
            }

            $chunk .= $character;
        }

        if ($chunk !== '') {
            $chunks[] = $chunk;
        }

        return implode(' ', array_map(
            static fn (string $part): string => '=?UTF-8?B?' . base64_encode($part) . '?=',
            $chunks
        ));
    }

    private static function encodeBody(string $value): string
    {
        return rtrim(chunk_split(base64_encode($value), 76, "\r\n"), "\r\n");
    }

    private static function domainOf(string $address): string
    {
        $at = strrpos($address, '@');

        return $at === false ? 'localhost' : substr($address, $at + 1);
    }

    private static function clientDomain(): string
    {
        $hostname = parse_url((string) config('app.url', ''), PHP_URL_HOST);

        return is_string($hostname) && $hostname !== '' ? $hostname : 'localhost';
    }
}
