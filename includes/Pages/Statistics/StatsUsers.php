<?php
namespace Waca\Pages\Statistics;

use PDO;
use Waca\DataObjects\User;
use Waca\Exceptions\ApplicationLogicException;
use Waca\Security\SecurityConfiguration;
use Waca\Tasks\InternalPageBase;
use Waca\WebRequest;

class StatsUsers extends InternalPageBase
{
	public function main()
	{
		$this->setHtmlTitle('Users :: Statistics');

		$database = $this->getDatabase();

		$lists = array(
			"Admin"      => User::getAllWithStatus("Admin", $database),
			"User"       => User::getAllWithStatus("User", $database),
			"CheckUsers" => User::getAllCheckusers($database),
		);

		$this->assign("lists", $lists);

		$this->assign('statsPageTitle', 'Account Creation Tool users');
		$this->setTemplate("statistics/users.tpl");
	}

	public function getSecurityConfiguration()
	{
		return $this->getSecurityManager()->configure()->asPublicPage();
	}

	/**
	 * Entry point for the detail action.
	 *
	 * @throws ApplicationLogicException
	 */
	protected function detail()
	{
		$userId = WebRequest::getInt('user');
		if ($userId === null) {
			throw new ApplicationLogicException("User not found");
		}

		$database = $this->getDatabase();

		$user = User::getById($userId, $database);
		if ($user == false) {
			throw new ApplicationLogicException('User not found');
		}

		$safeUsername = htmlentities($user->getUsername(), ENT_COMPAT, 'UTF-8');
		$this->setHtmlTitle($safeUsername . ' :: Users :: Statistics');

		$activitySummary = $database->prepare(<<<SQL
SELECT COALESCE(closes.mail_desc, log.action) AS action, COUNT(*) AS count
FROM log
INNER JOIN user ON log.user = user.id
LEFT JOIN closes ON log.action = closes.closes
WHERE user.username = :username
GROUP BY action;
SQL
		);
		$activitySummary->execute(array(":username" => $user->getUsername()));
		$activitySummaryData = $activitySummary->fetchAll(PDO::FETCH_ASSOC);

		$this->assign("user", $user);
		$this->assign("activity", $activitySummaryData);

		$usersCreatedQuery = $database->prepare(<<<SQL
SELECT log.timestamp time, request.name name, request.id id
FROM log
INNER JOIN request ON (request.id = log.objectid AND log.objecttype = 'Request')
INNER JOIN user ON log.user = user.id
LEFT JOIN emailtemplate ON concat('Closed ', emailtemplate.id) = log.action
WHERE user.username = :username
    AND log.action LIKE 'Closed %'
    AND (emailtemplate.oncreated = '1' OR log.action = 'Closed custom-y')
ORDER BY log.timestamp;
SQL
		);
		$usersCreatedQuery->execute(array(":username" => $user->getUsername()));
		$usersCreated = $usersCreatedQuery->fetchAll(PDO::FETCH_ASSOC);
		$this->assign("created", $usersCreated);

		$usersNotCreatedQuery = $database->prepare(<<<SQL
SELECT log.timestamp time, request.name name, request.id id
FROM log
JOIN request ON request.id = log.objectid AND log.objecttype = 'Request'
JOIN user ON log.user = user.id
LEFT JOIN emailtemplate ON concat('Closed ', emailtemplate.id) = log.action
WHERE user.username = :username
    AND log.action LIKE 'Closed %'
    AND (emailtemplate.oncreated = '0' OR log.action = 'Closed custom-n' OR log.action = 'Closed 0')
ORDER BY log.timestamp;
SQL
		);
		$usersNotCreatedQuery->execute(array(":username" => $user->getUsername()));
		$usersNotCreated = $usersNotCreatedQuery->fetchAll(PDO::FETCH_ASSOC);
		$this->assign("notcreated", $usersNotCreated);

		$accountLogQuery = $database->prepare(<<<SQL
SELECT
	user.username AS log_user,
    log.action AS log_action,
    log.timestamp AS log_time,
    log.comment AS log_cmt
FROM log
LEFT JOIN user ON user.id = log.user
WHERE log.objectid = :userid
AND log.objecttype = 'User'
AND log.action IN ('Approved','Suspended','Declined','Promoted','Demoted','Renamed','Prefchange','Registered');
SQL
		);
		$accountLogQuery->execute(array(":userid" => $user->getId()));
		$accountLog = $accountLogQuery->fetchAll(PDO::FETCH_ASSOC);
		$this->assign("accountlog", $accountLog);

		$this->assign('statsPageTitle', 'Account Creation Tool users');
		$this->setTemplate("statistics/userdetail.tpl");
	}
}
