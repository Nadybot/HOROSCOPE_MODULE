<?php declare(strict_types=1);

namespace Nadybot\User\Modules;

use Spatie\DataTransferObject\DataTransferObject;

class Horoscope extends DataTransferObject {
	/** Date in February 25, 2022 format from which this horoscope is */
	public string $current_date;

	/** The days where this zodiac is active */
	public string $date_range;

	/** Which zodiac is compatible with you */
	public string $compatibility;

	/** The horoscope text */
	public string $description;

	/** Which mood you're in */
	public string $mood;

	/** Your lucky number */
	public string $lucky_number;

	/** Your lucky time */
	public string $lucky_time;

	/** Your color */
	public string $color;

	public function isValid(): bool {
		return strlen($this->current_date) > 5
			&& strlen($this->description) > 5;
	}
}
