<?php declare(strict_types = 1);

namespace Tests\Cases\Tracy;

use App\Tracy\Redactor;
use Tester\Assert;
use Tester\TestCase;

require_once __DIR__ . '/../../bootstrap.php';

final class RedactorTest extends TestCase
{

	public function testKeyRedaction(): void
	{
		$redactor = new Redactor('/var/www/project', 60);

		$input = [
			'password' => 'my-secret',
			'nested' => [
				'authorization' => 'Bearer super-secret-token',
				'path' => '/var/www/project/app/Presenter.php',
			],
		];

		$output = $redactor->redactByKey('root', $input);

		Assert::same('[REDACTED]', $output['password']);
		Assert::same('[REDACTED]', $output['nested']['authorization']);
		Assert::contains('<project-root>/app/Presenter.php', $output['nested']['path']);
	}

	public function testPatternRedactionAndTruncation(): void
	{
		$redactor = new Redactor('/tmp/project', 35);

		$text = 'Contact alice@example.com with token ABCDEFGHIJKLMNOPQRSTUVWXYZ12345678901234567890';
		$out = $redactor->redactString($text);

		Assert::contains('[EMAIL_REDACTED]', $out);
		Assert::contains('... [truncated]', $out);
	}

}

(new RedactorTest())->run();
