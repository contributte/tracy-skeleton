<?php declare(strict_types = 1);

namespace App\Tracy;

final class Redactor
{

	private const SENSITIVE_KEYS = [
		'password',
		'passwd',
		'pass',
		'pwd',
		'secret',
		'token',
		'api_key',
		'apikey',
		'authorization',
		'cookie',
		'session',
		'dsn',
		'private_key',
		'jwt',
	];

	public function __construct(
		private readonly string $projectRoot,
		private readonly int $maxValueLength,
	)
	{
	}

	public function redactString(string $value): string
	{
		$value = $this->normalizePath($value);
		$value = $this->redactPatterns($value);

		if (strlen($value) <= $this->maxValueLength) {
			return $value;
		}

		return substr($value, 0, $this->maxValueLength) . '... [truncated]';
	}

	public function redactByKey(string|int $key, mixed $value): mixed
	{
		$keyString = strtolower((string) $key);

		foreach (self::SENSITIVE_KEYS as $sensitiveKey) {
			if (str_contains($keyString, $sensitiveKey)) {
				return '[REDACTED]';
			}
		}

		if (is_string($value)) {
			return $this->redactString($value);
		}

		if (is_array($value)) {
			$sanitized = [];

			foreach ($value as $innerKey => $innerValue) {
				$sanitized[$innerKey] = $this->redactByKey($innerKey, $innerValue);
			}

			return $sanitized;
		}

		return $value;
	}

	private function redactPatterns(string $value): string
	{
		$patterns = [
			'/(Authorization\s*:\s*Bearer\s+)[^\s]+/i' => '$1[REDACTED]',
			'/\bBearer\s+[A-Za-z0-9\-._~+\/=]*/i' => 'Bearer [REDACTED]',
			'/\beyJ[A-Za-z0-9\-_=]+\.[A-Za-z0-9\-_=]+\.[A-Za-z0-9\-_=]+\b/' => '[JWT_REDACTED]',
			'/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/i' => '[EMAIL_REDACTED]',
			'/\b(?:\d{1,3}\.){3}\d{1,3}\b/' => '[IP_REDACTED]',
			'/https?:\/\/[^\s:@]+:[^\s@]+@/' => 'https://[CREDENTIALS_REDACTED]@',
			'/\b[A-Za-z0-9_\-]{40,}\b/' => '[TOKEN_REDACTED]',
		];

		foreach ($patterns as $pattern => $replacement) {
			$value = (string) preg_replace($pattern, $replacement, $value);
		}

		return $value;
	}

	private function normalizePath(string $value): string
	{
		$normalizedRoot = str_replace('\\', '/', $this->projectRoot);
		$normalizedValue = str_replace('\\', '/', $value);

		$normalizedValue = str_replace($normalizedRoot, '<project-root>', $normalizedValue);

		$homeDir = getenv('HOME');
		if (is_string($homeDir) && $homeDir !== '') {
			$normalizedValue = str_replace(str_replace('\\', '/', $homeDir), '<home>', $normalizedValue);
		}

		return $normalizedValue;
	}

}
