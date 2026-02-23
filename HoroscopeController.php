<?php declare(strict_types=1);

namespace Nadybot\User\Modules\HOROSCOPE_MODULE;

use Amp\Http\Client\{HttpClientBuilder, Request};
use Amp\{TimeoutCancellation, TimeoutException};
use EventSauce\ObjectHydrator\UnableToHydrateObject;
use Nadybot\Core\{
	Attributes as NCA,
	CmdContext,
	Hydrator,
	ModuleInstance,
};

use Nadybot\Core\Types\AccessLevel;
use Psr\Log\LoggerInterface;

/**
 * A command to give you your daily horoscope.
 *
 * @author Nadyita (RK5) <nadyita@hodorraid.org>
 */
#[
	NCA\Instance,
	NCA\DefineCommand(
		command: 'horoscope',
		accessLevel: AccessLevel::Guest,
		description: 'Get your daily horoscope',
	)
]
class HoroscopeController extends ModuleInstance {
	/** The URL to the horoscope API with the zodiac as placeholder */
	public const HOROSCOPE_API = 'https://api.api-ninjas.com/v1/horoscope?zodiac=%s';

	/**
	 * An array of all Zodiac names, sorted by ecliptic longitude of the first point
	 *
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
	#[NCA\Inject]
	private HttpClientBuilder $http;

	#[NCA\Logger]
	private LoggerInterface $logger;

	/** Retrieve a horoscope depending on your user id */
	#[NCA\HandlesCommand('horoscope')]
	public function horoscopeCommand(CmdContext $context): void {
		$userID = $context->char->id;
		if (!isset($userID)) {
			return;
		}
		$zodiac = static::ZODIACS[$userID % 12];
		$client = $this->http->build();
		$request = new Request(sprintf(static::HOROSCOPE_API, $zodiac));
		try {
			$response = $client->request($request, new TimeoutCancellation(10));
			if ($response->getStatus() !== 200) {
				$msg = "There was an error getting today's horoscope. Please try again later.";
				$this->logger->error('Error getting horoscope. Status: {status}. Body: {body}', [
					'status' => $response->getStatus(),
					'body' => $response->getBody()->buffer(),
				]);
				$context->reply($msg);
				return;
			}
			$body = $response->getBody()->buffer();
		} catch (TimeoutException $e) {
			$msg = 'The horoscope-API timed out. Please try again later.';
			$context->reply($msg);
			return;
		}
		$reply = $this->parseHoroscope($body);
		$context->reply($reply);
	}

	private function parseHoroscope(string $body): string {
		if ($body === '') {
			return 'No horoscope today.';
		}
		try {
			$horoscope = Hydrator::hydrateString(Horoscope::class, $body);
		} catch (UnableToHydrateObject $e) {
			$this->logger->error('Unable to parse horoscope. Error: {error}. Body: {body}', [
				'error' => $e->getMessage(),
				'exception' => $e,
				'body' => $body,
			]);
			return "Today's horoscope was invalid. Please try again later.";
		}
		if (!$horoscope->isValid()) {
			return 'It seems the horoscope-API we are using has changed. Please contact nadyita@hodorraid.org';
		}
		return $horoscope->horoscope;
	}
}
