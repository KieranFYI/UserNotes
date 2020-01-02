<?php

namespace Kieran\UserNotes;

use XF\Db\Schema\Create;

class Setup extends \XF\AddOn\AbstractSetup
{
	use \XF\AddOn\StepRunnerInstallTrait;
	use \XF\AddOn\StepRunnerUninstallTrait;

	// php cmd.php xf-addon:install Kieran/UserNotes
	// php cmd.php xf-addon:build-release Kieran/UserNotes

	public function installStep1(array $stepParams = [])
	{
		$this->schemaManager()->createTable('xf_kieran_users_notes', function(Create $table)
		{
			$table->addColumn('note_id', 'int')->autoIncrement();
			$table->addColumn('parent_id', 'int')->setDefault(0);
			$table->addColumn('user_id', 'int', 11);
			$table->addColumn('creator_id', 'int', 11);
			$table->addColumn('visible', 'int', 1)->setDefault(1);
			$table->addColumn('note', 'longtext');
			$table->addColumn('data', 'text');
			$table->addColumn('timestamp', 'int', 11);
			$table->addPrimaryKey('note_id');
			$table->addUniqueKey(['note_id', 'user_id', 'creator_id'], 'note_id_user_id_creator_id');
		});
	}
	
	public function upgrade(array $stepParams = [])
	{
	}
	
	public function uninstallStep1(array $stepParams = [])
	{
		$this->schemaManager()->dropTable('xf_kieran_users_notes');
	}

}