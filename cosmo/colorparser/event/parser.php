<?php
/**
 *
 * @author Tekin Birdüzen <t.birduezen@web-coding.eu>
 * @since 09.06.15
 * @version 1.0.0
 * @copyright Tekin Birdüzen
 */


namespace cosmo\colorparser\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event listener
 */
class parser implements EventSubscriberInterface {
	private static $color_open;
	private static $color_close;

	public static function getSubscribedEvents() {
		return array(
			'core.modify_bbcode_init'  => 'initialize_fp_color',
			'core.bbcode_cache_init_end' => 'initialize_sp_color'
		);
	}

	public function initialize_fp_color ($event) {
		$new_color = $event['bbcodes'];
		$new_color['color'] = array('bbcode_id' => 6,	'regexp' => array('!\[color=(#[0-9a-f]{3}|#[0-9a-f]{6}|[a-z\-]+)\](.+)\[/color\]!uise' => "cosmo\\colorparser\\event\\parser::bbcode_first_pass_colors('\$0', \$this)"));

		$event['bbcodes'] = $new_color;
	}

	public function initialize_sp_color($event) {
		$tmp = $event['bbcode_cache'];
		$tmp[6] = array(
			'preg' => array(
				'/\[color=(#[0-9a-f]{3}|#[0-9a-f]{6}|[a-z\-]+):$uid\]((?!\[color=(#[0-9a-f]{3}|#[0-9a-f]{6}|[a-z\-]+):$uid\]).)?/ise'	=> "cosmo\\colorparser\\event\\parser::bbcode_second_pass_color_open('\$1', '\$2', \$this, \$bbcode_id)",
				'/\[\/color:$uid\]/ie' => "cosmo\\colorparser\\event\\parser::bbcode_second_pass_color_close(\$this, \$bbcode_id)"
			)
		);
		$event['bbcode_cache'] = $tmp;
	}

	/**
	 * Firstpass color bbcode
	 */
	public static function bbcode_first_pass_colors($in, $that)
	{
		$in = str_replace("\r\n", "\n", str_replace('\"', '"', trim($in)));

		if (!$in)
		{
			return '';
		}

		$out = $in;
		do {
			$in = $out;
			$out = preg_replace('/\[color=(#[0-9a-f]{3}|#[0-9a-f]{6}|[a-z\-]+)\]((?:.(?!\[color=(#[0-9a-f]{3}|#[0-9a-f]{6}|[a-z\-]+)\]))*?)\[\/color\]/is', '[color=$1:'.$that->bbcode_uid.']$2[/color:'.$that->bbcode_uid.']', $in);
		} while($out !== $in);

		return $out;
	}

	/**
	 * Secondpass color bbcode
	 */

	public static function bbcode_second_pass_color_open($color, $text, $that, $bbcode_id) {
		// Already got the part?
		if (!is_string(self::$color_open)) {
			self::get_tpl_parts($that, $bbcode_id);
		}

		// when using the /e modifier, preg_replace slashes double-quotes but does not
		// seem to slash anything else
		$color = str_replace('\"', '"', $color);
		$text = str_replace('\"', '"', $text);

		// remove newline at the beginning
		if ($text === "\n")
		{
			$text = '';
		}

		$text = str_replace('$1', $color, self::$color_open) . $text;

		return $text;
	}

	public static function bbcode_second_pass_color_close($that, $bbcode_id) {
		// Already got the part?
		if (!is_string(self::$color_close)) {
			self::get_tpl_parts($that, $bbcode_id);
		}

		return self::$color_close;
	}

	public static function get_tpl_parts($that, $bbcode_id) {
		$tpl = $that->bbcode_tpl('color', $bbcode_id);
		$strpos = strpos($tpl, '$2');
		self::$color_open = substr($tpl, 0, $strpos);
		self::$color_close = substr($tpl, $strpos+2);
	}
}