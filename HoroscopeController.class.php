<?php declare(strict_types=1);

namespace Nadybot\User\Modules;

use Nadybot\Core\{
	Attributes as NCA,
	CmdContext,
	Http,
	HttpResponse,
	ModuleInstance,
};

use function Safe\json_decode;
use Safe\Exceptions\JsonException;

/**
 * A command to give you your daily horoscope.
 *
 * @author Nadyita (RK5) <nadyita@hodorraid.org>
 */
#[
	NCA\Instance,
	NCA\DefineCommand(
		command:     'horoscope',
		accessLevel: 'guest',
		description: 'Get your daily horoscope',
	)
]
class HoroscopeController extends ModuleInstance {
	#[NCA\Inject]
	public Http $http;

	/**
	 * The URL to the horoscope API with the zodiac as placeholder
	 */
	public const HOROSCOPE_API = 'https://aztro.sameerkumar.website/?sign=%s&day=today';

	/**
	 * An array of all Zodiac names, sorted by ecliptic longitude of the first point
	 * @var string[] ZODIACS
	 */
	public const ZODIACS = [
		'Aries',
		'Taurus',
		'Gemini',
		'Cancer',
		'Leo',
		'Virgo',
		'Libra',
		'Scorpio',
		'Sagittarius',
		'Capricorn',
		'Aquarius',
		'Pisces',
	];

	/**
	 * Retrieve a horoscope depending on your user id
	 */
	#[NCA\HandlesCommand("horoscope")]
	public function horoscopeCommand(CmdContext $context): void {
		$userID = $context->char->id;
		if (!isset($userID)) {
			return;
		}
		$zodiac = static::ZODIACS[$userID % 12];
		$this->http
			->post(sprintf(static::HOROSCOPE_API, $zodiac))
			->withTimeout(5)
			->withCallback([$this, "sendHoroscope"], $context);
	}

	public function sendHoroscope(HttpResponse $response, CmdContext $context): void {
		if (isset($response->error)) {
			$msg = "There was an error getting today's horoscope: ".$response->error.". Please try again later.";
			$context->reply($msg);
			return;
		}
		if (!isset($response->body)) {
			$msg = "Today's horoscope id empty. Please try again later.";
			$context->reply($msg);
			return;
		}
		try {
			$horoscope = new Horoscope(json_decode($response->body, true));
		} catch (JsonException) {
			$msg = "Today's horoscope was invalid. Please try again later.";
			$context->reply($msg);
			return;
		}
		if (!$horoscope->isValid()) {
			$msg = 'It seems the horoscope-API we are using has changed. Please contact nadyita@hodorraid.org';
			$context->reply($msg);
			return;
		}
		$context->reply(
			$horoscope->description . "\n".
			"Lucky number: {$horoscope->lucky_number}, lucky time: {$horoscope->lucky_time}, color: {$horoscope->color}"
		);
	}
}
