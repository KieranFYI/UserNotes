<?php

namespace Kieran\UserNotes\Repository;

use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;

class UserNotes extends Repository
{

	public function getNotes($user_id, $canViewHidden = false, $timestamp = 0) {
		$finder = $this->finder('Kieran\UserNotes:UserNote')
			->where('user_id', $user_id)
			->where('parent_id', 0);
		if (!$canViewHidden) {
			$finder->where('visible', 1);
		}

		if ($timestamp != 0) {
			return $finder->where('timestamp', '>', $timestamp)
				->order('timestamp', 'ASC')
				->fetch()
				->reverse();
		}
		
		$finder->order('note_id', 'desc');
		return $finder->fetch();
	}

	public function setupBaseUserNote($user_id, $parent = 0)
	{
		$note = $this->em->create('Kieran\UserNotes:UserNote');
		$note->user_id = $user_id;
		$note->creator_id = \XF::visitor()->user_id;
		$note->parent_id = $parent;
		return $note;
	}
}