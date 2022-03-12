<?php

//Add areas to admin menu
function hook_StickyTopicsOrder_admin( &$areas )
{
	$areas['config']['areas']['modsettings']['subsections']['StickyTopicsOrder'] = array('StickyTopicsOrder');
}

//Set subaction handler.
function hook_StickyTopicsOrder_modify( &$subActions )
{
	$subActions['StickyTopicsOrder'] = 'hook_StickyTopicsOrder_modify_sa';
}

//Call subaction handler
function hook_StickyTopicsOrder_modify_sa()
{
	//Set current modsettings area tab:
	global $context;
	$context[$context['admin_menu_name']]['current_subsection'] = 'StickyTopicsOrder';
	$context['sub_template'] = 'hook_StickyTopicsOrder_modify_sa';

	loadLanguage('hook_StickyTopicsOrder');

	global $sourcedir, $board;
	if( $board and isset($_POST['save']) and !empty($_POST['tid']) and is_array($_POST['tid']) )
	{
		global $smcFunc;
		foreach( $_POST['tid'] as $t => $s )
			if( (int) $s )
				$smcFunc['db_query']('', 'UPDATE {db_prefix}topics
					SET is_sticky = {int:is_sticky}
					WHERE id_topic = {int:id_topic} AND id_board = {int:id_board}',
					array(
						'is_sticky' => (int) $s,
						'id_topic' => (int) $t,
						'id_board' => (int) $board,
					)
				 );
	}

	require_once $sourcedir . '/Subs-List.php';
	$list = empty($board) ? hook_StickyTopicsOrder_boards() : hook_StickyTopicsOrder_topics();
}
function hook_StickyTopicsOrder_boards()
{
	global $scripturl, $txt, $context;
	$format = '?action=admin;area=' . $context['admin_area'] . ';sa=' . $context[$context['admin_menu_name']]['current_subsection'];
	$list = array(
		'id' => 'sticky',
		'get_items' => array('function' => 'hook_StickyTopicsOrder_boards_list'),
		'columns' => array(
			'boards' => array(
				'header' => array('value' => $txt['board']),
				'data' => array(
					'sprintf' => array(
						'format' => '<a href="' . $scripturl . '?board=%1$d.0">%2$s</a> - %3$s',
						'params' => array(
							'id_board' => false,
							'name' => true,
							'description' => true,
						),
					),
				),
			),
			'topics' => array(
				'header' => array('value' => $txt['topics']),
				'data' => array(
					'sprintf' => array(
						'format' => '%2$d [<a href="' . $scripturl . $format . ';board=%1$d">' . $txt['StickyTopicsOrder_change'] . '</a>]',
						'params' => array(
							'id_board' => false,
							'ccc' => false,
						),
					),
				),
			),
		),
	);
	createList( $list );
}
function hook_StickyTopicsOrder_boards_list()
{
	global $smcFunc;
	$request = $smcFunc['db_query']('', 'SELECT t.id_board, COUNT(*) AS ccc, b.name, b.description
		FROM {db_prefix}topics t
		INNER JOIN {db_prefix}boards b ON t.id_board = b.id_board
		WHERE is_sticky != 0
		GROUP BY t.id_board
		HAVING COUNT(*) > 1');

	$r = array();
	while( $row = $smcFunc['db_fetch_assoc']($request) )
		$r[$row['id_board']] = $row;
	$smcFunc['db_free_result']($request);

	return $r;
}
function hook_StickyTopicsOrder_topics()
{
	global $scripturl, $txt, $context, $board_info;

	$format = '?action=admin;area=' . $context['admin_area'] . ';sa=' . $context[$context['admin_menu_name']]['current_subsection'];
	$list = array(
		'id' => 'sticky',
		'title' => $txt['board'] . ': <a href="' . $scripturl . '?board=' . $board_info['id'] . '">' . $board_info['name'] . '</a>',
		'get_items' => array('function' => 'hook_StickyTopicsOrder_topics_list'),
		'form' => array(
			'href' => $scripturl . $format . ';board=' . $board_info['id'],
		),
		'columns' => array(
			'topics' => array(
				'header' => array('value' => $txt['topic']),
				'data' => array(
					'sprintf' => array(
						'format' => '<a href="' . $scripturl . '?topic=%1$d.0">%2$s</a>',
						'params' => array(
							'id_topic' => false,
							'subject' => true,
						),
					),
				),
			),
			'order' => array(
				'header' => array('value' => $txt['StickyTopicsOrder_order']),
				'data' => array('db' => 'is_sticky'),
			),
			'oreder_new' => array(
				'header' => array('value' => $txt['StickyTopicsOrder_order_new']),
				'data' => array(
					'sprintf' => array(
						'format' => '<input name="tid[%1$d]" value="%2$d" />',
						'params' => array(
							'id_topic' => false,
							'is_sticky' => false,
						),
					),
				),
			),
		),
		'additional_rows' => array(array(
			'position' => 'bottom_of_list',
			'value' => '<input type="submit" name="save" value="' . $txt['save'] . '" />',
		)),
	);
	createList( $list );
}
function hook_StickyTopicsOrder_topics_list()
{
	global $smcFunc, $board;
	$request = $smcFunc['db_query']('', 'SELECT t.id_topic, t.is_sticky, m.subject
		FROM {db_prefix}topics t
		INNER JOIN {db_prefix}messages m ON t.id_first_msg = m.id_msg
		WHERE t.is_sticky != 0 AND t.id_board = {int:board}
		ORDER BY t.is_sticky DESC',
		array(
			'board' => (int) $board,
		)
	);

	$r = array();
	while( $row = $smcFunc['db_fetch_assoc']($request) )
		$r[ $row['id_topic'] ] = $row;
	$smcFunc['db_free_result']($request);

	return $r;
}
//display settings personal page
function template_hook_StickyTopicsOrder_modify_sa()
{
	template_show_list('sticky');
}
