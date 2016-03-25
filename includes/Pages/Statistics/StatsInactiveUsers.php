<?php
namespace Waca\Pages\Statistics;

use Waca\DataObjects\User;
use Waca\Tasks\InternalPageBase;
use Waca\WebRequest;

class StatsInactiveUsers extends InternalPageBase
{
	public function main()
	{
		$this->setHtmlTitle('Inactive Users :: Statistics');

		$showImmune = false;
		if (WebRequest::getBoolean('showimmune')) {
			$showImmune = true;
		}

		$this->assign('showImmune', $showImmune);
		$inactiveUsers = User::getAllInactive($this->getDatabase());
		$this->assign('inactiveUsers', $inactiveUsers);

		$this->setTemplate('statistics/inactive-users.tpl');
		$this->assign('statsPageTitle', 'Inactive tool users');
	}

	public function getSecurityConfiguration()
	{
		return $this->getSecurityManager()->configure()->asInternalPage();
	}
}
