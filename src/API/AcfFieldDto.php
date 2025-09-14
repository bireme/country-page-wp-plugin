<?php
namespace CP\API;

if (!defined('ABSPATH')) exit;

final class AcfField {
    public string $key;
    public mixed $value;
    public string $type;

    public function __construct(string $key, mixed $value, string $type = 'string') {
        $this->key   = $key;
        $this->value = $value;
        $this->type  = $type;
    }

    public function toArray(): array {
        return [
            'key'   => $this->key,
            'value' => $this->value,
            'type'  => $this->type,
        ];
    }
}
