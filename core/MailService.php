<?php
namespace Core;

class MailService
{
    public static function sendTemplate(string $to, string $templateKey, array $vars = []): bool
    {
        $stmt = Connection::prepare('SELECT subject, body_doc_xml, html_cache, text_cache FROM #__mail_templates WHERE template_key = :key AND is_enabled = 1 LIMIT 1');
        $stmt->execute([':key' => $templateKey]);
        $row = $stmt->fetch();
        if (!$row) {
            Logger::error('mail', 'Template missing', ['template' => $templateKey]);
            return false;
        }

        $subject = self::applyVars((string)$row['subject'], $vars);
        $contentHtml = '';
        $contentText = '';
        if (!empty($row['html_cache']) || !empty($row['text_cache'])) {
            $contentHtml = self::applyVars((string)($row['html_cache'] ?? ''), $vars);
            $contentText = self::applyVars((string)($row['text_cache'] ?? ''), $vars);
        } else {
            $rendered = Freewrite::render((string)$row['body_doc_xml']);
            $contentHtml = self::applyVars($rendered['html'], $vars);
            $contentText = self::applyVars($rendered['text'], $vars);
        }

        return self::sendWrapped($to, $subject, $contentText, $contentHtml);
    }

    public static function sendRaw(string $to, string $subject, ?string $textBody, ?string $htmlBody): bool
    {
        $textBody = $textBody ?? '';
        $htmlBody = $htmlBody ?? '';
        return self::sendWrapped($to, $subject, $textBody, $htmlBody);
    }

    private static function sendWrapped(string $to, string $subject, string $contentText, string $contentHtml): bool
    {
        $normalized = self::normalizeBodies($contentText, $contentHtml);
        $contentText = $normalized['text'];
        $contentHtml = $normalized['html'];
        if ($contentText === '' && $contentHtml === '') {
            Logger::error('mail', 'Mail content missing', []);
            return false;
        }

        $wrapped = self::wrapContent($contentText, $contentHtml);
        if ($wrapped === null) {
            return false;
        }

        $wrappedText = $wrapped['text'];
        $wrappedHtml = $wrapped['html'];

        if (!self::isMailConfigured()) {
            Logger::error('mail', 'Mail settings invalid', []);
            self::logMail('warning', 'Mail send failed', ['to' => $to]);
            return false;
        }

        $fromEmail = (string)Settings::get('mail_from_email', '', 'global');
        $fromName = (string)Settings::get('mail_from_name', '', 'global');
        $forcePlain = (bool)Settings::get('mail_force_plain_text', false, 'global');
        $transport = (string)Settings::get('mail_transport', 'php_mail', 'global');

        $headers = [];
        $headers[] = 'From: ' . $fromName . ' <' . $fromEmail . '>';
        $headers[] = 'MIME-Version: 1.0';

        $body = '';
        if ($forcePlain) {
            $headers[] = 'Content-Type: text/plain; charset=UTF-8';
            $body = $wrappedText;
        } else {
            $boundary = 'ffcms_' . bin2hex(random_bytes(8));
            $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
            $body = "--{$boundary}\r\n";
            $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
            $body .= $wrappedText . "\r\n";
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
            $body .= $wrappedHtml . "\r\n";
            $body .= "--{$boundary}--";
        }

        $sent = false;
        if ($transport === 'smtp') {
            $sent = self::sendSmtp($to, $subject, $body, $headers);
        } else {
            $sent = mail($to, $subject, $body, implode("\r\n", $headers));
        }

        if ($sent) {
            self::logMail('info', 'Mail sent', ['to' => $to]);
            return true;
        }

        self::logMail('warning', 'Mail send failed', ['to' => $to]);
        return false;
    }

    private static function isMailConfigured(): bool
    {
        $fromEmail = (string)Settings::get('mail_from_email', '', 'global');
        $fromName = (string)Settings::get('mail_from_name', '', 'global');
        if ($fromEmail === '' || $fromName === '') {
            return false;
        }

        $transport = (string)Settings::get('mail_transport', 'php_mail', 'global');
        if ($transport === 'smtp') {
            $host = (string)Settings::get('smtp_host', '', 'global');
            $port = (int)Settings::get('smtp_port', 0, 'global');
            return $host !== '' && $port > 0;
        }

        return true;
    }

