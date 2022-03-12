<?php

/**
 * Class-StickyTopicsOrder.php
 *
 * @package Sticky Topics Order
 * @link https://dragomano.ru/reviews/set-order-for-sticky-topics
 * @author maestrosite.ru <smf@maestrosite.ru>, Bugo <bugo@dragomano.ru>
 * @copyright 2012 maestrosite.ru, 2022 Bugo
 * @license https://creativecommons.org/licenses/by-sa/3.0 CC BY-SA 3.0
 *
 * @version 0.6
 */

if (!defined('SMF'))
	die('No direct access...');

final class StickyTopicsOrder
{
	public function hooks()
	{
		add_integration_function('integrate_admin_areas', __CLASS__ . '::adminAreas#', false, __FILE__);
		add_integration_function('integrate_modify_modifications', __CLASS__ . '::modifyModifications#', false, __FILE__);
	}

	/**
	 * @hook integrate_admin_areas
	 */
	public function adminAreas(array &$admin_areas)
	{
		global $txt;

		loadLanguage('StickyTopicsOrder');

		$admin_areas['config']['areas']['modsettings']['subsections']['sticky_topics_order'] = [$txt['sticky_topics_order_title']];
	}

	/**
	 * @hook integrate_modify_modifications
	 */
	public function modifyModifications(array &$subActions)
	{
		$subActions['sticky_topics_order'] = [$this, 'settings'];
	}

	public function settings()
	{
		global $board, $sourcedir, $context;

		loadLanguage('StickyTopicsOrder');

		if (isset($_POST['back']))
			redirectexit('action=admin;area=modsettings;sa=sticky_topics_order');

		if ($board and isset($_POST['save']) and !empty($_POST['tid']) and is_array($_POST['tid'])) {
			global $smcFunc;

			foreach ($_POST['tid'] as $t => $s) {
				if ((int) $s) {
					$smcFunc['db_query']('', '
						UPDATE {db_prefix}topics
						SET is_sticky = {int:is_sticky}
						WHERE id_topic = {int:id_topic}
							AND id_board = {int:id_board}',
						array(
							'is_sticky' => (int) $s,
							'id_topic' => (int) $t,
							'id_board' => (int) $board,
						)
					);
				}
			}
		}

		require_once $sourcedir . '/Subs-List.php';

		$list = empty($board) ? $this->boards() : $this->topics();

		createList($list);

		$context['sub_template'] = 'show_list';
		$context['default_list'] = 'sticky';
	}

	public function boards(): array
	{
		global $context, $scripturl, $txt;

		$format = '?action=admin;area=' . $context['admin_area'] . ';sa=' . $context[$context['admin_menu_name']]['current_subsection'];

		return array(
			'id' => 'sticky',
			'get_items' => array(
				'function' => __CLASS__ . '::boards_list#'
			),
			'columns' => array(
				'boards' => array(
					'header' => array(
						'value' => $txt['board']
					),
					'data' => array(
						'sprintf' => array(
							'format' => '<a href="' . $scripturl . '?board=%1$d.0">%2$s</a> - %3$s',
							'params' => array(
								'id_board' => false,
								'name' => true,
								'description' => true
							)
						)
					)
				),
				'topics' => array(
					'header' => array(
						'value' => $txt['topics']
					),
					'data' => array(
						'sprintf' => array(
							'format' => '%2$d [<a href="' . $scripturl . $format . ';board=%1$d">' . $txt['sticky_topics_order_change'] . '</a>]',
							'params' => array(
								'id_board' => false,
								'ccc' => false
							)
						),
						'class' => 'centertext'
					)
				)
			)
		);
	}

	public function boards_list()
	{
		global $smcFunc;

		$request = $smcFunc['db_query']('', '
			SELECT t.id_board, COUNT(t.id_topic) AS ccc, b.name, b.description
			FROM {db_prefix}topics t
				INNER JOIN {db_prefix}boards b ON t.id_board = b.id_board
			WHERE t.is_sticky != 0
			GROUP BY t.id_board, b.name, b.description
			HAVING COUNT(t.id_topic) > 1',
			array()
		);

		$boards = [];
		while($row = $smcFunc['db_fetch_assoc']($request))
			$boards[$row['id_board']] = $row;

		$smcFunc['db_free_result']($request);

		return $boards;
	}

	public function topics()
	{
		global $context, $txt, $scripturl, $board_info;

		$format = '?action=admin;area=' . $context['admin_area'] . ';sa=' . $context[$context['admin_menu_name']]['current_subsection'];

		return array(
			'id' => 'sticky',
			'title' => $txt['board'] . ': <a href="' . $scripturl . '?board=' . $board_info['id'] . '">' . $board_info['name'] . '</a>',
			'get_items' => array(
				'function' => __CLASS__ . '::topics_list#'
			),
			'form' => array(
				'href' => $scripturl . $format . ';board=' . $board_info['id'],
			),
			'columns' => array(
				'topics' => array(
					'header' => array(
						'value' => $txt['topic']
					),
					'data' => array(
						'sprintf' => array(
							'format' => '<a href="' . $scripturl . '?topic=%1$d.0">%2$s</a>',
							'params' => array(
								'id_topic' => false,
								'subject' => true
							)
						)
					)
				),
				'order' => array(
					'header' => array(
						'value' => $txt['sticky_topics_order_order']
					),
					'data' => array(
						'db' => 'is_sticky',
						'class' => 'centertext'
					)
				),
				'oreder_new' => array(
					'header' => array(
						'value' => $txt['sticky_topics_order_order_new']
					),
					'data' => array(
						'sprintf' => array(
							'format' => '<input name="tid[%1$d]" value="%2$d">',
							'params' => array(
								'id_topic' => false,
								'is_sticky' => false
							)
						),
						'class' => 'centertext'
					)
				)
			),
			'additional_rows' => array(
				array(
					'position' => 'bottom_of_list',
					'value' => '<input type="submit" class="button" name="save" value="' . $txt['save'] . '"><input type="submit" class="button" name="back" value="' . $txt['back'] . '">'
				)
			)
		);
	}

	public function topics_list()
	{
		global $smcFunc, $board;

		$request = $smcFunc['db_query']('', '
			SELECT t.id_topic, t.is_sticky, m.subject
			FROM {db_prefix}topics t
				INNER JOIN {db_prefix}messages m ON t.id_first_msg = m.id_msg
			WHERE t.is_sticky != 0 AND t.id_board = {int:board}
			ORDER BY t.is_sticky DESC',
			array(
				'board' => (int) $board,
			)
		);

		$topics = [];
		while($row = $smcFunc['db_fetch_assoc']($request))
			$topics[$row['id_topic']] = $row;

		$smcFunc['db_free_result']($request);

		return $topics;
	}
}
