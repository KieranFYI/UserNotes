<?php

namespace Kieran\UserNotes\Alert;

use XF\Mvc\Entity\Entity;
use XF\Alert\AbstractHandler;

class UserNote extends AbstractHandler
{

	public function getOptOutActions()
	{
		return [
			'reply',
			'posted'
		];
	}

	public function getOptOutDisplayOrder()
	{
		return 30006;
	}
}