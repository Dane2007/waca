<?php
namespace Waca\DataObjects;

use Exception;
use PDO;
use Waca\DataObject;
use Waca\Exceptions\OptimisticLockFailedException;
use Waca\PdoDatabase;

/**
 * Welcome template data object
 */
class WelcomeTemplate extends DataObject
{
	/** @var string */
	private $usercode;
	/** @var string */
	private $botcode;
	private $usageCache;

	/**
	 * Summary of getAll
	 *
	 * @param PdoDatabase $database
	 *
	 * @return WelcomeTemplate[]
	 */
	public static function getAll(PdoDatabase $database)
	{
		$statement = $database->prepare("SELECT * FROM welcometemplate;");

		$statement->execute();

		$result = array();
		/** @var WelcomeTemplate $v */
		foreach ($statement->fetchAll(PDO::FETCH_CLASS, self::class) as $v) {
			$v->setDatabase($database);
			$result[] = $v;
		}

		return $result;
	}

	/**
	 * @throws Exception
	 */
	public function save()
	{
		if ($this->isNew()) {
			// insert
			$statement = $this->dbObject->prepare(<<<SQL
INSERT INTO welcometemplate (usercode, botcode) VALUES (:usercode, :botcode);
SQL
			);
			$statement->bindValue(":usercode", $this->usercode);
			$statement->bindValue(":botcode", $this->botcode);

			if ($statement->execute()) {
				$this->id = $this->dbObject->lastInsertId();
			}
			else {
				throw new Exception($statement->errorInfo());
			}
		}
		else {
			// update
			$statement = $this->dbObject->prepare(<<<SQL
UPDATE `welcometemplate`
SET usercode = :usercode, botcode = :botcode, updateversion = updateversion + 1
WHERE id = :id AND updateversion = :updateversion
LIMIT 1;
SQL
			);

			$statement->bindValue(':id', $this->id);
			$statement->bindValue(':updateversion', $this->updateversion);

			$statement->bindValue(':usercode', $this->usercode);
			$statement->bindValue(':botcode', $this->botcode);

			if (!$statement->execute()) {
				throw new Exception($statement->errorInfo());
			}

			if ($statement->rowCount() !== 1) {
				throw new OptimisticLockFailedException();
			}

			$this->updateversion++;
		}
	}

	/**
	 * @return string
	 */
	public function getUserCode()
	{
		return $this->usercode;
	}

	/**
	 * @param string $usercode
	 */
	public function setUserCode($usercode)
	{
		$this->usercode = $usercode;
	}

	/**
	 * @return string
	 */
	public function getBotCode()
	{
		return $this->botcode;
	}

	/**
	 * @param string $botcode
	 */
	public function setBotCode($botcode)
	{
		$this->botcode = $botcode;
	}

	/**
	 * @return User[]
	 */
	public function getUsersUsingTemplate()
	{
		if ($this->usageCache === null) {
			$statement = $this->dbObject->prepare("SELECT * FROM user WHERE welcome_template = :id;");

			$statement->execute(array(":id" => $this->id));

			$result = array();
			/** @var WelcomeTemplate $v */
			foreach ($statement->fetchAll(PDO::FETCH_CLASS, User::class) as $v) {
				$v->setDatabase($this->dbObject);
				$result[] = $v;
			}

			$this->usageCache = $result;
		}

		return $this->usageCache;
	}
}
