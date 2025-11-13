<?php

namespace App\Services;

use App\Models\Tenant\Account;

class DocumentValidationService
{
    /**
     * Validate CPF (Brazilian individual taxpayer ID)
     */
    public function validateCPF(string $cpf): bool
    {
        // Remove non-numeric characters
        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        // Check if has 11 digits
        if (strlen($cpf) !== 11) {
            return false;
        }

        // Check if all digits are the same (invalid CPF)
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        // Validate first check digit
        $sum = 0;

        for ($i = 0; $i < 9; $i++) {
            $sum += intval($cpf[$i]) * (10 - $i);
        }
        $remainder = $sum % 11;
        $digit1 = $remainder < 2 ? 0 : 11 - $remainder;

        if (intval($cpf[9]) !== $digit1) {
            return false;
        }

        // Validate second check digit
        $sum = 0;

        for ($i = 0; $i < 10; $i++) {
            $sum += intval($cpf[$i]) * (11 - $i);
        }
        $remainder = $sum % 11;
        $digit2 = $remainder < 2 ? 0 : 11 - $remainder;

        return intval($cpf[10]) === $digit2;
    }

    /**
     * Validate CNPJ (Brazilian company taxpayer ID)
     */
    public function validateCNPJ(string $cnpj): bool
    {
        // Remove non-numeric characters
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

        // Check if has 14 digits
        if (strlen($cnpj) !== 14) {
            return false;
        }

        // Check if all digits are the same (invalid CNPJ)
        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        // Validate first check digit
        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;

        for ($i = 0; $i < 12; $i++) {
            $sum += intval($cnpj[$i]) * $weights1[$i];
        }
        $remainder = $sum % 11;
        $digit1 = $remainder < 2 ? 0 : 11 - $remainder;

        if (intval($cnpj[12]) !== $digit1) {
            return false;
        }

        // Validate second check digit
        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;

        for ($i = 0; $i < 13; $i++) {
            $sum += intval($cnpj[$i]) * $weights2[$i];
        }
        $remainder = $sum % 11;
        $digit2 = $remainder < 2 ? 0 : 11 - $remainder;

        return intval($cnpj[13]) === $digit2;
    }

    /**
     * Check if document (CPF or CNPJ) already exists in the database
     */
    public function checkDocumentExists(string $document): bool
    {
        $document = preg_replace('/[^0-9]/', '', $document);

        return Account::where(function ($query) use ($document) {
            $query->where('cpf', $document)
                ->orWhere('cnpj', $document);
        })->exists();
    }

    /**
     * Format CPF for display (XXX.XXX.XXX-XX)
     */
    public function formatCPF(string $cpf): string
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        if (strlen($cpf) !== 11) {
            return $cpf;
        }

        return sprintf(
            '%s.%s.%s-%s',
            substr($cpf, 0, 3),
            substr($cpf, 3, 3),
            substr($cpf, 6, 3),
            substr($cpf, 9, 2)
        );
    }

    /**
     * Format CNPJ for display (XX.XXX.XXX/XXXX-XX)
     */
    public function formatCNPJ(string $cnpj): string
    {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

        if (strlen($cnpj) !== 14) {
            return $cnpj;
        }

        return sprintf(
            '%s.%s.%s/%s-%s',
            substr($cnpj, 0, 2),
            substr($cnpj, 2, 3),
            substr($cnpj, 5, 3),
            substr($cnpj, 8, 4),
            substr($cnpj, 12, 2)
        );
    }

    /**
     * Sanitize document (remove formatting)
     */
    public function sanitizeDocument(string $document): string
    {
        return preg_replace('/[^0-9]/', '', $document);
    }

    /**
     * Validate document type (CPF or CNPJ)
     */
    public function validateDocument(string $document, string $type): bool
    {
        $document = $this->sanitizeDocument($document);

        return match ($type) {
            'cpf', 'PF' => $this->validateCPF($document),
            'cnpj', 'PJ' => $this->validateCNPJ($document),
            default => false,
        };
    }

    /**
     * Detect document type based on length
     */
    public function detectDocumentType(string $document): ?string
    {
        $document = $this->sanitizeDocument($document);
        $length = strlen($document);

        return match ($length) {
            11 => 'cpf',
            14 => 'cnpj',
            default => null,
        };
    }

    /**
     * Get account by document (CPF or CNPJ)
     */
    public function getAccountByDocument(string $document): ?Account
    {
        $document = $this->sanitizeDocument($document);

        return Account::where(function ($query) use ($document) {
            $query->where('cpf', $document)
                ->orWhere('cnpj', $document);
        })->first();
    }

    /**
     * Validate and format document
     */
    public function validateAndFormat(string $document): array
    {
        $sanitized = $this->sanitizeDocument($document);
        $type = $this->detectDocumentType($sanitized);

        if (! $type) {
            return [
                'valid' => false,
                'type' => null,
                'sanitized' => $sanitized,
                'formatted' => $document,
                'message' => 'Invalid document length',
            ];
        }

        $valid = $this->validateDocument($sanitized, $type);

        return [
            'valid' => $valid,
            'type' => $type,
            'sanitized' => $sanitized,
            'formatted' => $type === 'cpf' ? $this->formatCPF($sanitized) : $this->formatCNPJ($sanitized),
            'message' => $valid ? 'Document is valid' : 'Document is invalid',
        ];
    }

    /**
     * Check if document is available for registration
     */
    public function isDocumentAvailable(string $document): array
    {
        $sanitized = $this->sanitizeDocument($document);
        $exists = $this->checkDocumentExists($sanitized);

        return [
            'available' => ! $exists,
            'exists' => $exists,
            'message' => $exists ? 'Document already registered' : 'Document available',
        ];
    }
}
