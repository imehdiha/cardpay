<?php
declare(strict_types=1);

namespace CardPay\Core;

use RuntimeException;

final class View
{
    public function __construct(private readonly string $basePath) {}

    public function render(string $template, array $data = [], ?string $layout = null): string
    {
        $file = $this->basePath . '/' . $template . '.php';
        if (!is_file($file)) throw new RuntimeException("View not found: {$template}");
        extract($data, EXTR_SKIP);
        ob_start();
        try { require $file; $content = (string) ob_get_clean(); }
        catch (\Throwable $e) { ob_end_clean(); throw $e; }
        if ($layout === null) return $content;
        $layoutFile = $this->basePath . '/' . $layout . '.php';
        ob_start();
        try { require $layoutFile; return (string) ob_get_clean(); }
        catch (\Throwable $e) { ob_end_clean(); throw $e; }
    }

    public static function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function localDate(mixed $value, string $timezone = 'Asia/Tehran', string $format = 'Y/m/d H:i'): string
    {
        return Time::formatLocal($value, $timezone, $format);
    }
}
