<?php declare(strict_types = 1);

namespace App\Tracy;

use Throwable;
use Tracy\Debugger;

final class AiBlueScreenSetup
{

	private Redactor $redactor;

	/**
	 * @param array<string, mixed> $config
	 */
	public function __construct(private readonly array $config)
	{
		$projectRoot = $this->readStringConfig('projectRoot', dirname(__DIR__, 2));
		$maxValueLength = $this->readIntConfig('maxValueLength', 180);

		$this->redactor = new Redactor($projectRoot, $maxValueLength);
	}

	public function register(): void
	{
		if (!$this->readBoolConfig('enabled', true)) {
			return;
		}

		$blueScreen = Debugger::getBlueScreen();
		$blueScreen->addAction(fn (Throwable $exception): array => $this->createDataAction($exception, 'minimal'));
		$blueScreen->addAction(fn (Throwable $exception): array => $this->createDataAction($exception, 'sanitized'));
	}

	/**
	 * @return array{link: string, label: string}
	 */
	private function createDataAction(Throwable $exception, string $mode): array
	{
		$report = $mode === 'minimal'
			? $this->formatMinimalReport($exception)
			: $this->formatSanitizedReport($exception);

		$label = $mode === 'minimal'
			? 'open AI minimal report'
			: 'open AI sanitized report';

		return [
			'link' => 'data:text/plain;charset=utf-8;base64,' . rawurlencode(base64_encode($report)),
			'label' => $label,
		];
	}

	private function formatMinimalReport(Throwable $exception): string
	{
		$maxFrames = $this->readIntConfig('maxFrames', 8);
		$stackLines = [];

		foreach (array_slice($exception->getTrace(), 0, $maxFrames) as $index => $frame) {
			$file = isset($frame['file'])
				? $this->redactor->redactString($frame['file'])
				: '[internal]';
			$line = $frame['line'] ?? 0;
			$function = $frame['function'];
			$class = $frame['class'] ?? '';
			$type = $frame['type'] ?? '';

			$stackLines[] = sprintf('#%d %s:%d %s%s%s()', $index, $file, $line, $class, $type, $function);
		}

		$message = $this->redactor->redactString($exception->getMessage());
		$file = $this->redactor->redactString($exception->getFile());

		return implode("\n", [
			'=== Tracy BlueScreen: Minimal AI Report ===',
			'Policy: local copy, no headers/cookies/env/body',
			sprintf('Type: %s', get_debug_type($exception)),
			sprintf('Message: %s', $message),
			sprintf('Code: %s', (string) $exception->getCode()),
			sprintf('Location: %s:%d', $file, $exception->getLine()),
			'--- Top stack frames ---',
			...$stackLines,
		]);
	}

	private function formatSanitizedReport(Throwable $exception): string
	{
		$minimal = $this->formatMinimalReport($exception);

		$requestContext = [
			'method' => $this->readServer('REQUEST_METHOD'),
			'uri' => $this->readServer('REQUEST_URI'),
			'host' => $this->readServer('HTTP_HOST'),
			'user_agent' => $this->readServer('HTTP_USER_AGENT'),
			'remote_addr' => $this->readServer('REMOTE_ADDR'),
			'app_env' => $this->readServer('NETTE_ENV'),
		];

		$serverSubset = [
			'APP_DEBUG' => $this->readServer('APP_DEBUG'),
			'NETTE_DEBUG' => $this->readServer('NETTE_DEBUG'),
			'PHP_SELF' => $this->readServer('PHP_SELF'),
		];

		$requestContext = $this->redactor->redactByKey('request', $requestContext);
		$serverSubset = $this->redactor->redactByKey('server', $serverSubset);

		$requestJson = json_encode($requestContext, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		$serverJson = json_encode($serverSubset, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

		return implode("\n", [
			'=== Tracy BlueScreen: Sanitized AI Report ===',
			'Policy: redacted fields, normalized paths, truncated long values',
			$minimal,
			'--- Request summary ---',
			is_string($requestJson) ? $requestJson : '{}',
			'--- Server subset ---',
			is_string($serverJson) ? $serverJson : '{}',
		]);
	}

	private function readServer(string $key): ?string
	{
		$value = filter_input(INPUT_SERVER, $key, FILTER_UNSAFE_RAW);

		return is_string($value) ? $value : null;
	}

	private function readBoolConfig(string $key, bool $default): bool
	{
		$value = $this->config[$key] ?? $default;

		return is_bool($value) ? $value : $default;
	}

	private function readIntConfig(string $key, int $default): int
	{
		$value = $this->config[$key] ?? $default;

		return is_int($value) ? $value : $default;
	}

	private function readStringConfig(string $key, string $default): string
	{
		$value = $this->config[$key] ?? $default;

		return is_string($value) ? $value : $default;
	}

}
