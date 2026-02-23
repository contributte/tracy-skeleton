<?php declare(strict_types = 1);

namespace App\Tracy;

use Throwable;
use Tracy\Debugger;

final class AiBlueScreenSetup
{

	/**
	 * @param array<string, mixed> $config
	 */
	public function __construct(private readonly array $config)
	{
	}

	public function register(): void
	{
		if (!$this->readBoolConfig('enabled', true)) {
			return;
		}

		$blueScreen = Debugger::getBlueScreen();
		$blueScreen->addAction(fn (Throwable $exception): array => $this->createDataAction($exception));
	}

	/**
	 * @return array{link: string, label: string}
	 */
	private function createDataAction(Throwable $exception): array
	{
		$report = $this->formatMinimalReport($exception);

		return [
			'link' => 'data:text/plain;charset=utf-8;base64,' . rawurlencode(base64_encode($report)),
			'label' => 'open AI minimal report',
		];
	}

	private function formatMinimalReport(Throwable $exception): string
	{
		$maxFrames = $this->readIntConfig('maxFrames', 8);
		$stackLines = [];

		foreach (array_slice($exception->getTrace(), 0, $maxFrames) as $index => $frame) {
			$file = isset($frame['file'])
				? $frame['file']
				: '[internal]';
			$line = $frame['line'] ?? 0;
			$function = $frame['function'];
			$class = $frame['class'] ?? '';
			$type = $frame['type'] ?? '';

			$stackLines[] = sprintf('#%d %s:%d %s%s%s()', $index, $file, $line, $class, $type, $function);
		}

		$message = $exception->getMessage();
		$file = $exception->getFile();

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

}
