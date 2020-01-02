<?php

namespace Kieran\UserNotes\Entity;
    
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class UserNote extends Entity
{

	public function canView() {
		return \XF::visitor()->hasPermission('usernotes', 'user_notes_view');
	}

	public function hasMoreComments() {
		return $this->Comments->count() > 3;
	}

	public function getLatestComments() {
		$finder = $this->finder('Kieran\UserNotes:UserNote')
			->where('parent_id', $this->note_id)
			->order('note_id', 'DESC')
			->limit(3);

		if (!\XF::visitor()->hasPermission('usernotes', 'user_notes_hidden')) {
			$finder->where('visible', 1);
		}
			
		return $finder->fetch()
			->reverse();
	}

	public function notifyCommenters($current_user = false) {

		if ($current_user) {
			$current_user = $this->em()->find('XF:User', $current_user);
		}

		$users = [
			$this->creator_id => $this->Creator,
		];

		foreach ($this->Comments as $comment) {
			if (!isset($users[$comment->creator_id])) {
				$users[$comment->creator_id] = $comment->Creator;
			}
		}

		foreach ($users as $user) {

			if (!$user->hasPermission('usernotes', 'user_notes_view')
				|| $current_user->user_id == $user->user_id) {
				continue;
			}

			$alertRepo = $this->repository('XF:UserAlert');
			$alertRepo->alertFromUser(
				$user,
				$current_user ? $current_user : null,
				'usernotes', 
				$this->note_id,
				$current_user->user_id == $this->creator_id ? 'reply' : 'posted', 
				[]
			);
		}

	}

    public static function getStructure(Structure $structure)
	{
        $structure->table = 'xf_kieran_users_notes';
        $structure->shortName = 'Kieran\UserNotes:UserNote';
        $structure->primaryKey = 'note_id';
        $structure->columns = [
			'note_id' => ['type' => self::UINT, 'autoIncrement' => true, 'nullable' => false, 'changeLog' => false],
			'parent_id' => ['type' => self::UINT], 
			'user_id' => ['type' => self::UINT, 'maxLength' => 11],
			'creator_id' => ['type' => self::UINT, 'maxLength' => 11],
			'visible' => ['type' => self::UINT, 'maxLength' => 1, 'default' => 1],
			'note' => ['type' => self::STR, 'default' => ''],
			'data' => ['type' => self::JSON_ARRAY, 'default' => []],
			'timestamp' => ['type' => self::UINT, 'default' => \XF::$time],
        ];
        $structure->getters = [
        	'LatestComments' => true,
		];
		$structure->relations = [
			'User' => [
				'entity' => 'XF:User',
				'type' => self::TO_ONE,
				'conditions' => 'user_id',
				'primary' => true
			],
			'Creator' => [
				'entity' => 'XF:User',
				'type' => self::TO_ONE,
				'conditions' => [
					['user_id', '=', '$creator_id'],
				],
				'primary' => true
			],
			'Comments' => [
				'entity' => 'Kieran\UserNotes:UserNote',
				'type' => self::TO_MANY,
				'conditions' => [
					['parent_id', '=', '$note_id'],
				],
				'order' => ['note_id', 'asc'],
				'primary' => true
			],
			'Parent' => [
				'entity' => 'Kieran\UserNotes:UserNote',
				'type' => self::TO_ONE,
				'conditions' => [
					['note_id', '=', '$parent_id'],
				],
				'primary' => true
			],
		];
        
        return $structure;
    }
}