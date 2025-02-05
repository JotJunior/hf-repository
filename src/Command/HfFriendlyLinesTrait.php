<?php

namespace Jot\HfRepository\Command;

trait HfFriendlyLinesTrait
{

    public const MESSAGE_TYPES = [
        'success' => '✅',
        'error' => '🚫',
        'warning' => '⚠️',
        'waiting' => '⌛️',
        'finished' => '❤️',
        'critical' => '😱',
    ];

    protected function success(string $message, array $replacements = []): void
    {
        $this->validateReplacements($message, $replacements);
        $this->line($this->composeMessage(vsprintf($message, $replacements), 'success'));
    }

    protected function failed(string $message, array $replacements = []): void
    {
        $this->validateReplacements($message, $replacements);
        $this->line($this->composeMessage(vsprintf($message, $replacements), 'error'));
    }

    protected function waiting(string $message, array $replacements = []): void
    {
        $this->validateReplacements($message, $replacements);
        $this->line($this->composeMessage(vsprintf($message, $replacements), 'waiting'));
    }

    protected function warning(string $message, array $replacements = []): void
    {
        $this->validateReplacements($message, $replacements);
        $this->line($this->composeMessage(vsprintf($message, $replacements), 'warning'));
    }

    protected function critical(string $message, array $replacements = []): void
    {
        $this->validateReplacements($message, $replacements);
        $this->line($this->composeMessage(vsprintf($message, $replacements), 'critical'));
    }

    protected function finished(string $message, array $replacements = []): void
    {
        $this->validateReplacements($message, $replacements);
        $this->line($this->composeMessage(vsprintf($message, $replacements), 'finished'));
    }

    private function validateReplacements(string $message, array $replacements): void
    {
        $pattern = '/%(?:\d+\$)?[+-]?(?:\d+|\*)?(?:\.(?:\d+|\*))?[bcdeEfFgGosuxX]/';
        preg_match_all($pattern, $message, $matches);
        if (count($replacements) !== count($matches[0])) {
            throw new \Exception('Incorrect count of variables in the message string');
        }
    }

    private function composeMessage(string $message, string $type): string
    {
        $color = match ($type) {
            'error' => 'red',
            'success' => 'green',
            'warning' => 'yellow',
            default => 'white'
        };
        $emoji = self::MESSAGE_TYPES[$type] ?? '';
        $dateTime = new \DateTimeImmutable('now');
        return sprintf('<fg=%s>%s</> %s : %s', $color, $dateTime->format('Y-m-d\\TH:i:s.u'), $emoji, $message);
    }

}