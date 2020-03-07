<?php

namespace Kieran\UserNotes\Pub\Controller;

use XF\Mvc\ParameterBag;

class Member extends \XF\Pub\Controller\AbstractController
{

	public function actionUserNotes(ParameterBag $params) {
		$user = $this->assertUserExists($params->user_id);
		$visitor = \XF::visitor();

		$userGroup = $this->finder('XF:UserGroup')->where('user_group_id', $user->display_style_group_id)->fetchOne();
		$visitorGroup = $this->finder('XF:UserGroup')->where('user_group_id', $visitor->display_style_group_id)->fetchOne();

		if (!$visitor->hasPermission('usernotes', 'user_notes_view') 
			|| $user->user_id == \XF::visitor()->user_id
			|| $visitorGroup->display_style_priority <= $userGroup->display_style_priority) {
			return $this->noPermission();
		}

		$viewParams = [
			'user' => $user,
			'notes' => $this->getUserNotesRepo()->getNotes($user->user_id, $visitor->hasPermission('usernotes', 'user_notes_hidden')),
			'canCreate' => $visitor->hasPermission('usernotes', 'user_notes_create'),
			'canViewHidden' => $visitor->hasPermission('usernotes', 'user_notes_hidden'),

		];
		return $this->view('Kieran\UserNotes:Rank', 'kieran_usernotes_user_notes', $viewParams);
	}

	public function actionUserNotesReply(ParameterBag $params)
	{
		$user = $this->assertUserExists($params->user_id);
		$visitor = \XF::visitor();

		if (!$visitor->hasPermission('usernotes', 'user_notes_create') || $user->user_id == \XF::visitor()->user_id) {
			return $this->noPermission();
		}
		$this->assertNotFlooding('usernotes');

		$message = $this->plugin('XF:Editor')->fromInput('message');
		if (strlen($message)) {
			$hidden = $this->filter('hidden', 'bool');
			if (!$visitor->hasPermission('usernotes', 'user_notes_hidden')) {
				$hidden = false;
			}

			$note = $this->getUserNotesRepo()->setupBaseUserNote($user->user_id);
			$note->note = $message;
			$note->visible = $hidden ? 0 : 1;
			$note->save();
		} else {
			throw $this->exception($this->notFound(\XF::phrase('kieran_usernotes_message_required')));
		}

		if ($this->filter('_xfWithData', 'bool') && $this->request->exists('last_date')) {
			
			$viewParams = [
				'notes' => $this->getUserNotesRepo()->getNotes($user->user_id, $visitor->hasPermission('usernotes', 'user_notes_hidden'), $this->filter('last_date', 'uint')),
				'canCreate' => $visitor->hasPermission('usernotes', 'user_notes_create'),
				'canViewHidden' => $visitor->hasPermission('usernotes', 'user_notes_hidden'),
			];
			$view = $this->view('Kieran\UserNotes:UserNotes\AddComment', 'kieran_usernotes_new_user_notes', $viewParams);
			$view->setJsonParam('lastDate', $viewParams['notes']->last()->timestamp);
			return $view;
		} else {
			return $this->redirect($this->router()->buildLink('members/user-notes', $user));
		}
	}

	protected function assertUserExists($id, $with = null, $phraseKey = null)
	{
		return $this->assertRecordExists('XF:User', $id, $with, $phraseKey);
	}
	
	protected function getUserNotesRepo() {
		return $this->repository('Kieran\UserNotes:UserNotes');
	}

}