    private static function getDefaultWrapper(): ?array
    {
        $stmt = Connection::prepare('SELECT wrapper_key, wrapper_doc_xml FROM #__mail_wrappers WHERE is_default = 1 AND is_enabled = 1 LIMIT 1');
        $stmt->execute();
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function wrapContent(string $contentText, string $contentHtml): ?array
    {
        $wrapper = self::getDefaultWrapper();
        $simpleWrapperXml = '<freewrite version="1"><block type="shortcode">[mailcontent]</block></freewrite>';
        if ($wrapper === null) {
            // Fallback to simple wrapper
            return self::wrapWithWrapperXml($simpleWrapperXml, 'fallback', $contentText, $contentHtml);
        }

        $wrapperRendered = Freewrite::render($wrapper['wrapper_doc_xml']);
        $result = self::applyWrapper($wrapperRendered, $wrapper['wrapper_key'], $contentText, $contentHtml);
        if ($result === null) {
            // Wrapper invalid, fallback
            return self::wrapWithWrapperXml($simpleWrapperXml, 'fallback', $contentText, $contentHtml);
        }
        return $result;
    }

    public static function wrapWithWrapperXml(string $wrapperXml, string $wrapperKey, string $contentText, string $contentHtml): ?array
    {
        $wrapperRendered = Freewrite::render($wrapperXml);
        return self::applyWrapper($wrapperRendered, $wrapperKey, $contentText, $contentHtml);
    }

    private static function applyWrapper(array $wrapperRendered, string $wrapperKey, string $contentText, string $contentHtml): ?array
    {
        if (trim($wrapperRendered['html']) === '' && trim($wrapperRendered['text']) === '') {
            Logger::error('mail', 'Wrapper render empty', ['wrapper' => $wrapperKey]);
            return null;
        }

        $htmlCount = substr_count($wrapperRendered['html'], '[mailcontent]');
        $textCount = substr_count($wrapperRendered['text'], '[mailcontent]');
        if ($htmlCount !== 1 || $textCount !== 1) {
            Logger::error('mail', 'Wrapper placeholder invalid', [
                'wrapper' => $wrapperKey,
                'html_count' => $htmlCount,
                'text_count' => $textCount,
            ]);
            return null;
        }

        return [
            'html' => str_replace('[mailcontent]', $contentHtml, $wrapperRendered['html']),
            'text' => str_replace('[mailcontent]', $contentText, $wrapperRendered['text']),
        ];
    }

    private static function sendSmtp(string $to, string $subject, string $body, array $headers): bool
    {
        $host = (string)Settings::get('smtp_host', '', 'global');
        $port = (int)Settings::get('smtp_port', 25, 'global');
        $security = (string)Settings::get('smtp_security', 'none', 'global');
        $username = (string)Settings::get('smtp_username', '', 'global');
        $password = (string)Settings::get('smtp_password', '', 'global');

        $remote = ($security === 'ssl' ? 'ssl://' : '') . $host;
        $socket = fsockopen($remote, $port, $errno, $errstr, 10);
        if (!$socket) {
            Logger::error('mail', 'SMTP connection failed', ['error' => $errstr, 'host' => $host, 'port' => $port]);
            return false;
        }

        $read = static function () use ($socket): string {
            $data = '';
            while (!feof($socket)) {
                $line = fgets($socket, 515);
                if ($line === false) {
                    break;
                }
                $data .= $line;
                if (isset($line[3]) && $line[3] === ' ') {
                    break;
                }
            }
            return $data;
        };

        $send = static function (string $command) use ($socket): void {
            fwrite($socket, $command . "\r\n");
        };

        $checkResponse = static function (string $response, array $expectedCodes): bool {
            $code = (int)substr($response, 0, 3);
            return in_array($code, $expectedCodes, true);
        };

        $response = $read();
        if (!$checkResponse($response, [220])) {
            Logger::error('mail', 'SMTP greeting failed', ['response' => trim($response)]);
            fclose($socket);
            return false;
        }

        $send('EHLO localhost');
        $response = $read();
        if (!$checkResponse($response, [250])) {
            Logger::error('mail', 'SMTP EHLO failed', ['response' => trim($response)]);
            fclose($socket);
            return false;
        }

        if ($security === 'tls') {
            $send('STARTTLS');
            $response = $read();
            if (!$checkResponse($response, [220])) {
                Logger::error('mail', 'SMTP STARTTLS failed', ['response' => trim($response)]);
                fclose($socket);
                return false;
            }
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $send('EHLO localhost');
            $response = $read();
            if (!$checkResponse($response, [250])) {
                Logger::error('mail', 'SMTP EHLO after TLS failed', ['response' => trim($response)]);
                fclose($socket);
                return false;
            }
        }

        if ($username !== '') {
            $send('AUTH LOGIN');
            $response = $read();
            if (!$checkResponse($response, [334])) {
                Logger::error('mail', 'SMTP AUTH LOGIN failed', ['response' => trim($response)]);
                fclose($socket);
                return false;
            }
            $send(base64_encode($username));
            $response = $read();
            if (!$checkResponse($response, [334])) {
                Logger::error('mail', 'SMTP username failed', ['response' => trim($response)]);
                fclose($socket);
                return false;
            }
            if ($password !== '') {
                $send(base64_encode($password));
                $response = $read();
                if (!$checkResponse($response, [235])) {
                    Logger::error('mail', 'SMTP password failed', ['response' => trim($response)]);
                    fclose($socket);
                    return false;
                }
            }
        }

        $fromEmail = (string)Settings::get('mail_from_email', '', 'global');
        $send('MAIL FROM:<' . $fromEmail . '>');
        $response = $read();
        if (!$checkResponse($response, [250])) {
            Logger::error('mail', 'SMTP MAIL FROM failed', ['response' => trim($response), 'from' => $fromEmail]);
            fclose($socket);
            return false;
        }
        $send('RCPT TO:<' . $to . '>');
        $response = $read();
        if (!$checkResponse($response, [250])) {
            Logger::error('mail', 'SMTP RCPT TO failed', ['response' => trim($response), 'to' => $to]);
            fclose($socket);
            return false;
        }
        $send('DATA');
        $response = $read();
        if (!$checkResponse($response, [354])) {
            Logger::error('mail', 'SMTP DATA failed', ['response' => trim($response)]);
            fclose($socket);
            return false;
        }

        $headerLines = implode("\r\n", $headers);
        $message = $headerLines . "\r\nSubject: " . $subject . "\r\nTo: " . $to . "\r\n\r\n" . $body . "\r\n.";
        $send($message);
        $response = $read();
        if (!$checkResponse($response, [250])) {
            Logger::error('mail', 'SMTP message send failed', ['response' => trim($response)]);
            fclose($socket);
            return false;
        }
        $send('QUIT');
        fclose($socket);
        return true;
    }

    private static function logMail(string $level, string $message, array $context): void
    {
        $enabled = (bool)Settings::get('mail_log_enabled', true, 'global');
        if (!$enabled) {
            return;
        }
        if ($level === 'info') {
            Logger::info('mail', $message, $context);
        } elseif ($level === 'warning') {
            Logger::warning('mail', $message, $context);
        } else {
            Logger::error('mail', $message, $context);
        }
    }

    private static function applyVars(string $text, array $vars): string
    {
        if (!$vars) {
            return $text;
        }
        $replace = [];
        foreach ($vars as $key => $value) {
            $replace['{{' . $key . '}}'] = (string)$value;
        }
        return strtr($text, $replace);
    }

    private static function normalizeBodies(string $textBody, string $htmlBody): array
    {
        $textBody = trim($textBody);
        $htmlBody = trim($htmlBody);
        if ($textBody === '' && $htmlBody === '') {
            return ['text' => '', 'html' => ''];
        }
        if ($textBody === '' && $htmlBody !== '') {
            $textBody = trim(strip_tags($htmlBody));
        }
        if ($htmlBody === '' && $textBody !== '') {
            $escaped = Template::escape($textBody);
            $htmlBody = '<p>' . nl2br($escaped) . '</p>';
        }
        return ['text' => $textBody, 'html' => $htmlBody];
    }
}
