<?php

namespace Kieran\UserNotes\Events;

use DOMDocument;

class MemberProfile {

	private static $user = null;

	public static function preRenderTemplate(\XF\Template\Templater $templater, &$type, &$template, array &$params) {
		
		$hint = $type . ':' . $template;

		if ($hint == 'public:member_view') {
			self::$user = $params['user'];
		}
	}

	public static function postRenderTemplate(\XF\Template\Templater $templater, $type, $template, &$output) {

		$hint = $type . ':' . $template;
		$visitor = \XF::visitor();
		if ($hint == 'public:member_view') {
            
			$userGroup = \XF::app()->finder('XF:UserGroup')->where('user_group_id', self::$user->display_style_group_id)->fetchOne();
			$visitorGroup = \XF::app()->finder('XF:UserGroup')->where('user_group_id', $visitor->display_style_group_id)->fetchOne();

			if ($visitor->user_id != self::$user->user_id 
				&& $visitor->hasPermission('usernotes', 'user_notes_view')
				&& $visitorGroup->display_style_priority > $userGroup->display_style_priority) {
				$link = '<a href="' .\XF::app()->router()->buildLink('members/user-notes', self::$user) . '"
							class="tabs-tab"
							id="user-notes"
							role="tab">' . \XF::phrase('user_notes') . '</a>';

				$panel = '<li data-href="' .\XF::app()->router()->buildLink('members/user-notes', self::$user) . '" role="tabpanel" aria-labelledby="user-notes">
							<div class="blockMessage">' . \XF::phrase('loading...') . '</div>
                        </li>';
                        
                $output = str_replace('<usernotetab />', $link, $output);
                $output = str_replace('<usernotepanel />', $panel, $output);

			}
		}
	}

	public static function routeMatch(\XF\Mvc\Dispatcher $dispatcher, \XF\Mvc\RouteMatch &$match) {

		if ($match->getController() == 'XF:Member') {
			if ($match->getAction() == 'user-notes') {
				$match->setController('Kieran\UserNotes:Member');
			} else if ($match->getAction() == 'user-notes/reply') {
				$match->setController('Kieran\UserNotes:Member');
			}
		}
	}
    
}