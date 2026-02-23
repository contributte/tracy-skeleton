<?php declare(strict_types = 1);

namespace App\UI\Home;

use App\UI\BasePresenter;
use RuntimeException;

class HomePresenter extends BasePresenter
{

	public function actionCrash(string $mode = 'demo'): void
	{
		if ($mode === 'service') {
			throw new RuntimeException('Service token=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.payload.signature failed for alice@example.com');
		}

		throw new RuntimeException('Demo crash with Authorization: Bearer SECRET-1234567890123456789012345678901234567890 and DSN mysql://root:password@localhost/db');
	}

}
