<?php declare(strict_types=1);

namespace Nadybot\User\Modules\HOROSCOPE_MODULE;

use DateTimeInterface;
use EventSauce\ObjectHydrator\PropertyCasters\CastToDateTimeImmutable;

class Horoscope {
	/**
	 * @param DateTimeInterface $date      Date for which the horoscope is
	 * @param string            $sign      The zodiac sign
	 * @param string            $horoscope The horoscope text
	 */
	public function __construct(
		#[CastToDateTimeImmutable('Y-m-d')] public DateTimeInterface $date,
		public string $sign,
		public string $horoscope,
	) {
	}

	public function isValid(): bool {
		return strlen($this->horoscope) > 5;
	}
}
