<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Whois;

use SuprunBohdan\IpInfo\Contracts\WhoisClient;
use SuprunBohdan\IpInfo\Exceptions\IpInfoException;

final class SocketWhoisClient implements WhoisClient
{
    public function query(string $server, string $query, int $timeout): string
    {
        $endpoint = 'tcp://'.$server.':43';
        $stream = @stream_socket_client(
            $endpoint,
            $errorCode,
            $errorMessage,
            $timeout,
            STREAM_CLIENT_CONNECT,
        );

        if ($stream === false) {
            throw new IpInfoException(sprintf(
                'WHOIS query failed for %s: [%d] %s',
                $server,
                $errorCode,
                $errorMessage,
            ));
        }

        stream_set_timeout($stream, $timeout);

        if (@fwrite($stream, $query."\r\n") === false) {
            fclose($stream);

            throw new IpInfoException('Unable to write WHOIS query to '.$server.'.');
        }

        $response = stream_get_contents($stream);
        fclose($stream);

        if (! is_string($response) || $response === '') {
            throw new IpInfoException('Empty WHOIS response from '.$server.'.');
        }

        return $response;
    }
}
