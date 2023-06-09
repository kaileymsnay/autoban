<?php
/**
 *
 * Auto Ban extension for the phpBB Forum Software package
 *
 * @copyright (c) 2021, Kailey Snay, https://www.snayhomelab.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace kaileymsnay\autoban\event;

/**
 * @ignore
 */
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Auto Ban event listener
 */
class main_listener implements EventSubscriberInterface
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var */
	protected $root_path;

	/** @var */
	protected $php_ext;

	/**
	 * Constructor
	 *
	 * @param \phpbb\config\config      $config
	 * @param \phpbb\language\language  $language
	 * @param                           $root_path
	 * @param                           $php_ext
	 */
	public function __construct(\phpbb\config\config $config, \phpbb\language\language $language, $root_path, $php_ext)
	{
		$this->config = $config;
		$this->language = $language;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;
	}

	public static function getSubscribedEvents()
	{
		return [
			'core.user_setup'	=> 'user_setup',

			'core.mcp_warn_post_after'	=> 'mcp_warn_after',
			'core.mcp_warn_user_after'	=> 'mcp_warn_after',

			'core.acp_board_config_edit_add'	=> 'acp_board_config_edit_add',
		];
	}

	public function user_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = [
			'ext_name' => 'kaileymsnay/autoban',
			'lang_set' => 'common',
		];
		$event['lang_set_ext'] = $lang_set_ext;
	}

	public function mcp_warn_after($event)
	{
		$user_row = $event['user_row'];

		$user_row['user_warnings']++;

		if ($user_row['user_warnings'] >= $this->config['auto_ban_warnings'])
		{
			// User has reached maximum number of warnings
			if (!function_exists('user_ban'))
			{
				include($this->root_path . 'includes/functions_user.' . $this->php_ext);
			}

			user_ban('user', [$event['user_row']['username']], $this->config['auto_ban_expire'], false, 0, $this->language->lang('AUTO_BAN_REASON'));
		}
	}

	public function acp_board_config_edit_add($event)
	{
		if ($event['mode'] == 'settings')
		{
			$config_vars = [
				'auto_ban_warnings'	=> ['lang' => 'AUTO_BAN_WARNINGS', 'validate' => 'int:0:9999', 'type' => 'number:0:9999', 'explain' => true],
				'auto_ban_expire'	=> ['lang' => 'AUTO_BAN_EXPIRE', 'validate' => 'int:0:9999', 'type' => 'number:0:9999', 'explain' => true],
			];

			$event->update_subarray('display_vars', 'vars', phpbb_insert_config_array($event['display_vars']['vars'], $config_vars, ['after' => 'warnings_expire_days']));
		}
	}
}
