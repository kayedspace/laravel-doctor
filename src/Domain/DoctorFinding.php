<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Domain;

use kayedspace\Doctor\Domain\Enums\Confidence;
use kayedspace\Doctor\Domain\Enums\Severity;
use kayedspace\Doctor\Support\Runtime\SecretRedactor;

class DoctorFinding
{
    /**
     * Create a new finding instance.
     */
    public function __construct(
        public string $id = '',
        public string $ruleId = '',
        public string $title = '',
        public string $message = '',
        public Severity $severity = Severity::Info,
        public Confidence $confidence = Confidence::Medium,
        public string $evidence = '',
        public ?string $file = null,
        public ?int $line = null,
        public ?string $remediation = null,
        public array $tags = [],
    ) {}

    public static function make(string $ruleId): self
    {
        return new self(ruleId: $ruleId);
    }

    public function id(string $id): self
    {
        if (empty($id)) {
            throw new \InvalidArgumentException('Finding ID must not be empty');
        }
        $this->id = $id;

        return $this;
    }

    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function message(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function severity(Severity $severity): self
    {
        $this->severity = $severity;

        return $this;
    }

    public function confidence(Confidence $confidence): self
    {
        $this->confidence = $confidence;

        return $this;
    }

    public function evidence(string $evidence): self
    {
        if (empty($evidence)) {
            throw new \InvalidArgumentException('Evidence must not be empty');
        }
        $this->evidence = $evidence;

        return $this;
    }

    public function file(?string $file): self
    {
        if ($file !== null) {
            if (str_starts_with($file, '/') || str_contains($file, '..')) {
                throw new \InvalidArgumentException('Path must be project-relative and not contain traversal');
            }
        }
        $this->file = $file;

        return $this;
    }

    public function line(?int $line): self
    {
        $this->line = $line;

        return $this;
    }

    public function remediation(?string $remediation): self
    {
        if ($remediation === '') {
            throw new \InvalidArgumentException('Remediation must not be empty');
        }
        $this->remediation = $remediation;

        return $this;
    }

    public function tags(array $tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * Get the array representation of the finding, redacting any secrets in the evidence.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'ruleId' => $this->ruleId,
            'rule' => $this->ruleId,
            'title' => $this->title,
            'message' => $this->message,
            'severity' => $this->severity->value,
            'confidence' => $this->confidence->value,
            'evidence' => $this->redactSecrets($this->evidence),
            'file' => $this->file,
            'line' => $this->line,
            'remediation' => $this->remediation,
            'tags' => $this->tags,
        ];
    }

    /**
     * Redact secret-looking values from a string.
     */
    private function redactSecrets(string $text): string
    {
        $pattern = '/\b([a-zA-Z0-9_\-]*(?:password|passwd|secret|token|key|cert|sig|credential|private|auth|pass)[a-zA-Z0-9_\-]*)(\s*(=|:|=>)\s*)([\'"]?)([^\n\'"\s]{4,})\4/i';

        return preg_replace($pattern, '$1$2$4[REDACTED]$4', $text);
    }

    /**
     * Convert the finding to a compact array representation.
     *
     * @return array<string, mixed>
     */
    public function toCompactArray(): array
    {
        $file = $this->file ?? '';
        $line = $this->line;
        $location = 'project';

        if ($file !== '') {
            $location = $line !== null ? $file.':'.$line : $file;
        }

        return [
            'rule' => $this->ruleId,
            'severity' => $this->severity->value,
            'location' => $location,
            'message' => SecretRedactor::redact($this->message),
        ];
    }

    /**
     * Get the remediation string, with a fallback if none exists.
     */
    public function getFallbackRemediation(): string
    {
        return SecretRedactor::redact($this->remediation ?? 'doctor_explain_rule '.$this->ruleId);
    }

    /**
     * Convert the finding to a markdown string representation.
     */
    public function toMarkdown(): string
    {
        $compact = $this->toCompactArray();

        return implode("\n", [
            '### Finding: '.$this->ruleId,
            'Rule: '.$this->ruleId,
            'Severity: '.$compact['severity'],
            'Location: '.$compact['location'],
            'Message: '.$compact['message'],
            'Remediation: '.$this->getFallbackRemediation(),
        ]);
    }

    /**
     * Create a DoctorFinding from an array.
     */
    public static function fromArray(array $data): self
    {
        $severity = $data['severity'] ?? 'info';
        if (is_string($severity)) {
            $severity = Severity::tryFrom($severity) ?? Severity::Info;
        }

        $confidence = $data['confidence'] ?? 'medium';
        if (is_string($confidence)) {
            $confidence = Confidence::tryFrom($confidence) ?? Confidence::Medium;
        }

        $finding = self::make($data['ruleId'] ?? $data['rule'] ?? '')
            ->id($data['id'] ?? uniqid())
            ->title($data['title'] ?? '')
            ->message($data['message'] ?? '')
            ->severity($severity)
            ->confidence($confidence)
            ->evidence($data['evidence'] ?? '');

        if (isset($data['file'])) {
            $finding->file($data['file']);
        }
        if (isset($data['line'])) {
            $finding->line($data['line']);
        }
        if (isset($data['remediation'])) {
            $finding->remediation($data['remediation']);
        }
        if (isset($data['tags'])) {
            $finding->tags($data['tags']);
        }

        return $finding;
    }
}
