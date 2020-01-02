<?php

namespace Kieran\UserNotes\Pub\Controller;

use XF\Mvc\ParameterBag;

class UserNotes extends \XF\Pub\Controller\AbstractController
{
	public function actionIndex(ParameterBag $params) {
		$note = $this->assertUserNoteExists($params->note_id);
		$user = $this->assertUserExists($note->user_id);
		$visitor = \XF::visitor();

		if (!$visitor->hasPermission('usernotes', 'user_notes_create')
			|| $user->user_id == \XF::visitor()->user_id) {
			return $this->noPermission();
		}

		if ($note->parent_id) {
			return $this->redirect($this->router()->buildLink('members/user-notes#note-' . $note->parent_id, $user));
		} else {
			return $this->redirect($this->router()->buildLink('members/user-notes#note-' . $note->note_id, $user));
		}
	}

	public function actionHide(ParameterBag $params) {
		$note = $this->assertUserNoteExists($params->note_id);
		$user = $this->assertUserExists($note->user_id);
		$visitor = \XF::visitor();

		if (!$visitor->hasPermission('usernotes', 'user_notes_hidden')
			|| $user->user_id == \XF::visitor()->user_id) {
			return $this->noPermission();
		}

		$note->visible = 0;
		$note->save();

		if ($note->parent_id) {
			return $this->redirect($this->router()->buildLink('members/user-notes#note-' . $note->parent_id, $user));
		} else {
			return $this->redirect($this->router()->buildLink('members/user-notes#note-' . $note->note_id, $user));
		}
	}

	public function actionShow(ParameterBag $params) {
		$note = $this->assertUserNoteExists($params->note_id);
		$user = $this->assertUserExists($note->user_id);
		$visitor = \XF::visitor();

		if (!$visitor->hasPermission('usernotes', 'user_notes_hidden')
			|| $user->user_id == \XF::visitor()->user_id) {
			return $this->noPermission();
		}

		$note->visible = 1;
		$note->save();

		if ($note->parent_id) {
			return $this->redirect($this->router()->buildLink('members/user-notes#note-' . $note->parent_id, $user));
		} else {
			return $this->redirect($this->router()->buildLink('members/user-notes#note-' . $note->note_id, $user));
		}
	}

	public function actionAddComment(ParameterBag $params) {
		$this->assertPostOnly();

		$note = $this->assertUserNoteExists($params->note_id);
		$user = $this->assertUserExists($note->user_id);
		$visitor = \XF::visitor();

		if (!$visitor->hasPermission('usernotes', 'user_notes_create')
			|| $user->user_id == \XF::visitor()->user_id
			|| $note->parent_id != 0) {
			return $this->noPermission();
		}
		$this->assertNotFlooding('usernotes');

		$message = $this->filter('message', 'str');

		if (!strlen($message)) {
			throw $this->exception($this->notFound(\XF::phrase('kieran_usernotes_message_required')));
		}

		$comment = $this->getUserNotesRepo()->setupBaseUserNote($user->user_id, $note->note_id);
		$comment->visible = $note->visible;
		$comment->note = $message;
		$comment->save();
		$note->notifyCommenters($visitor->user_id);

		if ($this->filter('_xfWithData', 'bool') && $this->request->exists('last_date')) {

			$comments = $this->finder('Kieran\UserNotes:UserNote')
				->where('parent_id', $note->note_id)
				->where('timestamp', '>', $this->filter('last_date', 'uint'))
				->order('timestamp', 'DESC');

			if (!$visitor->hasPermission('usernotes', 'user_notes_hidden')) {
				$comments->where('visible', 1);
			}

			$comments = $comments->fetch()
				->reverse();

			$viewParams = [
				'comments' => $comments,
				'canViewHidden' => $visitor->hasPermission('usernotes', 'user_notes_hidden'),
			];
			$view = $this->view('Kieran\UserNotes:UserNotes\AddComment', 'kieran_usernotes_user_notes_new_comments', $viewParams);
			$view->setJsonParam('lastDate', $comments->last()->timestamp);
			return $view;
		} else {
			return $this->redirect($this->router()->buildLink('user-notes', $comment));
		}
	}

	public function actionLoadPrevious(ParameterBag $params)
	{
		$note = $this->assertUserNoteExists($params->note_id);
		$user = $this->assertUserExists($note->user_id);
		$visitor = \XF::visitor();

		if (!$visitor->hasPermission('usernotes', 'user_notes_view')
			|| $user->user_id == \XF::visitor()->user_id
			|| $note->parent_id != 0) {
			return $this->noPermission();
		}

		$comments = $this->finder('Kieran\UserNotes:UserNote')
			->where('parent_id', $note->note_id)
			->where('timestamp', '<', $this->filter('before', 'uint'))
			->order('timestamp', 'DESC')
			->limit(20);

		if (!$visitor->hasPermission('usernotes', 'user_notes_hidden')) {
			$comments->where('visible', 1);
		}

		$comments = $comments->fetch()
			->reverse();

		if ($comments->count())
		{
			$firstCommentDate = $comments->first()->timestamp;

			$moreCommentsFinder = $this->finder('Kieran\UserNotes:UserNote')
				->where('parent_id', $note->note_id)
				->where('timestamp', '<', $firstCommentDate);

			if (!$visitor->hasPermission('usernotes', 'user_notes_hidden')) {
				$moreCommentsFinder->where('visible', 1);
			}

			$loadMore = ($moreCommentsFinder->total() > 0);
		}
		else
		{
			$firstCommentDate = 0;
			$loadMore = false;
		}

		$viewParams = [
			'note' => $note,
			'comments' => $comments,
			'firstCommentDate' => $firstCommentDate,
			'loadMore' => $loadMore,
			'canViewHidden' => $visitor->hasPermission('usernotes', 'user_notes_hidden'),
		];
		return $this->view('Kieran\UserNotes:UserNotes\LoadPrevious', 'kieran_usernotes_user_notes_comments', $viewParams);
	}

	protected function assertUserNoteExists($id, $with = null, $phraseKey = null)
	{
		return $this->assertRecordExists('Kieran\UserNotes:UserNote', $id, $with, $phraseKey);
	}

	protected function assertUserExists($id, $with = null, $phraseKey = null)
	{
		return $this->assertRecordExists('XF:User', $id, $with, $phraseKey);
	}

	protected function getUserNotesRepo() {
		return $this->repository('Kieran\UserNotes:UserNotes');
	}

}