<?php

declare(strict_types=1);

/**
 * Common contract for validators that return validation errors.
 */
interface ValidatorInterface
{
    /**
     * @param array<string, string> $data
     * @return string[]
     */
    public function validate(array $data): array;
}
