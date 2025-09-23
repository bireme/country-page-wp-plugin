<?php
namespace CP\API;

if (!defined('ABSPATH')) exit;

/**
 * DTO para normalizar dados de journals da API BVS Saúde
 */
final class JournalDto {
    public ?string $id;
    public ?string $title;
    public ?string $issn;
    public ?string $eissn;
    public ?string $publisher;
    public ?string $country;
    public ?array $languages;
    public ?string $status;
    public ?string $subject_area;
    public ?string $url;
    public ?array $collections;
    public ?string $created_date;
    public ?string $updated_date;

    public function __construct(array $data = []) {
        $this->id = $data['id'] ?? null;
        $this->title = $data['title'] ?? $data['journal_title'] ?? $this->extractFirstValue($data['shortened_title'] ?? null);
        
        // Normalizar campos que podem vir como arrays da API BVS
        $this->issn = $this->extractFirstValue($data['issn'] ?? null);
        $this->eissn = $this->extractFirstValue($data['eissn'] ?? $data['e_issn'] ?? null);
        $this->publisher = $this->extractFirstValue($data['publisher'] ?? $data['responsibility_mention'] ?? null);
        $this->country = $this->extractFirstValue($data['country'] ?? null);
        $this->status = $this->extractFirstValue($data['status'] ?? null);

        $subjectArea = $data['subject_area'] ?? $data['subjectArea'] ?? $data['descriptor'] ?? $data['mh'] ?? null;
        $this->subject_area = $this->extractFirstValue($subjectArea);
        
        $this->url = $this->extractFirstValue($data['url'] ?? $data['journal_url'] ?? $data['link'] ?? null);
        $this->created_date = $this->extractFirstValue($data['created_date'] ?? $data['createdDate'] ?? null);
        $this->updated_date = $this->extractFirstValue($data['updated_date'] ?? $data['updatedDate'] ?? null);

        $this->languages = $this->normalizeToArray($data['languages'] ?? $data['language'] ?? []);
        $this->collections = $this->normalizeToArray($data['collections'] ?? []);
    }

    /**
     * Converte o DTO para array
     */
    public function toArray(): array {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'issn' => $this->issn,
            'eissn' => $this->eissn,
            'publisher' => $this->publisher,
            'country' => $this->country,
            'languages' => $this->languages,
            'status' => $this->status,
            'subject_area' => $this->subject_area,
            'url' => $this->url,
            'collections' => $this->collections,
            'created_date' => $this->created_date,
            'updated_date' => $this->updated_date,
        ];
    }

    /**
     * Retorna apenas os campos principais para exibição
     */
    public function getDisplayData(): array {
        return [
            'title' => $this->title,
            'issn' => $this->issn,
            'publisher' => $this->publisher,
            'country' => $this->country,
            'languages' => $this->languages,
            'url' => $this->url,
        ];
    }


    public function isValid(): bool {
        return !empty($this->title) || !empty($this->id);
    }


    public function getPrimaryIssn(): ?string {
        return $this->issn ?: $this->eissn;
    }

    /**
     * Retorna as linguagens como string separada por vírgula
     */
    public function getLanguagesString(): string {
        if (!is_array($this->languages) || empty($this->languages)) {
            return '';
        }
        return implode(', ', $this->languages);
    }

    /**
     * Extrai o primeiro valor se for array, ou retorna o valor se for string/null
     */
    private function extractFirstValue($value): ?string {
        if (is_array($value)) {
            return !empty($value) ? (string)$value[0] : null;
        }
        return $value ? (string)$value : null;
    }

    /**
     * Normaliza valor para array
     */
    private function normalizeToArray($value): array {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value) && !empty($value)) {
            return [$value];
        }
        return [];
    }
}
