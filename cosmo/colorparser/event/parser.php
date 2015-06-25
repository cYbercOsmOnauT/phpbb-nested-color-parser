<?php
/**
 *
 * @author    Tekin Birdüzen <t.birduezen@web-coding.eu>
 * @since     09.06.15
 * @version   1.1.0
 * @copyright Tekin Birdüzen
 */


namespace cosmo\colorparser\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event listener
 */
class parser implements EventSubscriberInterface
{
	private $color_open;
	private $color_close;
	private $bbcode;
	private $bbcode_id;

	public static function getSubscribedEvents()
	{
		return array(
			'core.modify_bbcode_init'              => 'initialize_fp_color',
			'core.bbcode_cache_init_end'           => 'initialize_sp_color',
			'core.validate_bbcode_by_extension'    => 'bbcode_first_pass_colors',
			'core.bbcode_second_pass_by_extension' => 'bbcode_second_pass_color'
		);
	}

	public function initialize_fp_color($event)
	{
		$new_color = $event['bbcodes'];
		$new_color['color'] = array('bbcode_id' => 6, 'regexp' => array('!\[color=(#[0-9a-f]{3}|#[0-9a-f]{6}|[a-z\-]+)\](.+)\[/color\]!uise' => "\$this->validate_bbcode_by_extension('\$0', \$this)"));

		$event['bbcodes'] = $new_color;
	}

	public function initialize_sp_color($event)
	{
		$tmp = $event['bbcode_cache'];
		$tmp[6] = array(
			'preg' => array(
				'/\[color=(#[0-9a-f]{3}|#[0-9a-f]{6}|[a-z\-]+):$uid\]((?!\[color=(#[0-9a-f]{3}|#[0-9a-f]{6}|[a-z\-]+):$uid\]).)?/ise' => "\$this->bbcode_second_pass_by_extension('open', \$this,  \$bbcode_id, '\$1', '\$2')",
				'/\[\/color:$uid\]/ie'                                                                                                => "\$this->bbcode_second_pass_by_extension('close', \$this, \$bbcode_id)"
			)
		);
		$event['bbcode_cache'] = $tmp;
	}

	/**
	 * Firstpass color bbcode
	 */
	public function bbcode_first_pass_colors($event)
	{
		$in = $event['params_array'][0];
		$this->bbcode = $event['params_array'][1];
		$in = str_replace("\r\n", "\n", str_replace('\"', '"', trim($in)));

		if (!$in)
		{
			$event['return'] = '';
			return;
		}

		$out = $in;
		do
		{
			$in = $out;
			$out = preg_replace('/\[color=(#[0-9a-f]{3}|#[0-9a-f]{6}|[a-z\-]+)\]((?:.(?!\[color=(#[0-9a-f]{3}|#[0-9a-f]{6}|[a-z\-]+)\]))*?)\[\/color\]/is', '[color=$1:' . $this->bbcode->bbcode_uid . ']$2[/color:' . $this->bbcode->bbcode_uid . ']', $in);
		} while ($out !== $in);

		$event['return'] = $out;
	}

	/**
	 * Secondpass color bbcode
	 */

	public function bbcode_second_pass_color($event)
	{
		$mode = $event['params_array'][0];
		$this->bbcode = $event['params_array'][1];
		$this->bbcode_id = $event['params_array'][2];

		// open or close?
		if ('open' === $mode)
		{
			// These two variables are not really needed
			// It's just to make clear what they are for
			$color = $event['params_array'][3];
			$text = $event['params_array'][4];
			$event['return'] = $this->bbcode_second_pass_color_open($color, $text);
		}
		else
		{
			$event['return'] = $this->bbcode_second_pass_color_close();
		}
	}

	private function bbcode_second_pass_color_open($color, $text)
	{
		// Already got the part?
		if (!is_string($this->color_open))
		{
			$this->get_tpl_parts();
		}

		// when using the /e modifier, preg_replace slashes double-quotes but does not
		// seem to slash anything else
		$text = str_replace('\"', '"', $text);

		// remove newline at the beginning
		if ("\n" === $text) {
			$text = '';
		}

		$text = str_replace('$1', $color, $this->color_open) . $text;

		return $text;
	}

	private function bbcode_second_pass_color_close()
	{
		// Already got the part?
		if (!is_string($this->color_close))
		{
			$this->get_tpl_parts();
		}

		return $this->color_close;
	}

	private function get_tpl_parts()
	{
		$tpl = $this->bbcode->bbcode_tpl('color', $this->bbcode_id);
		$strpos = strpos($tpl, '$2');
		$this->color_open = substr($tpl, 0, $strpos);
		$this->color_close = substr($tpl, $strpos + 2);
	}
}
