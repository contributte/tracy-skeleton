<?php declare(strict_types = 1);

namespace Tests\Cases\Tracy;

use App\Tracy\AiBlueScreenSetup;
use ReflectionClass;
use RuntimeException;
use Tester\Assert;
use Tester\TestCase;
use Tracy\Debugger;

require_once __DIR__ . '/../../bootstrap.php';

final class AiBlueScreenSetupTest extends TestCase
{

	public function testLocalOnlyModeDisablesGatewayAction(): void
	{
		$this->resetBlueScreen();

		$setup = new AiBlueScreenSetup([
			'enabled' => true,
			'localOnly' => true,
			'gatewayUrl' => 'https://ai-gateway.internal/import',
			'projectRoot' => '/var/www/project',
			'maxFrames' => 4,
			'maxValueLength' => 100,
		]);
		$setup->register();

		$actions = $this->getBlueScreenActions();
		Assert::count(2, $actions);

		$result = $actions[0](new RuntimeException('Minimal message'));
		Assert::contains('data:text/plain', (string) $result['link']);
	}

	public function testGatewayActionEnabledWhenConfigured(): void
	{
		$this->resetBlueScreen();

		$setup = new AiBlueScreenSetup([
			'enabled' => true,
			'localOnly' => false,
			'gatewayUrl' => 'https://ai-gateway.internal/import',
			'gatewaySecret' => 'test-secret',
			'projectRoot' => '/var/www/project',
			'maxFrames' => 4,
			'maxValueLength' => 100,
		]);
		$setup->register();

		$actions = $this->getBlueScreenActions();
		Assert::count(3, $actions);

		$gateway = $actions[2](new RuntimeException('Gateway message'));
		Assert::contains('ai-gateway.internal', (string) $gateway['link']);
		Assert::contains('sig=', (string) $gateway['link']);
	}

	private function resetBlueScreen(): void
	{
		$reflection = new ReflectionClass(Debugger::getBlueScreen());

		$actions = $reflection->getProperty('actions');
		$actions->setValue(Debugger::getBlueScreen(), []);

		$panels = $reflection->getProperty('panels');
		$panels->setValue(Debugger::getBlueScreen(), []);
	}

	/**
	 * @return list<callable>
	 */
	private function getBlueScreenActions(): array
	{
		$reflection = new ReflectionClass(Debugger::getBlueScreen());
		$actions = $reflection->getProperty('actions');

		/** @var list<callable> $value */
		$value = $actions->getValue(Debugger::getBlueScreen());

		return $value;
	}

}

(new AiBlueScreenSetupTest())->run();
