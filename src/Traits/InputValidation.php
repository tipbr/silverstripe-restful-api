<?php

namespace TipBr\RestfulApi\Traits;

use SilverStripe\Control\HTTPResponse_Exception;

trait InputValidation
{
    /**
     * Returns a sanitized variable from the POST or GET vars
     *
     * @param string $name Variable name
     * @param int|null $filter PHP filter constant (default: FILTER_SANITIZE_SPECIAL_CHARS)
     * @return mixed
     */
    protected function getVar(string $name, ?int $filter = FILTER_SANITIZE_SPECIAL_CHARS): mixed
    {
        $key = strtolower($name);
        $value = $this->vars[$key] ?? null;

        if ($value !== null && $filter !== null && is_string($value)) {
            return filter_var($value, $filter);
        }

        return $value;
    }

    /**
     * Checks if a variable exists in the POST or GET vars
     *
     * @param string $name
     * @return bool
     */
    protected function hasVar(string $name): bool
    {
        $key = strtolower($name);
        return isset($this->vars[$key]);
    }

    /**
     * Returns an array of all the variables listed from the POST or GET vars
     *
     * @param array $vars
     * @return array
     * @throws HTTPResponse_Exception
     */
    protected function ensureVars(?array $vars = []): array
    {
        $output = [];

        foreach ($vars as $k => $v) {
            if ($v && is_callable($v)) {
                if (!$this->hasVar($k) || !$v($this->getVar($k, null))) {
                    throw $this->httpError(400, 'Missing or invalid required variable: ' . $k);
                }

                $output[] = $this->getVar($k);
            } elseif (!$this->hasVar($v)) {
                throw $this->httpError(400, 'Missing required variable: ' . $v);
            } else {
                $output[] = $this->getVar($v);
            }
        }

        return $output;
    }

    /**
     * Validate email address
     *
     * @param string $email
     * @return string|false
     */
    protected function sanitizeEmail(string $email)
    {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }

    /**
     * Validate and sanitize URL
     *
     * @param string $url
     * @return string|false
     */
    protected function sanitizeUrl(string $url)
    {
        return filter_var($url, FILTER_SANITIZE_URL);
    }

    /**
     * Sanitize string input
     *
     * @param string $string
     * @return string
     */
    protected function sanitizeString(string $string): string
    {
        return filter_var($string, FILTER_SANITIZE_SPECIAL_CHARS);
    }

    /**
     * Validate integer input
     *
     * @param mixed $value
     * @return int|false
     */
    protected function validateInt($value)
    {
        return filter_var($value, FILTER_VALIDATE_INT);
    }
}
