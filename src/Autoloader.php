<?php
namespace CP;

/** PSR-4: prefixo CP\ → src/, nome do arquivo = nome da classe. */

final class Autoloader {
    public static function init(string $prefix, string $baseDir): void {
        spl_autoload_register(function ($class) use ($prefix, $baseDir) {
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) return;
            $relative = substr($class, $len + 1);
            $file = $baseDir . '/' . str_replace('\\', '/', $relative) . '.php';
            if (file_exists($file)) require $file;
        });
    }
}
